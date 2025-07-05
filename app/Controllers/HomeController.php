
<?php
namespace VidSocial\Controllers;

use VidSocial\Models\Video;
use VidSocial\Models\Category;

/**
 * Home Controller
 * Handles home page display
 */
class HomeController extends BaseController
{
    public function index(): void
    {
        $videoModel = new Video();
        $categoryModel = new Category();
        
        // Get trending and recent videos
        $trendingVideos = $videoModel->getTrending(12);
        $recentVideos = $videoModel->getRecent(12);
        
        // Get popular categories
        $categories = $categoryModel->getPopular(8);
        
        // Generate structured data for homepage
        $structuredData = $this->generateHomePageStructuredData($trendingVideos);
        
        $this->render('home.twig', [
            'title' => $this->settings['site_name'] . ' - Premium Adult Videos',
            'meta_description' => $this->settings['site_description'],
            'trending_videos' => $trendingVideos,
            'recent_videos' => $recentVideos,
            'categories' => $categories,
            'structured_data' => $structuredData,
            'show_age_disclaimer' => true,
        ]);
    }
    
    private function generateHomePageStructuredData(array $videos): array
    {
        $videoList = [];
        
        foreach ($videos as $video) {
            $videoList[] = [
                '@type' => 'VideoObject',
                'name' => $video['title'],
                'description' => $video['description'] ?: substr($video['title'], 0, 100),
                'thumbnailUrl' => $video['thumb_url'],
                'uploadDate' => date('c', strtotime($video['created_at'])),
                'duration' => 'PT' . $video['duration'] . 'S',
                'contentUrl' => $this->app->url("video/{$video['slug']}-{$video['eporner_id']}"),
                'embedUrl' => $video['embed_url'],
                'interactionStatistic' => [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'http://schema.org/WatchAction',
                    'userInteractionCount' => $video['views']
                ],
                'isFamilyFriendly' => 'false',
                'contentRating' => 'adult',
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Trending Adult Videos',
            'itemListElement' => array_map(function($video, $index) {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => $video
                ];
            }, $videoList, array_keys($videoList))
        ];
    }
    
    /**
     * Trending videos page
     */
    public function trending(int $page = 1): void
    {
        $videoModel = new Video();
        $perPage = (int)($this->settings['videos_per_page'] ?? 24);
        $offset = ($page - 1) * $perPage;
        
        $videos = $videoModel->getTrending($perPage, $offset);
        $totalVideos = $videoModel->getTotalCount();
        $totalPages = ceil($totalVideos / $perPage);
        
        $this->render('trending.twig', [
            'page_title' => 'Trending Videos' . ($page > 1 ? " - Page {$page}" : '') . ' - ' . $this->getSetting('site_name'),
            'meta_description' => 'Most popular trending videos on ' . $this->getSetting('site_name'),
            'videos' => $videos,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'prev_page' => $page - 1,
                'next_page' => $page + 1
            ],
            'canonical_url' => $this->app->url('trending' . ($page > 1 ? '/' . $page : ''))
        ]);
    }
}
