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
        echo "Pagamento aprovado!";
    } else {
        echo "Pagamento não aprovado.";
        if (isset($response['status_detail'])) {
            echo " Motivo: " . $response['status_detail'];
        } else {
            echo " Detalhes da resposta: " . print_r($response, true);
        }
    }
}
?>
