<?php
try {
    $host = $_ENV['DB_HOST'];
    $service = $_ENV['DB_SERVICE'];
    $server = $_ENV['DB_SERVER'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];
    $database = $_ENV['DB_NAME'];

    // Configuración de conexión para Informix con soporte UTF-8
    // Para Informix, el charset se configura en el DSN directamente
    $dsn = "informix:host=$host; service=$service;database=$database; server=$server; protocol=onsoctcp;EnableScrollableCursors=1;TRANSLITERATION=1";
    
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    error_log("Conexión PDO establecida para Informix con TRANSLITERATION");
    
    // Para Informix, intentamos configurar el modo de caracteres
    try {
        // Intentar habilitar soporte para caracteres extendidos
        $db->exec("SET ENVIRONMENT DELIMIDENT 'y'");
        error_log("DELIMIDENT configurado correctamente");
    } catch (PDOException $e) {
        error_log("Warning DELIMIDENT: " . $e->getMessage());
    }
    
    // Verificar la configuración actual de charset
    try {
        $stmt = $db->query("SELECT FIRST 1 * FROM systables WHERE tabname = 'categorias'");
        error_log("Consulta de prueba ejecutada correctamente");
    } catch (PDOException $e) {
        error_log("Error en consulta de prueba: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    echo json_encode([
        "detalle" => $e->getMessage(),
        "mensaje" => "Error de conexión bd",
        "codigo" => 5,
    ]);
    header('Location: /');
    exit;
}