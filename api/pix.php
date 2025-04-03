<?php

$config = require_once '../config.php';
require_once '../class/Conn.class.php';
require_once '../class/Payment.class.php';


$amount = (float) trim($_GET['vl']);
$user_id = $_GET['user_id'] ?? null;
$tipo = $_GET['tipo'] ?? null;

if (!isset($amount) || empty($amount) || !is_numeric($amount) || $amount < $config['min_donation'] || $amount > $config['max_donation']) {
    die("O valor da doação deve estar entre R$ {$config['min_donation']} e R$ {$config['max_donation']}. Por favor, verifique o valor informado e tente novamente.");
}
if (!$user_id || !is_numeric($user_id)) {
    die('Não foi possível identificar seu usuário. Por favor, faça login novamente e tente realizar a doação.');
}
if ($tipo === null || ($tipo != 0 && $tipo != 1)) {
    die('O tipo de pagamento selecionado é inválido. Por favor, selecione uma opção válida e tente novamente.');
}


$payment = new Payment($user_id);
$payCreate = $payment->addPayment($amount, $tipo);

if ($payCreate) {
    $accesstoken = $config['accesstoken'];
    $pixUrl = 'https://api.mercadopago.com/v1/payments';

    $codigoKey = substr(str_shuffle('123456789ABCDFGHIJKLMNPQRSTUVXYZ'), 0, 5) . '-' . substr(str_shuffle('123456789ABCDFGHIJKLMNPQRSTUVXYZ'), 0, 8) . '-' . substr(str_shuffle('123456789ABCDFGHIJKLMNPQRSTUVXYZ'), 0, 4);

    $payload = json_encode([
        'description' => 'Pagamento PIX',
        'external_reference' => $payCreate,
        'notification_url' => $config['url_notification_api'],
        'payer' => [
            'email' => 'test_user_123@testuser.com',
            'identification' => [
                'type' => 'CPF',
                'number' => '95749019047'
            ]
        ],
        'payment_method_id' => 'pix',
        'transaction_amount' => $amount
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $pixUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accesstoken,
            'X-Idempotency-Key: ' . $codigoKey
        ]
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $obj = json_decode($response);

    if (isset($obj->id) && $obj->id !== NULL) {
        $copiaCopia = $obj->point_of_interaction->transaction_data->qr_code;
        $imgQrCode = $obj->point_of_interaction->transaction_data->qr_code_base64;
        $linkExterno = $obj->point_of_interaction->transaction_data->ticket_url;
        $transactionAmount = $obj->transaction_amount;
        $externalReference = $obj->external_reference;

        $pdo = DB::getInstance();
        $query = $pdo->prepare("SELECT status FROM `payment` WHERE id = :externalReference");
        $query->bindParam(':externalReference', $externalReference);
        $query->execute();
        $paymentStatus = $query->fetch(PDO::FETCH_ASSOC)['status'];
        if ($paymentStatus == "processing") {
            $paymentStatus = "Em Processamento";
        }

        echo "
        <link rel='stylesheet' type='text/css' href='style.css'>
        <style>
            body {
                background-color: #111827;
            }
        </style>
        <div class='container'>
            <h3>R$ {$transactionAmount} #{$externalReference}</h3>
            <p>Status do pagamento: <span id='payment-status' class='{$paymentStatus}'>{$paymentStatus}</span></p>
            <div class='qrcode-container'>
                <img src='data:image/png;base64, {$imgQrCode}' class='centered-qrcode' />
            </div>
            <div class='centered-content'>
            <a><p>Ou</p></a>
            <a><p>Copie o codigo abaixo:</p></a>
                <textarea>{$copiaCopia}</textarea>
            </div>
        </div>
        <script>
        setInterval(function() {
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var status = this.responseText;
                    if (status.trim().toLowerCase() === 'pending') {
                    status = 'Aguardando Pagamento';
                }
                else if (status.trim().toLowerCase() === 'approved') {
                    status = 'Pagamento Aprovado';
                }
                    document.getElementById('payment-status').innerText = status;
                    document.getElementById('payment-status').className = status;
                }
            };
            xhttp.open('GET', 'get_payment_status.php?external_reference={$externalReference}', true);
            xhttp.send();
        }, 5000); // 5000 milissegundos = 5 segundos
    </script>
        ";
    }
}
