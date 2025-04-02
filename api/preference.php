<?php

$config = require_once '../config.php';
require_once '../class/Conn.class.php';
require_once '../class/Payment.class.php';

$accesstoken = $config['accesstoken'];
$amount = (float) trim($_GET['vl']);
$user_id = $_GET['user_id'] ?? null;
$tipo = $_GET['tipo'] ?? null;

if (!isset($amount) || empty($amount) || !is_numeric($amount) || $amount < $config['min_donation'] || $amount > $config['max_donation']) {
    die("O valor deve ser entre {$config['min_donation']} e {$config['max_donation']}, e não pode ser vazio.");
}
if (!$user_id || !is_numeric($user_id)) {
    die('user_id inválido');
}
if ($tipo === null || ($tipo != 0 && $tipo != 1)) {
    die('tipo inválido');
}

$payment = new Payment($user_id);
$payCreate = $payment->addPayment($amount, $tipo);
	

if ($payCreate) {

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode([
            'back_urls' => [
                'success' => $config['url_success'],
                'pending' => $config['url_pending'],
                'failure' => $config['url_failure']
            ],
            'external_reference' => $payCreate,
            'notification_url' => $config['url_notification_api'],
            'auto_return' => 'approved',
            'items' => [[
                'title' => 'Ragnarok',
                'description' => 'Dummy description',
                'picture_url' => 'http://www.myapp.com/myimage.jpg',
                'category_id' => 'car_electronics',
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => $amount
            ]],
            'payment_methods' => [
                'excluded_payment_methods' => [['id' => 'pix']],
                'excluded_payment_types' => [['id' => 'ticket']]
            ]
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['accesstoken']
        ],
    ]);


    if ($response = curl_exec($curl)) {
        $obj = json_decode($response);
        if (isset($obj->init_point)) {
            echo "<script>window.location.href = '{$obj->init_point}';</script>";
        }
    } else {
        echo 'Erro na solicitação cURL: ' . curl_error($curl);
    }
    curl_close($curl);
}

?>