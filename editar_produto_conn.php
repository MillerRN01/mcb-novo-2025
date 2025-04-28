<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se o ID do produto foi enviado
    if (!isset($_POST['id_produto'])) {
        $_SESSION['mensagem'] = "ID do produto não fornecido.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: cadastro_produto.php");
        exit;
    }

    $id_produto = intval($_POST['id_produto']);

    // Captura e sanitiza os dados do formulário
    $produto = trim($_POST['produto']);
    $preco = floatval(str_replace(['.', ','], ['', '.'], $_POST['preco']));
    $quantidade_estoque = intval($_POST['quantidade_estoque']);
    $categoria_id = intval($_POST['categoria_id']);
    $fornecedor_id = !empty($_POST['fornecedor_id']) ? intval($_POST['fornecedor_id']) : null;
    $status = trim($_POST['status']);
    $descricao = trim($_POST['descricao']);
    $estoque_minimo = !empty($_POST['estoque_minimo']) ? intval($_POST['estoque_minimo']) : 0;
    $estoque_maximo = !empty($_POST['estoque_maximo']) ? intval($_POST['estoque_maximo']) : null;
    $data_validade = !empty($_POST['data_validade']) ? $_POST['data_validade'] : null;

    // Validações básicas
    if (empty($produto) || $preco <= 0 || $quantidade_estoque < 0 || empty($categoria_id) || empty($status)) {
        $_SESSION['mensagem'] = "Por favor, preencha todos os campos obrigatórios corretamente.";
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: editar_produto.php?id=" . $id_produto);
        exit;
    }

    // Inicia transação
    $conn->begin_transaction();

    try {
        // 1. Atualiza os dados do produto
        $sql_produto = "UPDATE produtos SET 
                produto = ?,
                preco = ?,
                categoria_id = ?,
                fornecedor_id = ?,
                status = ?,
                descricao = ?,
                data_validade = ?
                WHERE id_produto = ?";

        $stmt_produto = $conn->prepare($sql_produto);

        if (!$stmt_produto) {
            throw new Exception("Erro ao preparar a atualização do produto: " . $conn->error);
        }

        $stmt_produto->bind_param(
            "sdiisssi",
            $produto,
            $preco,
            $categoria_id,
            $fornecedor_id,
            $status,
            $descricao,
            $data_validade,
            $id_produto
        );

        if (!$stmt_produto->execute()) {
            throw new Exception("Erro ao atualizar produto: " . $stmt_produto->error);
        }

        // 2. Verifica se houve alteração no estoque para registrar movimentação
        $sql_estoque_atual = "SELECT quantidade_estoque FROM estoque 
                             WHERE produto_id = ? 
                             ORDER BY data_movimentacao DESC LIMIT 1";
        $stmt_estoque_atual = $conn->prepare($sql_estoque_atual);
        $stmt_estoque_atual->bind_param("i", $id_produto);
        $stmt_estoque_atual->execute();
        $result_estoque = $stmt_estoque_atual->get_result();
        $estoque_anterior = $result_estoque->fetch_assoc();
        $quantidade_anterior = $estoque_anterior ? $estoque_anterior['quantidade_estoque'] : 0;

        if ($quantidade_anterior != $quantidade_estoque) {
            $tipo_movimentacao = ($quantidade_estoque > $quantidade_anterior) ? 'entrada' : 'saida';
            $diferenca = abs($quantidade_estoque - $quantidade_anterior);

            $sql_estoque = "INSERT INTO estoque (
                produto_id, 
                quantidade, 
                tipo_movimentacao, 
                quantidade_estoque,
                estoque_minimo,
                estoque_maximo,
                observacoes
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $observacoes = "Atualização manual via edição de produto";

            $stmt_estoque = $conn->prepare($sql_estoque);
            $stmt_estoque->bind_param(
                "iisiiis",
                $id_produto,
                $diferenca,
                $tipo_movimentacao,
                $quantidade_estoque,
                $estoque_minimo,
                $estoque_maximo,
                $observacoes
            );

            if (!$stmt_estoque->execute()) {
                throw new Exception("Erro ao registrar movimentação de estoque: " . $stmt_estoque->error);
            }
        }

        // 3. Atualiza fotos se foram enviadas
        // (Implementar lógica de upload de arquivos aqui se necessário)

        // Commit da transação
        $conn->commit();

        $_SESSION['mensagem'] = "Produto atualizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
        header("Location: cadastro_produto.php");
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao atualizar produto: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: editar_produto.php?id=" . $id_produto);
    } finally {
        if (isset($stmt_produto)) $stmt_produto->close();
        if (isset($stmt_estoque_atual)) $stmt_estoque_atual->close();
        if (isset($stmt_estoque)) $stmt_estoque->close();
        $conn->close();
    }
} else {
    // Se não for POST, redireciona
    header("Location: cadastro_produto.php");
}
