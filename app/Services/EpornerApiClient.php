<?php
namespace VidSocial\Services;

use VidSocial\Core\Application;

/**
 * Eporner API Client - Enhanced with simplified approach
 * Maintains backward compatibility while using optimized methods
 */
class EpornerApiClient
{
    private $simplifiedClient;
    
    public function __construct()
    {
        $this->simplifiedClient = new SimplifiedEpornerClient();
    }
    
    /**
     * Search for videos - now using simplified client
     */
    public function search(array $params = []): array
    {
        $defaultParams = [
            'query' => 'all',
            'page' => 1,
            'per_page' => 50,
            'order' => 'latest',
        ];
        
        $params = array_merge($defaultParams, $params);
        
        $response = $this->simplifiedClient->getVideos(
            $params['page'],
            $params['query'],
            $params['per_page'],
            $params['order']
        );
        
        if ($response) {
            return [
                'videos' => $response['videos'],
                'total' => $response['total'],
                'pages' => $response['pages'],
                'current_page' => $response['current_page']
            ];
        }
        
        return ['videos' => [], 'total' => 0, 'pages' => 0];
    }
    
    /**
     * Get video by ID - now using simplified client
     */
    public function getById(string $id): ?array
    {
        return $this->simplifiedClient->getVideoById($id);
    }
    
    /**
     * Get removed video IDs - now using simplified client
     */
    public function getRemoved(int $page = 1): array
    {
        return $this->simplifiedClient->getRemovedVideos($page);
    }
    
    /**
     * Get videos by category - now using simplified client
     */
    public function getByCategory(string $category, array $params = []): array
    {
        $defaultParams = [
            'page' => 1,
            'per_page' => 50,
            'order' => 'latest',
        ];
        
        $params = array_merge($defaultParams, $params);
        
        $response = $this->simplifiedClient->getVideos(
            $params['page'],
            $category,
            $params['per_page'],
            $params['order']
        );
        
        if ($response) {
            return [
                'videos' => $response['videos'],
                'total' => $response['total'],
                'pages' => $response['pages'],
                'current_page' => $response['current_page']
            ];
        }
        
        return ['videos' => [], 'total' => 0, 'pages' => 0];
    }
    
    // Deprecated methods - kept for backward compatibility
    private function processSearchResults(array $data): array
    {
        // Redirect to simplified client processing
        return $data;
    }
    
    private function processVideoData(array $video): ?array
    {
        // This is now handled by SimplifiedEpornerClient
        return $video;
    }
    
    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return substr($slug, 0, 200); // Limit length
    }
    
    /**
     * Clean and format description
     */
    private function cleanDescription(string $keywords): string
    {
        $keywords = html_entity_decode($keywords, ENT_QUOTES, 'UTF-8');
        $keywords = preg_replace('/\s+/', ' ', $keywords);
        $keywords = trim($keywords);
        
        // Create a more natural description from keywords
        $words = explode(' ', $keywords);
        $words = array_slice($words, 0, 20); // Limit to 20 words
        
        return implode(' ', $words);
    }
    
    /**
     * Extract keywords as array
     */
    private function extractKeywords(string $keywords): array
    {
        $keywords = html_entity_decode($keywords, ENT_QUOTES, 'UTF-8');
        $words = preg_split('/[\s,]+/', $keywords);
        $words = array_filter($words, function($word) {
            return strlen($word) > 2 && strlen($word) < 30;
        });
        
        return array_unique(array_slice($words, 0, 20));
    }
    
    /**
     * Extract main category from keywords
     */
    private function extractMainCategory(string $keywords): string
    {
        $categories = [
            'amateur', 'anal', 'asian', 'babe', 'bbw', 'big-ass', 'big-tits',
            'blonde', 'blowjob', 'brunette', 'creampie', 'cumshot', 'deepthroat',
            'fetish', 'hardcore', 'interracial', 'latina', 'lesbian', 'mature',
            'milf', 'oral', 'pornstar', 'pov', 'redhead', 'teen', 'threesome'
        ];
        
        $keywords = strtolower($keywords);
        
        foreach ($categories as $category) {
            if (strpos($keywords, $category) !== false) {
                return $category;
            }
        }
        
        return 'other';
    }
    
    /**
     * Parse date string to MySQL datetime
     */
    private function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }
        
        try {
            $timestamp = strtotime($dateString);
            if ($timestamp === false) {
                return null;
            }
            
            return date('Y-m-d H:i:s', $timestamp);
        } catch (Exception $e) {
            return null;
        }
    }
}
