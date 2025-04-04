<?php

// https://www.mercadopago.com.br/developers
// Cria uma aplicação Checkout Transparente

return [    
    // Configurações do banco de dados
    'db_host'                      => 'localhost',
    'db_user'                      => 'ragnarok',
    'db_password'                  => 'ragnarok',
    'db_name'                      => 'ragnarok',
    'accesstoken'                   => 'ACCESS_TOKEN',                                  //SEU ACESS_TOKEN
    'url_notification_api'          => 'https://exemple.com/mp/api/notification.php',
    'url_success'                   => 'https://exemple.com/success',                   //URL_PAGAMENTO_APROVADO
    'url_pending'                   => 'https://exemple.com/pending',                   //URL_PAGAMENTO_PENDENTE
    'url_failure'                   => 'https://exemple.com/failure',                   //URL_PAGAMENTO_REJEITADO
    'min_donation'                  => 10,                                               //Valor mínimo pra doação
    'max_donation'                  => 1000,                                            //Valor máximo pra doação
    'donation_multiplicate'         => 10,                                              //taxa de multiplicação da doação (10 = valor da doação * 10)
    'mp_public_key'                 => 'YOUR_PUBLIC_KEY',                               // Adicionar chave pública do MP
];
