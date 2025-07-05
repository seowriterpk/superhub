
<?php
namespace VidSocial\Controllers;

use VidSocial\Models\Video;

/**
 * Search Controller
 * Handles video search functionality
 */
class SearchController extends BaseController
{
    public function results(string $query, int $page = 1): void
    {
        $videoModel = new Video();
        $perPage = (int)($this->settings['videos_per_page'] ?? 24);
        $offset = ($page - 1) * $perPage;
        
        // Sanitize search query
        $query = trim(strip_tags($query));
        if (empty($query)) {
            $this->redirect('/');
            return;
        }
        
        // Get search results
        $videos = $videoModel->search($query, $perPage, $offset);
        $totalVideos = $videoModel->getSearchCount($query);
        
        // Generate pagination data
        $paginationData = $this->getPaginationData($totalVideos, $perPage, $page);
        
        // Handle AJAX requests for infinite scroll
        if (isset($_GET['ajax'])) {
            $videoHtml = '';
            foreach ($videos as $video) {
                $videoHtml .= $this->renderVideoCard($video);
            }
            
            $this->renderJson([
                'html' => $videoHtml,
                'pagination' => $paginationData
            ]);
            return;
        }
        
        // Generate structured data
        $structuredData = $this->generateSearchStructuredData($videos, $query);
        
        // SEO optimization
        $title = "Search Results for \"{$query}\" - Page {$page}";
        $description = "Found {$totalVideos} videos matching \"{$query}\". Browse our extensive collection of adult content.";
        
        $this->render('search.twig', [
            'title' => $title,
            'meta_description' => $description,
            'search_query' => $query,
            'videos' => $videos,
            'pagination' => $paginationData,
            'total_results' => $totalVideos,
            'structured_data' => $structuredData,
            'breadcrumb_items' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => "Search: {$query}"]
            ],
            'canonical_url' => $this->app->url("search/{$query}" . ($page > 1 ? "/{$page}" : '')),
            'prev_url' => $page > 1 ? $this->app->url("search/{$query}" . ($page > 2 ? "/" . ($page - 1) : '')) : null,
            'next_url' => $paginationData['has_next'] ? $this->app->url("search/{$query}/" . ($page + 1)) : null,
        ]);
    }
    
    private function generateSearchStructuredData(array $videos, string $query): array
    {
        $videoList = [];
        
        foreach ($videos as $index => $video) {
            $videoList[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'VideoObject',
                    'name' => $video['title'],
                    'description' => $video['description'] ?: substr($video['title'], 0, 100),
                    'thumbnailUrl' => $video['thumb_url'],
                    'url' => $this->app->url("video/{$video['slug']}-{$video['eporner_id']}"),
                    'duration' => 'PT' . $video['duration'] . 'S',
                    'uploadDate' => date('c', strtotime($video['created_at'])),
                ]
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => "Search Results for \"{$query}\"",
            'numberOfItems' => count($videos),
            'itemListElement' => $videoList
        ];
    }
    
    private function renderVideoCard(array $video): string
    {
        return $this->twig->render('components/video-card.twig', ['video' => $video]);
    }
}
