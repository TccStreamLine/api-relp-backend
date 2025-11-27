<?php
// Cabeçalhos para permitir acesso do App (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// --- CONFIGURAÇÃO DA CONEXÃO COM NEON (POSTGRESQL) ---
// Dados extraídos da sua imagem 'connection string'
$host = "ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech";
$db_name = "neondb";
$user = "neondb_owner";
$password = "npg_8E6ccUHIaxAs";
$port = "5432"; 

// String de Conexão (DSN) para PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$db_name;sslmode=require";

try {
    // Cria a conexão usando PDO
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lança erros como exceções
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Traz dados como array associativo
    ]);
} catch (PDOException $e) {
    // Se falhar a conexão
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()]);
    exit();
}

// --- RECEBIMENTO DOS DADOS DO APP ---
$data = json_decode(file_get_contents('php://input'), true);

// Validação básica
if (!isset($data['email']) || !isset($data['senha'])) {
    echo json_encode(['success' => false, 'message' => 'Email ou senha não informados']);
    exit();
}

$email = $data['email']; 
$senha_digitada = $data['senha'];

try {
    // --- CONSULTA AO BANCO (MUDANÇA PARA PDO) ---
    // Prepara a query SQL (segurança contra SQL Injection)
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    
    // Vincula o parâmetro :email
    $stmt->bindParam(':email', $email);
    
    // Executa
    $stmt->execute();
    
    // Busca o resultado (apenas 1 linha)
    $user = $stmt->fetch();

    if ($user) {
        // Usuário encontrado, agora verificamos a senha
        // OBS: Se no banco a senha estiver em texto puro (não recomendado), mude para: if ($senha_digitada == $user['senha'])
        
        if (password_verify($senha_digitada, $user['senha'])) {
            // Sucesso! Removemos a senha do array antes de enviar de volta por segurança
            unset($user['senha']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login bem-sucedido!', 
                'usuario' => $user
            ]);
        } else {
            // Senha incorreta
            echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos']);
        }
    } else {
        // Email não existe no banco
        echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao processar login: ' . $e->getMessage()]);
}
?>