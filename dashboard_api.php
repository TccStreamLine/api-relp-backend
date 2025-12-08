<?php
// dashboard_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'config.php'; 

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID do usuário não fornecido']);
    exit();
}

$usuario_id = (int)$_GET['id'];

try {
    // 1. TOTAL GERAL (Continua pegando de todo o histórico ou quer só da semana também?)
    // Vou manter histórico total para o faturamento, que costuma ser o padrão.
    $sqlTotal = "SELECT SUM(valor_total) as total_geral FROM vendas WHERE usuario_id = :id";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute([':id' => $usuario_id]);
    $totalGeral = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_geral'] ?? 0;

    // 2. GRÁFICO SEMANAL (Últimos 7 dias)
    $sqlGrafico = "SELECT 
                    TO_CHAR(data_venda, 'DD/MM') as dia, 
                    SUM(valor_total) as total 
                   FROM vendas 
                   WHERE usuario_id = :id
                   AND data_venda >= CURRENT_DATE - INTERVAL '7 days'
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

    // 3. ÚLTIMAS VENDAS (AGORA FILTRADO: Apenas últimos 7 dias)
    // MUDANÇA AQUI: Adicionei o filtro de data igual ao do gráfico
    $sqlRecentes = "SELECT id, valor_total, data_venda 
                    FROM vendas 
                    WHERE usuario_id = :id
                    AND data_venda >= CURRENT_DATE - INTERVAL '7 days'
                    ORDER BY data_venda DESC"; 
                    // Removi o LIMIT 7, pois agora o limite é o tempo (uma semana)
    
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