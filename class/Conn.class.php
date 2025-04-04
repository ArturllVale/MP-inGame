<?php

class DB
{
  private $host;
  private $user;
  private $senha;
  private $bd;

  public $pdo;

  private static $instance = null;

  private function __construct()
  {
    $config = require_once dirname(__DIR__) . '/config.php';

    $this->host   = $config['db_host'];
    $this->user   = $config['db_user'];
    $this->senha  = $config['db_password'];
    $this->bd     = $config['db_name'];

    $this->pdo = new PDO("mysql:host=$this->host;dbname=$this->bd", $this->user, $this->senha, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8MB4"));
  }

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance->pdo;
  }
}
