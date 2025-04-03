<?php

// Verifique se o external_reference foi recebido via GET
if (!isset($_GET['external_reference'])) {
    http_response_code(400); // Bad Request
    echo "Não foi possível verificar o status do pagamento. Informação necessária não encontrada.";
    exit;
}

$external_reference = $_GET['external_reference'];

// Inclua o arquivo de configuração e a classe DB
require_once '../config.php';
require_once '../class/Conn.class.php';

// Conecte-se ao banco de dados usando a classe DB
$pdo = DB::getInstance();

// Consulta SQL para obter o status do pagamento usando $external_reference
$query = $pdo->prepare("SELECT status FROM `payment` WHERE id = :external_reference");
$query->bindParam(':external_reference', $external_reference);
$query->execute();
$payment_status = $query->fetch(PDO::FETCH_ASSOC)['status'];

// Verifique se o status foi encontrado no banco de dados
if ($payment_status) {
    // Retorne o status do pagamento
    echo $payment_status;
} else {
    http_response_code(404); // Not Found
    echo "Não foi possível encontrar informações sobre este pagamento. Por favor, verifique se o código está correto.";
}
