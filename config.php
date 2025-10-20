<?php
// config.php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Carrega as variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__);
// safeLoad() para carregar sem erro se o arquivo não estiver presente.
$dotenv->safeLoad(); 

/**
 * Retorna uma instância de PDO para conexão com o banco de dados.
 * As credenciais são lidas do arquivo .env
 * @throws PDOException
 */
function getPDO() {
    // As variáveis de ambiente são lidas do $_ENV graças ao Dotenv
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8";
    return new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}