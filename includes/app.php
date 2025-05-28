<?php

session_start();

use Dotenv\Dotenv;
use Model\ActiveRecord;
use Middleware\SecurityHeadersMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Error reporting
$debugMode = $_ENV['DEBUG_MODE'] ?? false;
ini_set('display_errors', $debugMode);
ini_set('display_startup_errors', $debugMode);
error_reporting($debugMode ? E_ALL : 0);

// Security headers
$securityMiddleware = new SecurityHeadersMiddleware();
$securityMiddleware->handle([]);

// CSRF Token
if (!isset($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

// Database
require 'database.php';
require 'funciones.php';

ActiveRecord::setDB($db);
