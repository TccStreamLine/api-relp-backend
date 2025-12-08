<?php
// detalhes_venda.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID da venda não fornecido']);
    exit();
}

$venda_id = (int)$_GET['id'];

try {
    // 1. Busca dados gerais da venda
    $sqlVenda = "SELECT id, descricao, data_venda, valor_total FROM vendas WHERE id = :id";
    $stmtVenda = $pdo->prepare($sqlVenda);
    $stmtVenda->execute([':id' => $venda_id]);
    $dadosVenda = $stmtVenda->fetch(PDO::FETCH_ASSOC);

    if (!$dadosVenda) {
        echo json_encode(['success' => false, 'error' => 'Venda não encontrada']);
        exit();
    }

    // 2. Busca os produtos dessa venda + dados de estoque
    // JOIN entre venda_itens e produtos
    $sqlItens = "SELECT 
                    p.nome,
                    vi.quantidade as qtd_vendida,
                    vi.valor_unitario,
                    p.quantidade_estoque,
                    p.quantidade_minima
                 FROM venda_itens vi
                 JOIN produtos p ON vi.produto_id = p.id
                 WHERE vi.venda_id = :id";
                 
    $stmtItens = $pdo->prepare($sqlItens);
    $stmtItens->execute([':id' => $venda_id]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'venda' => $dadosVenda,
        'produtos' => $itens
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>