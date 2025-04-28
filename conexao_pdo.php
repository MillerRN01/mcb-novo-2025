<?php
// Função para sanitizar entradas
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Configurações do banco de dados
$host = getenv('DB_host') ?: 'localhost';
$banco_dados = getenv('DB_banco_dados') ?: 'mcb';
$usuario = getenv('DB_usuario') ?: 'root';
$senha = getenv('DB_senha') ?: '';
$charset = 'utf8mb4';

// DSN para conexão PDO
$dsn = "mysql:host=$host;dbname=$banco_dados;charset=$charset";

// Opções de conexão PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Conexão ao banco de dados
    $pdo = new PDO($dsn, $usuario, $senha, $options);
    $pdo->exec("SET NAMES '$charset'");
} catch (PDOException $e) {
    // Caminho do log de erros
    $logFile = __DIR__ . '/assets/erros/logs/errors.log';
    
    // Cria a pasta caso não exista
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    // Registra o erro no log
    error_log($e->getMessage(), 3, $logFile);
    
    // Mensagem genérica ao usuário
    echo 'Estamos enfrentando problemas técnicos. Por favor, tente novamente mais tarde.';
    exit;
}
?>
