<?php

$config = require_once '../config.php';
require_once '../class/Conn.class.php';
require_once '../class/Payment.class.php';

$accesstoken = $config['accesstoken'];

try {
  $body = json_decode(file_get_contents('php://input'));

  if (!isset($body->data->id)) {
    throw new Exception('Notificação recebida sem identificador de pagamento válido.');
  }

  $id = $body->data->id;

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Authorization: Bearer ' . $accesstoken
    ),
  ));

  $response = curl_exec($curl);

  if (curl_errno($curl)) {
    throw new Exception('Não foi possível conectar ao serviço de pagamento. Por favor, tente novamente mais tarde.');
  }

  curl_close($curl);

  $payment = json_decode($response);

  if (!isset($payment->id)) {
    throw new Exception('Não foi possível obter informações do pagamento. Por favor, verifique se o pagamento foi realizado corretamente.');
  }

  $payment_class = new Payment();
  $payment_class->payment_id = $payment->external_reference;
  $payment_data = $payment_class->get();

  if (!$payment_data) {
    throw new Exception('Pagamento não encontrado em nosso sistema. Por favor, entre em contato com o suporte.');
  }

  $result = $payment_class->setStatusPayment($payment->status);

  if (!$result) {
    throw new Exception('Não foi possível atualizar o status do pagamento. Por favor, tente novamente mais tarde.');
  }

  // Log de sucesso
  error_log('Notificação processada com sucesso para o pagamento ID: ' . $payment->external_reference);
} catch (Exception $e) {
  // Log do erro
  error_log('Erro ao processar notificação: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['erro' => 'Ocorreu um problema ao processar sua solicitação. Nossa equipe foi notificada.']);
}
