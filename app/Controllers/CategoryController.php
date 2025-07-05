
<?php
namespace VidSocial\Controllers;

use VidSocial\Models\Category;
use VidSocial\Models\Video;
use VidSocial\Core\Database;

/**
 * Category Controller
 * Handles category listings and navigation
 */
class CategoryController extends BaseController
{
    private $categoryModel;
    private $videoModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new Category();
        $this->videoModel = new Video();
    }
    
    /**
     * Show all categories
     */
    public function index(): void
    {
        $categories = $this->categoryModel->getAllWithCounts();
        
        $this->render('categories.twig', [
            'page_title' => 'Video Categories - ' . $this->getSetting('site_name'),
            'meta_description' => 'Browse all video categories on ' . $this->getSetting('site_name'),
            'canonical_url' => $this->app->url('categories'),
            'categories' => $categories,
            'breadcrumb_items' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Categories', 'url' => '/categories']
            ]
        ]);
    }
    
    /**
     * Show videos in specific category
     */
    public function listing(string $slug, int $page = 1): void
    {
        $category = $this->categoryModel->getBySlug($slug);
        
        if (!$category) {
            $this->handle404();
            return;
        }
        
        $perPage = 24;
        $offset = ($page - 1) * $perPage;
        
        $videos = $this->videoModel->getByCategory($category['id'], $perPage, $offset);
        $totalVideos = $this->videoModel->countByCategory($category['id']);
        $totalPages = ceil($totalVideos / $perPage);
        
        // SEO optimization
        $pageTitle = $category['name'] . ' Videos';
        if ($page > 1) {
            $pageTitle .= ' - Page ' . $page;
        }
        $pageTitle .= ' - ' . $this->getSetting('site_name');
        
        $this->render('category-listing.twig', [
            'page_title' => $pageTitle,
            'meta_description' => 'Watch ' . $category['name'] . ' videos on ' . $this->getSetting('site_name') . '. Page ' . $page . ' of ' . $totalPages,
            'canonical_url' => $this->app->url('category/' . $slug . ($page > 1 ? '/' . $page : '')),
            'category' => $category,
            'videos' => $videos,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'prev_page' => $page - 1,
                'next_page' => $page + 1
            ],
            'breadcrumb_items' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Categories', 'url' => '/categories'],
                ['name' => $category['name'], 'url' => '/category/' . $slug]
            ]
        ]);
    }
    
    private function handle404(): void
    {
        http_response_code(404);
        $this->render('404.twig', [
            'page_title' => 'Category Not Found - ' . $this->getSetting('site_name'),
            'meta_description' => 'The requested category was not found.'
        ]);
    }
}
