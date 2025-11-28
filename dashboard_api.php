<?php
// dashboard_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- CONEXÃO COM NEON (Copie os dados exatos do seu login.php) ---
$host = "ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech";
$db_name = "neondb";
$user = "neondb_owner";
$password = "npg_8E6cCUhIaxAs"; // <--- Verifique se é a senha atualizada
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db_name;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro de conexão']);
    exit();
}

// --- 1. BUSCAR TOTAL GERAL DE VENDAS ---
// Ajuste 'vendas' e 'valor_total' se o nome da sua tabela/coluna for diferente
$sqlTotal = "SELECT SUM(valor_total) as total_geral FROM vendas";
$stmtTotal = $pdo->query($sqlTotal);
$totalGeral = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_geral'] ?? 0;

// --- 2. BUSCAR DADOS PARA O GRÁFICO (Últimos 6 meses) ---
// PostgreSQL usa TO_CHAR para formatar datas
$sqlGrafico = "SELECT 
                TO_CHAR(data_venda, 'Mon') as mes, 
                SUM(valor_total) as total 
               FROM vendas 
               WHERE data_venda >= CURRENT_DATE - INTERVAL '6 months'
               GROUP BY TO_CHAR(data_venda, 'Mon'), DATE_TRUNC('month', data_venda)
               ORDER BY DATE_TRUNC('month', data_venda) ASC";

$stmtGrafico = $pdo->query($sqlGrafico);
$dadosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

// Preparar arrays para o App
$labels = [];
$valores = [];

foreach ($dadosGrafico as $dado) {
    $labels[] = $dado['mes']; // Ex: Jan, Feb
    $valores[] = (float)$dado['total'];
}

// Se não houver dados, envia dados zerados para não quebrar o gráfico
if (empty($labels)) {
    $labels = ["Sem dados"];
    $valores = [0];
}

echo json_encode([
    'success' => true,
    'total_vendas' => number_format($totalGeral, 2, '.', ''),
    'grafico' => [
        'labels' => $labels,
        'datasets' => [[ 'data' => $valores ]]
    ]
]);
?>