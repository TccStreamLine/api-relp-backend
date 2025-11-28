<?php
// dashboard_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- CONEXÃO (Mantenha suas credenciais do Neon aqui) ---
$host = "ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech";
$db_name = "neondb";
$user = "neondb_owner";
$password = "npg_8E6ccUHIaxAs";
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db_name;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro de conexão']);
    exit();
}

// 1. TOTAL GERAL
$sqlTotal = "SELECT SUM(valor_total) as total_geral FROM vendas";
$stmtTotal = $pdo->query($sqlTotal);
$totalGeral = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_geral'] ?? 0;

// 2. GRÁFICO (Últimos 6 meses)
$sqlGrafico = "SELECT 
                TO_CHAR(data_venda, 'Mon') as mes, 
                SUM(valor_total) as total 
               FROM vendas 
               WHERE data_venda >= CURRENT_DATE - INTERVAL '6 months'
               GROUP BY TO_CHAR(data_venda, 'Mon'), DATE_TRUNC('month', data_venda)
               ORDER BY DATE_TRUNC('month', data_venda) ASC";
$stmtGrafico = $pdo->query($sqlGrafico);
$dadosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$valores = [];
foreach ($dadosGrafico as $dado) {
    $labels[] = $dado['mes'];
    $valores[] = (float)$dado['total'];
}
if (empty($labels)) { $labels = ["Sem dados"]; $valores = [0]; }

// --- NOVA PARTE: 3. ÚLTIMAS 5 VENDAS (Para a lista) ---
$sqlRecentes = "SELECT id, valor_total, data_venda, cliente_nome 
                FROM vendas 
                ORDER BY data_venda DESC 
                LIMIT 5";
$stmtRecentes = $pdo->query($sqlRecentes);
$vendasRecentes = $stmtRecentes->fetchAll(PDO::FETCH_ASSOC);


echo json_encode([
    'success' => true,
    'total_vendas' => number_format($totalGeral, 2, ',', '.'), // Formato BR
    'grafico' => [
        'labels' => $labels,
        'datasets' => [[ 'data' => $valores ]]
    ],
    'vendas_recentes' => $vendasRecentes // Enviando a lista nova
]);
?>