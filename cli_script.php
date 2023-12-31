<?php
use Dotenv\Dotenv;

require 'vendor/autoload.php';
require 'Controllers/DomaineController.class.php';
require 'Models/Domaine.class.php';

// Load the environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get the environment variables
$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPass = $_ENV['DB_PASS'];
$fileUrl = $_ENV['FILE_URL'];
$logFile = $_ENV['LOG_FILE'];

// Create the controller
$controller = new DomaineController($dbHost, $dbName, $dbUser, $dbPass, $fileUrl, $logFile);
// Check if the desired parameter is passed via command-line argument
if (isset($argv[1])) {
    $parameter = $argv[1];
    $controller->processFileContent($parameter);
} else {
    $controller->processFileContent();
}

// exemple of command: php cli_script.php 20230601