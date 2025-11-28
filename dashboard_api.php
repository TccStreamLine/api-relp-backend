<?php
// dashboard_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- CONEXÃO (Mantenha suas credenciais do Neon) ---
$host = "ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech";
$db_name = "neondb";
$user = "neondb_owner";
$password = "npg_8E6ccUHIaxAs"; // <--- Verifique se é a senha atual
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db_name;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $e->getMessage()]);
    exit();
}

try {
    // 1. TOTAL GERAL
    // Correção: coluna 'valor' em vez de 'valor_total'
    $sqlTotal = "SELECT SUM(valor) as total_geral FROM vendas";
    $stmtTotal = $pdo->query($sqlTotal);
    $totalGeral = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_geral'] ?? 0;

    // 2. GRÁFICO (Últimos 6 meses)
    // Correção: coluna 'data' em vez de 'data_venda' e 'valor' em vez de 'valor_total'
    // Adicionei ::date para garantir que o Postgres entenda como data
    $sqlGrafico = "SELECT 
                    TO_CHAR(data::date, 'Mon') as mes, 
                    SUM(valor) as total 
                   FROM vendas 
                   WHERE data::date >= CURRENT_DATE - INTERVAL '6 months'
                   GROUP BY TO_CHAR(data::date, 'Mon'), DATE_TRUNC('month', data::date)
                   ORDER BY DATE_TRUNC('month', data::date) ASC";
    
    $stmtGrafico = $pdo->query($sqlGrafico);
    $dadosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];
    foreach ($dadosGrafico as $dado) {
        $labels[] = $dado['mes'];
        $valores[] = (float)$dado['total'];
    }
    
    // Dados fictícios para não ficar vazio se não tiver vendas (Opcional, remova se preferir zeros)
    if (empty($labels)) { 
        $labels = ["Sem dados"]; 
        $valores = [0]; 
    }

    // 3. ÚLTIMAS 5 VENDAS (Para a lista)
    // Correção: nomes das colunas ajustados
    $sqlRecentes = "SELECT id_venda as id, valor, data, cliente 
                    FROM vendas 
                    ORDER BY data DESC 
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