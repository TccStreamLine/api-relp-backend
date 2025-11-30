<?php
// dashboard_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'config.php'; 

// VERIFICAÇÃO DE SEGURANÇA: O ID do usuário é obrigatório
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID do usuário não fornecido']);
    exit();
}

$usuario_id = (int)$_GET['id']; // Pega o ID vindo da URL

try {
    // 1. TOTAL GERAL (Filtrado por usuário)
    // Adicionamos: WHERE usuario_id = :id
    $sqlTotal = "SELECT SUM(valor_total) as total_geral FROM vendas WHERE usuario_id = :id";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute([':id' => $usuario_id]);
    $totalGeral = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_geral'] ?? 0;

    // 2. GRÁFICO SEMANAL (Filtrado por usuário)
    // Adicionamos: AND usuario_id = :id
    $sqlGrafico = "SELECT 
                    TO_CHAR(data_venda, 'DD/MM') as dia, 
                    SUM(valor_total) as total 
                   FROM vendas 
                   WHERE data_venda >= CURRENT_DATE - INTERVAL '7 days'
                   AND usuario_id = :id
                   GROUP BY TO_CHAR(data_venda, 'DD/MM'), DATE(data_venda)
                   ORDER BY DATE(data_venda) ASC";
    
    $stmtGrafico = $pdo->prepare($sqlGrafico);
    $stmtGrafico->execute([':id' => $usuario_id]);
    $dadosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];
    
    foreach ($dadosGrafico as $dado) {
        $labels[] = $dado['dia'];
        $valores[] = (float)$dado['total'];
    }
    
    if (empty($labels)) { 
        $labels = ["Sem dados"]; 
        $valores = [0]; 
    }

    // 3. ÚLTIMAS VENDAS (Filtrado por usuário)
    // Adicionamos: WHERE usuario_id = :id
    $sqlRecentes = "SELECT id, valor_total, data_venda 
                    FROM vendas 
                    WHERE usuario_id = :id
                    ORDER BY data_venda DESC 
                    LIMIT 7";
    $stmtRecentes = $pdo->prepare($sqlRecentes);
    $stmtRecentes->execute([':id' => $usuario_id]);
    $vendasRecentes = $stmtRecentes->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total_vendas' => number_format($totalGeral, 2, ',', '.'),
        'grafico' => [
            'labels' => $labels,
            'datasets' => [[ 'data' => $valores ]]
        ],
        'vendas_recentes' => $vendasRecentes
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>