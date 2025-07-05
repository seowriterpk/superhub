
<?php
namespace VidSocial\Controllers;

use VidSocial\Models\Video;
use VidSocial\Models\Category;
use VidSocial\Models\Setting;
use VidSocial\Core\Database;

/**
 * Admin Controller
 * Handles admin panel functionality
 */
class AdminController extends BaseController
{
    private $videoModel;
    private $categoryModel;
    private $settingModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->videoModel = new Video();
        $this->categoryModel = new Category();
        $this->settingModel = new Setting();
        
        // Check admin authentication
        $this->checkAdminAuth();
    }
    
    /**
     * Admin dashboard
     */
    public function dashboard(): void
    {
        $stats = [
            'total_videos' => $this->videoModel->getTotalCount(),
            'total_categories' => $this->categoryModel->getTotalCount(),
            'videos_today' => $this->videoModel->getCountByDate(date('Y-m-d')),
            'top_categories' => $this->categoryModel->getTopCategories(10)
        ];
        
        $this->render('admin/dashboard.twig', [
            'page_title' => 'Admin Dashboard - ' . $this->getSetting('site_name'),
            'stats' => $stats
        ]);
    }
    
    /**
     * Admin login
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($this->validateLogin($username, $password)) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $username;
                header('Location: /admin');
                exit;
            } else {
                $error = 'Invalid credentials';
            }
        }
        
        $this->render('admin/login.twig', [
            'page_title' => 'Admin Login - ' . $this->getSetting('site_name'),
            'error' => $error ?? null
        ]);
    }
    
    /**
     * Admin logout
     */
    public function logout(): void
    {
        session_destroy();
        header('Location: /admin');
        exit;
    }
    
    /**
     * Videos management
     */
    public function videos(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        $videos = $this->videoModel->getAll($perPage, $offset);
        $totalVideos = $this->videoModel->getTotalCount();
        $totalPages = ceil($totalVideos / $perPage);
        
        $this->render('admin/videos.twig', [
            'page_title' => 'Video Management - Admin',
            'videos' => $videos,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ]);
    }
    
    /**
     * Settings management
     */
    public function settings(): void
    {
        $settings = $this->settingModel->getAll();
        
        $this->render('admin/settings.twig', [
            'page_title' => 'Settings - Admin',
            'settings' => $settings
        ]);
    }
    
    /**
     * Update settings
     */
    public function updateSettings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = $_POST['settings'] ?? [];
            
            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
            }
            
            $_SESSION['flash_message'] = 'Settings updated successfully';
            header('Location: /admin/settings');
            exit;
        }
    }
    
    /**
     * Check admin authentication
     */
    private function checkAdminAuth(): void
    {
        $publicRoutes = ['/admin/login'];
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if (!in_array($currentPath, $publicRoutes) && !isset($_SESSION['admin_logged_in'])) {
            header('Location: /admin/login');
            exit;
        }
    }
    
    /**
     * Validate admin login
     */
    private function validateLogin(string $username, string $password): bool
    {
        $adminUser = $this->getSetting('admin_username', 'admin');
        $adminPass = $this->getSetting('admin_password', password_hash('admin123', PASSWORD_DEFAULT));
        
        return $username === $adminUser && password_verify($password, $adminPass);
    }
}
