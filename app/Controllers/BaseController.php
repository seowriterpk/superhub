<?php
namespace VidSocial\Controllers;

use VidSocial\Core\Application;
use VidSocial\Models\Setting;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Base Controller
 * Common functionality for all controllers
 */
abstract class BaseController
{
    protected $app;
    protected $twig;
    protected $settings;
    
    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->loadSettings();
        $this->initializeTwig();
        $this->setSecurityHeaders();
        $this->initializeCsrfToken();
    }
    
    /**
     * Get setting value by key
     */
    protected function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
    
    private function loadSettings(): void
    {
        $settingModel = new Setting();
        $this->settings = $settingModel->getAll();
        
        // Default settings
        $defaults = [
            'site_name' => 'VidSocial',
            'site_description' => 'Premium adult video platform with the latest content',
            'contact_email' => 'contact@yourdomain.com',
            'dmca_email' => 'dmca@yourdomain.com',
            'videos_per_page' => '24',
            'admin_username' => 'admin',
            'admin_password' => password_hash('admin123', PASSWORD_DEFAULT)
        ];
        
        $this->settings = array_merge($defaults, $this->settings);
    }
    
    private function initializeTwig(): void
    {
        $loader = new FilesystemLoader(__DIR__ . '/../Views');
        $this->twig = new Environment($loader, [
            'cache' => __DIR__ . '/../../storage/cache/twig',
            'debug' => $_ENV['APP_DEBUG'] === 'true',
        ]);
        
        // Add global variables
        $this->twig->addGlobal('app', $this->app);
        $this->twig->addGlobal('site_settings', $this->settings ?? []);
    }
    
    private function setSecurityHeaders(): void
    {
        // Enhanced Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com https://www.googletagmanager.com https://www.google-analytics.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
               "img-src 'self' data: https: http:; " .
               "media-src 'self' https: http:; " .
               "frame-src 'self' https://www.eporner.com https://*.eporner.com; " .
               "connect-src 'self' https://www.eporner.com https://www.google-analytics.com; " .
               "font-src 'self' data:; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self';";
        
        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // HSTS header (only on HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    private function initializeCsrfToken(): void
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        $this->twig->addGlobal('csrf_token', $_SESSION['csrf_token']);
    }
    
    protected function validateCsrfToken(): bool
    {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    protected function render(string $template, array $data = []): void
    {
        // Add common template variables
        $data['page_title'] = $data['title'] ?? $this->settings['site_name'];
        $data['canonical_url'] = $data['canonical_url'] ?? $this->getCurrentUrl();
        $data['meta_description'] = $data['meta_description'] ?? $this->settings['site_description'];
        
        echo $this->twig->render($template, $data);
    }
    
    protected function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $protocol . '://' . $host . $uri;
    }
    
    protected function generateBreadcrumbs(array $items): array
    {
        $breadcrumbs = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        foreach ($items as $index => $item) {
            $breadcrumbs['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => isset($item['url']) ? $item['url'] : null
            ];
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Generate pagination data
     */
    protected function getPaginationData(int $total, int $perPage, int $currentPage): array
    {
        $totalPages = ceil($total / $perPage);
        
        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_page' => $currentPage - 1,
            'next_page' => $currentPage + 1,
            'total_items' => $total
        ];
    }
    
    /**
     * Render JSON response
     */
    protected function renderJson(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
