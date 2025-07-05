
<?php
namespace VidSocial\Middleware;

/**
 * Rate Limiting Middleware
 * Prevents API abuse and excessive requests
 */
class RateLimitMiddleware
{
    private $redis;
    private $useFile = true;
    
    public function __construct()
    {
        // Use file-based rate limiting for simplicity
        // In production, consider Redis for better performance
        $this->useFile = true;
    }
    
    public function handle(string $identifier, int $maxRequests = 100, int $windowSeconds = 3600): bool
    {
        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        
        if ($this->useFile) {
            return $this->handleFileBasedLimit($key, $maxRequests, $windowSeconds, $now);
        }
        
        return true;
    }
    
    private function handleFileBasedLimit(string $key, int $maxRequests, int $windowSeconds, int $now): bool
    {
        $cacheDir = __DIR__ . '/../../storage/cache/rate_limits';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $file = $cacheDir . '/' . $key;
        $data = [];
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true) ?: [];
        }
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });
        
        // Check if limit exceeded
        if (count($data) >= $maxRequests) {
            return false;
        }
        
        // Add current request
        $data[] = $now;
        
        // Save to file
        file_put_contents($file, json_encode($data));
        
        return true;
    }
    
    public static function checkApiLimit(): bool
    {
        $middleware = new self();
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // 100 requests per hour for API endpoints
        return $middleware->handle($clientIp, 100, 3600);
    }
    
    public static function checkEmbedLimit(): bool
    {
        $middleware = new self();
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // 500 embed requests per hour (more lenient for video viewing)
        return $middleware->handle('embed_' . $clientIp, 500, 3600);
    }
}
