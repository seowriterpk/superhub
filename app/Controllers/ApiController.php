
<?php
namespace VidSocial\Controllers;

use VidSocial\Models\Video;
use VidSocial\Services\EpornerApiClient;
use VidSocial\Middleware\RateLimitMiddleware;

/**
 * API Controller
 * Handles JSON API endpoints
 */
class ApiController extends BaseController
{
    private $videoModel;
    private $apiClient;
    
    public function __construct()
    {
        parent::__construct();
        $this->videoModel = new Video();
        $this->apiClient = new EpornerApiClient();
    }
    
    /**
     * Videos JSON endpoint
     */
    public function videosJson(): void
    {
        // Rate limiting
        if (!RateLimitMiddleware::checkApiLimit()) {
            $this->jsonResponse(['error' => 'Rate limit exceeded'], 429);
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 20), 100);
        $category = $_GET['category'] ?? null;
        
        $offset = ($page - 1) * $perPage;
        
        if ($category) {
            $videos = $this->videoModel->getByCategory($category, $perPage, $offset);
            $total = $this->videoModel->countByCategory($category);
        } else {
            $videos = $this->videoModel->getAll($perPage, $offset);
            $total = $this->videoModel->getTotalCount();
        }
        
        $this->jsonResponse([
            'videos' => $videos,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => ceil($total / $perPage)
            ]
        ]);
    }
    
    /**
     * Search JSON endpoint
     */
    public function searchJson(): void
    {
        // Rate limiting
        if (!RateLimitMiddleware::checkApiLimit()) {
            $this->jsonResponse(['error' => 'Rate limit exceeded'], 429);
            return;
        }
        
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 20), 100);
        
        if (empty($query)) {
            $this->jsonResponse(['error' => 'Query parameter required'], 400);
            return;
        }
        
        $offset = ($page - 1) * $perPage;
        $results = $this->videoModel->search($query, $perPage, $offset);
        $total = $this->videoModel->getSearchCount($query);
        
        $this->jsonResponse([
            'results' => $results,
            'query' => $query,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => ceil($total / $perPage)
            ]
        ]);
    }
    
    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
