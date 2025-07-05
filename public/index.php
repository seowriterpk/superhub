
<?php
/**
 * VidSocial Front Controller
 * Entry point for all HTTP requests
 */

// Error reporting for production
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Enhanced security headers
header("Content-Security-Policy: frame-src https://www.eporner.com; default-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https:; img-src 'self' data: https: http:;");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Start session
session_start();

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Initialize application
use VidSocial\Core\Application;
use VidSocial\Core\Router;
use VidSocial\Core\Database;

try {
    // Create storage directories if they don't exist
    $storageDirs = [
        __DIR__ . '/../storage/cache',
        __DIR__ . '/../storage/logs', 
        __DIR__ . '/../storage/sitemaps',
        __DIR__ . '/../storage/temp'
    ];
    
    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Initialize database connection
    Database::getInstance();
    
    // Initialize router
    $router = new Router();
    
    // Register routes
    require_once __DIR__ . '/../app/routes.php';
    
    // Handle the request
    $router->handleRequest();
    
} catch (Exception $e) {
    // Log error
    error_log("VidSocial Error: " . $e->getMessage());
    
    // Show generic error page
    http_response_code(500);
    
    if (file_exists(__DIR__ . '/error.html')) {
        include __DIR__ . '/error.html';
    } else {
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Internal Server Error</h1><p>The application encountered an error. Please try again later.</p></body></html>';
    }
}
