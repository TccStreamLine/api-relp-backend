<?php
// dashboard_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- CONEXÃO NEON ---
$host = "ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech";
$db_name = "neondb";
$user = "neondb_owner";
$password = "npg_8E6ccUHIaxAs";
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db_name;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão']);
    exit();
}

try {
    // 1. TOTAL GERAL (Continua sendo a soma total para referência)
    $sqlTotal = "SELECT SUM(valor_total) as total_geral FROM vendas";
    $stmtTotal = $pdo->query($sqlTotal);
    $totalGeral = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_geral'] ?? 0;

    // 2. GRÁFICO DIÁRIO (Últimos 7 Dias)
    // Mudamos para pegar dia a dia (DD/MM)
    $sqlGrafico = "SELECT 
                    TO_CHAR(data_venda, 'DD/MM') as dia, 
                    SUM(valor_total) as total 
                   FROM vendas 
                   WHERE data_venda >= CURRENT_DATE - INTERVAL '7 days'
                   GROUP BY TO_CHAR(data_venda, 'DD/MM'), DATE(data_venda)
                   ORDER BY DATE(data_venda) ASC";
    
    $stmtGrafico = $pdo->query($sqlGrafico);
    $dadosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];
    
    foreach ($dadosGrafico as $dado) {
        $labels[] = $dado['dia']; // Ex: 27/11, 28/11
        $valores[] = (float)$dado['total'];
    }
    
    // Se não tiver vendas na semana, mostra vazio
    if (empty($labels)) { 
        $labels = ["Sem vendas"]; 
        $valores = [0]; 
    }

    // 3. ÚLTIMAS VENDAS (Lista detalhada)
    $sqlRecentes = "SELECT id, valor_total, data_venda 
                    FROM vendas 
                    ORDER BY data_venda DESC 
                    LIMIT 7"; // Mostra as últimas 7 transações
    $stmtRecentes = $pdo->query($sqlRecentes);
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

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro SQL: ' . $e->getMessage()]);
}
?>