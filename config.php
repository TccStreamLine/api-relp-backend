<?php
// config.php
// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Dados de conexão (Ajuste se a senha tiver mudado!)
$host = 'ep-damp-sound-ado6c3f7-pooler.c-2.us-east-1.aws.neon.tech';
$db   = 'neondb';
$user = 'neondb_owner';
$pass = 'npg_8E6cCUhIaxAs'; // <--- VERIFIQUE SE ESSA É A SENHA CERTA MESMO!
$port = '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
} catch (PDOException $e) {
    // Retorna erro em JSON para o app não travar com "Erro de Conexão"
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'error' => 'Erro no Banco: ' . $e->getMessage()]);
    exit();
}
?>