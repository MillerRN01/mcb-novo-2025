<?php
$servido = "localhost"; // Endereço do servidor MySQL
$usuario = "root";  // Usuário do MySQL
$senha = "";     // Senha do MySQL
$banco_dados = "mcb"; // Nome do banco de dados

// Criando a conexão com o banco de dados
$conn = new mysqli($servido, $usuario, $senha, $banco_dados);

// Verificando se houve erro na conexão
if ($conn->connect_error) {
    // Lançar uma exceção em vez de imprimir uma mensagem
    throw new Exception('Erro na conexão: ' . $conn->connect_error);
}

// Se você quiser retornar a conexão, você pode fazer isso
return $conn;
?>