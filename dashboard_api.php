<?php
// dashboard_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- CONEXÃO COM O BANCO NEON ---
$host = "ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech";
$db_name = "neondb";
$user = "neondb_owner";
$password = "npg_8E6ccUHIaxAs";
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db_name;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $e->getMessage()]);
    exit();
}

try {
    // 1. TOTAL GERAL (Soma de todas as vendas da história)
    $sqlTotal = "SELECT SUM(valor_total) as total_geral FROM vendas";
    $stmtTotal = $pdo->query($sqlTotal);
    $totalGeral = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_geral'] ?? 0;

    // 2. GRÁFICO SEMANAL (Últimas 8 semanas)
    // DATE_TRUNC('week', data_venda) -> Pega o primeiro dia da semana daquela venda
    // TO_CHAR(..., 'DD/MM') -> Formata para mostrar "27/11" no gráfico
    $sqlGrafico = "SELECT 
                    TO_CHAR(DATE_TRUNC('week', data_venda), 'DD/MM') as semana, 
                    SUM(valor_total) as total 
                   FROM vendas 
                   WHERE data_venda >= CURRENT_DATE - INTERVAL '8 weeks'
                   GROUP BY DATE_TRUNC('week', data_venda)
                   ORDER BY DATE_TRUNC('week', data_venda) ASC";
    
    $stmtGrafico = $pdo->query($sqlGrafico);
    $dadosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];
    
    foreach ($dadosGrafico as $dado) {
        $labels[] = $dado['semana']; // Ex: 20/11, 27/11
        $valores[] = (float)$dado['total'];
    }
    
    // Se não tiver dados, evita que o gráfico quebre
    if (empty($labels)) { 
        $labels = ["Sem dados"]; 
        $valores = [0]; 
    }

    // 3. ÚLTIMAS 5 VENDAS (Lista detalhada)
    // Trazemos id, valor_total e data_venda (nomes corretos do seu banco)
    $sqlRecentes = "SELECT id, valor_total, data_venda 
                    FROM vendas 
                    ORDER BY data_venda DESC 
                    LIMIT 5";
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