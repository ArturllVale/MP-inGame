<?php
if (!file_exists('../config.php')) {
    die("Erro: O arquivo de configuração '../config.php' não foi encontrado.");
}
$config = require_once '../config.php';
require_once '../class/Conn.class.php';
require_once '../class/Payment.class.php';

$accesstoken = $config['accesstoken'];
$amount = isset($_GET['vl']) ? (float) trim($_GET['vl']) : 0.0;
$user_id = $_GET['user_id'] ?? null;
$tipo = $_GET['tipo'] ?? null;

try {
    // Validação de entrada
    if (!isset($amount) || empty($amount) || !is_numeric($amount) || $amount < $config['min_donation'] || $amount > $config['max_donation']) {
        throw new Exception("O valor deve ser entre {$config['min_donation']} e {$config['max_donation']}, e não pode ser vazio.");
    }
    if (!$user_id || !is_numeric($user_id)) {
        throw new Exception("user_id inválido");
    }
    if ($tipo === null || ($tipo != 0 && $tipo != 1)) {
        throw new Exception("tipo inválido");
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $payment = new Payment($user_id);
        $payCreate = $payment->addPayment($amount, $tipo);
        if (!$payCreate) {
            throw new Exception("Erro ao criar pagamento.");
        }

        $card_data = array(
            "card_number" => preg_replace('/\s+/', '', $_POST['cardNumber']),
            "cardholder" => array(
                "name" => $_POST['cardholderName'],
                "identification" => array(
                    "type" => $_POST['docType'],
                    "number" => $_POST['docNumber']
                )
            ),
            "expiration_month" => $_POST['cardExpirationMonth'],
            "expiration_year" => "20{$_POST['cardExpirationYear']}",
            "security_code" => $_POST['securityCode']
        );

        // Chamada para gerar o token do cartão
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/card_tokens?access_token=" . $accesstoken);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($card_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Erro na chamada de API (gerar token): " . curl_error($ch));
        }
        curl_close($ch);

        $response = json_decode($result, true);
        if (!isset($response['id'])) {
            throw new Exception("Erro ao gerar o token do cartão: " . json_encode($response));
        }

        $token = $response['id'];
        $payment_method_id = $response['payment_method_id'] ?? null;

        $payment_data = array(
            "transaction_amount" => $amount,
            "token" => $token,
            "description" => "Descrição do Produto",
            "installments" => 1,
            "payment_method_id" => $payment_method_id,
            "external_reference" => $payCreate,
            "notification_url" => $config['url_notification_api'],
            "payer" => array(
                "email" => "user_email@example.com",
                "identification" => [
                    "type" => $_POST['docType'],
                    "number" => $_POST['docNumber']
                ]
            )
        );

        $idempotency_key = uniqid('mp_', true);

        // Chamada para processar o pagamento
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments?access_token=" . $accesstoken);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "X-Idempotency-Key: $idempotency_key"
        ));

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Erro na chamada de API (processar pagamento): " . curl_error($ch));
        }
        curl_close($ch);

        $response = json_decode($result, true);
        if (isset($response['status']) && $response['status'] == 'approved') {
            $message = "<p class='green'>Pagamento aprovado!</p>";
        } else {
            $status_detail = $response['status_detail'] ?? "Detalhes não disponíveis.";
            throw new Exception("Pagamento não aprovado. Motivo: " . $status_detail);
        }
    }
} catch (Exception $e) {
    $message = "<p class='red'>Erro: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="boot.css" />
    <link rel="stylesheet" href="style.css" />
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mp = new MercadoPago('<?php echo $config['mp_public_key']; ?>');
        });
    </script>
</head>

