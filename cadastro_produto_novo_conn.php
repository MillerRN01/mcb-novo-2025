<?php
include_once 'conexao.php';
require_once 'verifica_session_conn.php';

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados básicos do produto
    $produto = trim($_POST['produto'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $preco = $_POST['preco'] ?? 0;
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $fornecedor_id = intval($_POST['fornecedor_id'] ?? 0);
    $quantidade_estoque = intval($_POST['quantidade_estoque'] ?? 0);
    $estoque_minimo = isset($_POST['estoque_minimo']) ? intval($_POST['estoque_minimo']) : null;
    $estoque_maximo = isset($_POST['estoque_maximo']) ? intval($_POST['estoque_maximo']) : null;
    $data_validade = !empty($_POST['data_validade']) ? $_POST['data_validade'] : null;
    $status = 'ativo'; // Status padrão

    // Upload de arquivos
    $foto_produto = $_FILES['foto_produto'] ?? null;
    $foto_comprovante = $_FILES['foto_comprovante'] ?? null;

    // Diretórios de upload separados
    $uploadDirFoto = 'assets/uploads/produto/foto/';
    $uploadDirComprovante = 'assets/uploads/produto/comprovante/';

    // Criar diretórios se não existirem
    if (!file_exists($uploadDirFoto)) {
        mkdir($uploadDirFoto, 0777, true);
    }
    if (!file_exists($uploadDirComprovante)) {
        mkdir($uploadDirComprovante, 0777, true);
    }

    // Função para fazer upload de arquivos
    function uploadFile($file, $uploadDir) {
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            // Verifica se o arquivo é uma imagem
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                return ['success' => false, 'message' => 'Tipo de arquivo não permitido. Apenas imagens JPEG, PNG ou GIF são aceitas.'];
            }

            // Verifica tamanho máximo (2MB)
            if ($file['size'] > 2097152) {
                return ['success' => false, 'message' => 'Arquivo muito grande. Tamanho máximo permitido: 2MB.'];
            }

            // Gera nome único para o arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $targetFilePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                return ['success' => true, 'path' => $targetFilePath];
            }
        }
        return ['success' => false, 'message' => 'Erro ao fazer upload do arquivo.'];
    }

    // Upload da foto do produto
    $fotoProdutoResult = $foto_produto ? uploadFile($foto_produto, $uploadDirFoto) : ['success' => true, 'path' => null];
    if (!$fotoProdutoResult['success']) {
        $_SESSION['mensagem'] = $fotoProdutoResult['message'];
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: cadastro_produto_novo.php');
        exit();
    }

    // Upload do comprovante
    $fotoComprovanteResult = $foto_comprovante ? uploadFile($foto_comprovante, $uploadDirComprovante) : ['success' => true, 'path' => null];
    if (!$fotoComprovanteResult['success']) {
        // Remove a foto do produto se o comprovante falhar
        if ($fotoProdutoResult['path'] && file_exists($fotoProdutoResult['path'])) {
            unlink($fotoProdutoResult['path']);
        }
        $_SESSION['mensagem'] = $fotoComprovanteResult['message'];
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: cadastro_produto_novo.php');
        exit();
    }

    // Validação dos dados
    $errors = [];
    if (empty($produto)) $errors[] = "Nome do produto é obrigatório";
    if ($preco <= 0) $errors[] = "Preço deve ser maior que zero";
    if ($quantidade_estoque < 0) $errors[] = "Quantidade em estoque não pode ser negativa";
    if ($categoria_id <= 0) $errors[] = "Categoria é obrigatória";
    if ($fornecedor_id <= 0) $errors[] = "Fornecedor é obrigatório";

    if (!empty($errors)) {
        // Remove arquivos enviados se houver erro de validação
        if ($fotoProdutoResult['path'] && file_exists($fotoProdutoResult['path'])) {
            unlink($fotoProdutoResult['path']);
        }
        if ($fotoComprovanteResult['path'] && file_exists($fotoComprovanteResult['path'])) {
            unlink($fotoComprovanteResult['path']);
        }
        
        $_SESSION['mensagem'] = implode("<br>", $errors);
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: cadastro_produto_novo.php');
        exit();
    }

    // Formatação do preço
    $preco = str_replace(['R$', ' ', '.'], '', $preco);
    $preco = str_replace(',', '.', $preco);
    $preco = floatval($preco);

    // Inserção no banco de dados
    try {
        $conn->begin_transaction();

        $sql = "INSERT INTO produtos (
                produto, descricao, preco, categoria_id, fornecedor_id, 
                quantidade_estoque, estoque_minimo, estoque_maximo, 
                data_validade, foto_produto, foto_comprovante, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }

        // Obtém apenas os nomes dos arquivos para armazenar no banco
        $fotoProdutoPath = $fotoProdutoResult['path'] ? basename($fotoProdutoResult['path']) : null;
        $fotoComprovantePath = $fotoComprovanteResult['path'] ? basename($fotoComprovanteResult['path']) : null;

        $stmt->bind_param(
            "ssdiiiisssss",
            $produto,
            $descricao,
            $preco,
            $categoria_id,
            $fornecedor_id,
            $quantidade_estoque,
            $estoque_minimo,
            $estoque_maximo,
            $data_validade,
            $fotoProdutoPath,
            $fotoComprovantePath,
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception("Erro ao cadastrar o produto: " . $stmt->error);
        }

        $conn->commit();

        $_SESSION['mensagem'] = 'Produto cadastrado com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
        header('Location: cadastro_produto.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        
        // Remove arquivos em caso de erro
        if ($fotoProdutoResult['path'] && file_exists($fotoProdutoResult['path'])) {
            unlink($fotoProdutoResult['path']);
        }
        if ($fotoComprovanteResult['path'] && file_exists($fotoComprovanteResult['path'])) {
            unlink($fotoComprovanteResult['path']);
        }

        $_SESSION['mensagem'] = 'Erro ao cadastrar produto: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: cadastro_produto_novo.php');
        exit();
    }
}