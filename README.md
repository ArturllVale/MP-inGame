# MP inGame

Sistema de doação in-game utilizando MercadoPago (Pix e Cartão de Crédito)

## Funcionalidades

- Doação via Pix
- Doação via Cartão de Crédito
- Sistema Cash
- Sistema RMT

## Instalação

1. Clone o repositório
2. Configure o arquivo `config.php` com suas credenciais do MercadoPago
3. Importe o script `donate.txt` para seu servidor rAthena

## Configuração

Renomeie o arquivo `config.example.php` para `config.php` e preencha as informações:

```php
return [
    'accesstoken' => 'SEU_ACCESS_TOKEN_AQUI',
    'min_donation' => 1,
    'max_donation' => 1000,
    'donation_multiplicate' => 1,
    'url_notification_api' => 'URL_DE_NOTIFICACAO'
];