<body>

    <div class="wrapper">
        <form id="paymentForm" action="" method="POST" class="form-container">
            <div class="box-form">
                <div class="input-content">
                    <div class="box-input">
                        <label>Número do cartão</label>
                        <input autocomplete="off" type="text" id="cardNumber" name="cardNumber" maxlength="19"
                            placeholder="1234 1234 1234 1234" data-checkout="cardNumber" style="word-spacing: 8px;"
                            oninput="updateCreditCardInfo();" onkeydown="handleNumber(event)">

                    </div>

                    <div class="box-input">
                        <label>Nome do titular</label>
                        <input autocomplete="off" type="text" id="cardholderName" name="cardholderName"
                            placeholder="Ex.: Maria Silva" data-checkout="cardholderName" onkeydown="handleName(event)">
                        <div class="info">
                            <span class="icon">
                                <img src="../assets/warning.svg" />
                            </span>
                            <span class="message"></span>
                        </div>
                    </div>

                    <div class="box-input-more">
                        <div class="box-one">
                            <label>Mês</label>
                            <input type="text" id="cardExpirationMonth" name="cardExpirationMonth" placeholder="MM"
                                maxlength="2" data-checkout="cardExpirationMonth" required>
                        </div>
                        <div class="box-one">
                            <label>Ano</label>
                            <input type="text" id="cardExpirationYear" name="cardExpirationYear" placeholder="AA"
                                maxlength="2" data-checkout="cardExpirationYear" required>
                        </div>
                        <div class="box-two">
                            <label>
                                C V V
                                <span class="icon" title="Ajuda">
                                    <img src="../assets/help.svg" />
                                </span>
                            </label>
                            <input autocomplete="off" type="password" id="securityCode" name="securityCode"
                                placeholder="123" data-checkout="securityCode" maxlength="4"
                                onkeydown="handleCvv(event)">
                            <div class="info">
                                <span class="icon">
                                    <img src="../assets/warning.svg" />
                                </span>
                                <span class="message"></span>
                            </div>
                        </div>

                    </div>

                    <label>Documento</label>
                    <div class="box-input-more">
                        <div class="box-one">
                            <select name="docType" placeholder="Tipo de Documento" data-checkout="docType" required
                                style="width: 100px;">
                                <option value="CPF">CPF</option>
                                <option value="CNPJ">CNPJ</option>
                            </select>
                        </div>
                        <div class="box-one">
                            <input type="hidden" name="token" id="token">
                            <input type="text" id="docNumber" name="docNumber" placeholder="Número do Documento"
                                data-checkout="docNumber" required>
                        </div>
                    </div>
                </div>

                <div class="card-content animate__animated animate__backInUp">
                    <div class="card-content-box rotate">
                        <div class="box-card">
                            <div class="content">
                                <div class="card-header">
                                    <span class="icon"><img id="imagemBandeira" src="../assets/bandeira.png"
                                            alt="Bandeira do Cartão" style="display: none; max-width: 50px;"></span>
                                    <span class="icon"><img src="../assets/contact-less-payment.svg" /></span>
                                </div>
                                <div class="card-body">
                                    <div id="card-user-number" class="number-card">•••• •••• •••• ••••</div>
                                    <div class="name-and-date">
                                        <div id="card-user-name" class="name">Nome do Titular</div>
                                        <div id="card-user-date" class="date">MM/AA</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-card">
                            <div class="content card-2">
                                <div class="bar"></div>
                                <div class="cvv">
                                    <div id="card-user-cvv" class="cvv-number"></div>
                                    <div class="cvv-text">CVV</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="rotate-card" class="rotate-card" title="Ver a outra parte do cartão">
                        <img src="../assets/rotate.svg" width="20px">
                    </div>

                    <div class="status-security">
                        <span class="icon"><img src="../assets/shield.svg" /></span>
                        <span>Seus dados estão seguros</span>

                    </div>
                    <p style="color: white;">Valor: <?php echo "R$" . number_format($amount, 2, ',', '.'); ?></p>
                    <p style="color: white;">ROP's a Receber:
                        <?php echo intval($amount * $config['donation_multiplicate']) ?>
                    </p>

                </div>
            </div>
            <?php if (isset($message))
                echo "<br>" . $message; ?>
            <button id="input-submit" type="submit" class="button-submit">Pagar</button>
        </form>
    </div>



    <!-- Javascript | Start -->
    <script src="../js/script.js"></script>
    <script>
        $(document).ready(function() {
            $('#cardNumber').on('input', function() {
                var creditCardNumber = $(this).val();
                var cardType = identifyCreditCard(creditCardNumber);

            });
        });
    </script>
    <!-- Javascript | End -->
</body>

</html>