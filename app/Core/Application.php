
<?php
namespace VidSocial\Core;

/**
 * Main Application Class
 * Handles application initialization and configuration
 */
class Application
{
    private static $instance = null;
    private $config = [];
    
    private function __construct()
    {
        $this->loadConfig();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig(): void
    {
        $this->config = [
            'app_name' => $_ENV['APP_NAME'] ?? 'VidSocial',
            'app_url' => $_ENV['APP_URL'] ?? 'https://localhost',
            'app_env' => $_ENV['APP_ENV'] ?? 'production',
            'app_debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
            'db_name' => $_ENV['DB_NAME'] ?? 'vidsocial',
            'db_user' => $_ENV['DB_USER'] ?? 'root',
            'db_pass' => $_ENV['DB_PASS'] ?? '',
            'eporner_api_url' => $_ENV['EPORNER_API_URL'] ?? 'https://www.eporner.com/api/v2',
            'eporner_api_key' => $_ENV['EPORNER_API_KEY'] ?? '',
            'cache_driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
            'cache_ttl' => (int)($_ENV['CACHE_TTL'] ?? 3600),
        ];
    }
    
    public function config(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    public function url(string $path = ''): string
    {
        return rtrim($this->config('app_url'), '/') . '/' . ltrim($path, '/');
    }
    
    public function isDebug(): bool
    {
        return $this->config('app_debug');
    }
}
