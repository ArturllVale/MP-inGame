<?php
$access_token = "TEST-5850301002742227-041913-cbbe262227292dcdba99553a9f11f693-294370258";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $payment_data = array(
        "transaction_amount" => 0.50, // Valor da transação
        "token" => $token,
        "description" => "Descrição do Produto",
        "installments" => 1, // Parcelas
        "payer" => array(
            "email" => "email_do_cliente@example.com",
            "identification" => array(
                "type" => $_POST['docType'],
                "number" => $_POST['docNumber']
            )
        )
    );


    $idempotency_key = uniqid('mp_', true);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments?access_token=" . $access_token);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-Idempotency-Key: ' . $idempotency_key
    ));

    $result = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($result, true);

    if (isset($response['status']) && $response['status'] == 'approved') {
        echo "Pagamento aprovado! Obrigado pela sua compra.";
    } else {
        echo "Não foi possível completar seu pagamento.";
        if (isset($response['status_detail'])) {
            $status_detail = $response['status_detail'];
            $mensagem_amigavel = "";

            // Traduzir os códigos de erro para mensagens amigáveis
            switch ($status_detail) {
                case "cc_rejected_bad_filled_date":
                    $mensagem_amigavel = "A data de validade do cartão está incorreta.";
                    break;
                case "cc_rejected_bad_filled_other":
                    $mensagem_amigavel = "Alguma informação do cartão está incorreta.";
                    break;
                case "cc_rejected_bad_filled_security_code":
                    $mensagem_amigavel = "O código de segurança do cartão está incorreto.";
                    break;
                case "cc_rejected_blacklist":
                    $mensagem_amigavel = "O cartão está em uma lista de restrição.";
                    break;
                case "cc_rejected_call_for_authorize":
                    $mensagem_amigavel = "Você precisa autorizar o pagamento com o banco emissor.";
                    break;
                case "cc_rejected_card_disabled":
                    $mensagem_amigavel = "O cartão está desativado.";
                    break;
                case "cc_rejected_duplicated_payment":
                    $mensagem_amigavel = "Você já realizou um pagamento com esse valor.";
                    break;
                case "cc_rejected_high_risk":
                    $mensagem_amigavel = "Seu pagamento foi recusado por motivos de segurança.";
                    break;
                case "cc_rejected_insufficient_amount":
                    $mensagem_amigavel = "O cartão não possui saldo suficiente.";
                    break;
                case "cc_rejected_invalid_installments":
                    $mensagem_amigavel = "O cartão não aceita o número de parcelas selecionado.";
                    break;
                case "cc_rejected_max_attempts":
                    $mensagem_amigavel = "Você atingiu o limite de tentativas permitidas.";
                    break;
                default:
                    $mensagem_amigavel = "Por favor, verifique os dados do cartão ou tente outro método de pagamento.";
            }
            echo " " . $mensagem_amigavel;
        } else {
            echo " Por favor, verifique os dados do seu cartão e tente novamente.";
        }
    }
}
