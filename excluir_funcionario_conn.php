<?php
include_once 'conexao.php';
require_once 'verifica_session_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_funcionario'])) {
    $id_funcionario = $_POST['id_funcionario'];

    // Inicia transação para garantir que todas as operações sejam concluídas
    $conn->begin_transaction();

    try {
        // 1. Primeiro obtemos os IDs relacionados
        $sql = "SELECT login_id, endereco_id FROM funcionario WHERE id_funcionario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_funcionario);
        $stmt->execute();
        $result = $stmt->get_result();
        $dados = $result->fetch_assoc();
        $stmt->close();

        if ($dados) {
            $login_id = $dados['login_id'];
            $endereco_id = $dados['endereco_id'];

            // 2. Exclui o funcionário
            $sql = "DELETE FROM funcionario WHERE id_funcionario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_funcionario);
            $stmt->execute();
            $stmt->close();

            // 3. Exclui o login associado (se existir)
            if ($login_id) {
                $sql = "DELETE FROM login WHERE id_login = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $login_id);
                $stmt->execute();
                $stmt->close();
            }

            // 4. Exclui o endereço associado (se existir)
            if ($endereco_id) {
                $sql = "DELETE FROM enderecos WHERE id_endereco = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $endereco_id);
                $stmt->execute();
                $stmt->close();
            }

            // Confirma todas as operações
            $conn->commit();

            $_SESSION['mensagem'] = 'Funcionário excluído com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
        } else {
            $_SESSION['mensagem'] = 'Funcionário não encontrado!';
            $_SESSION['tipo_mensagem'] = 'danger';
        }
    } catch (Exception $e) {
        // Em caso de erro, reverte todas as operações
        $conn->rollback();
        $_SESSION['mensagem'] = 'Erro ao excluir funcionário: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
    }

    header('Location: cadastro_funcionario.php');
    exit();
}
