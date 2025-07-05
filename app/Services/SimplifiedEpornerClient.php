<?php
namespace VidSocial\Services;

use VidSocial\Core\Application;

/**
 * Simplified Eporner API Client
 * Based on official API examples for better performance and reliability
 */
class SimplifiedEpornerClient
{
    private $baseUrl;
    private $apiKey;
    private $cache = [];
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->baseUrl = $app->config('eporner_api_url');
        $this->apiKey = $app->config('eporner_api_key');
    }
    
    /**
     * Generic API call function (based on official examples)
     */
    private function epornerAPICall($apiUrl, $params): ?string
    {
        $url = $apiUrl . '?' . http_build_query($params);
        
        // Cache key for repeated requests
        $cacheKey = md5($url);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'VidSocial/2.0 (+' . Application::getInstance()->url() . ')');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $results = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Eporner API cURL error: " . $error);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("Eporner API HTTP error: " . $httpCode);
            return null;
        }
        
        // Cache successful responses
        $this->cache[$cacheKey] = $results;
        return $results;
    }
    
    /**
     * Get videos with optimized parameters
     */
    public function getVideos($page = 1, $query = 'all', $perPage = 1000, $order = 'latest', $thumbsize = 'big'): ?array
    {
        $apiUrl = $this->baseUrl . '/video/search/';
        $params = [
            'per_page' => $perPage,
            'order' => $order,
            'thumbsize' => $thumbsize,
            'query' => $query,
            'page' => $page,
            'format' => 'json'
        ];
        
        $response = $this->epornerAPICall($apiUrl, $params);
        if ($response) {
            $json = json_decode($response, true);
            if ($json && isset($json['videos'])) {
                return $this->processVideosResponse($json);
            }
        }
        
        return null;
    }
    
    /**
     * Get video by ID
     */
    public function getVideoById($id, $thumbsize = 'big'): ?array
    {
        $apiUrl = $this->baseUrl . '/video/id/';
        $params = [
            'id' => $id,
            'thumbsize' => $thumbsize,
            'format' => 'json'
        ];
        
        $response = $this->epornerAPICall($apiUrl, $params);
        if ($response) {
            $json = json_decode($response, true);
            if ($json) {
                return $this->processVideoData($json);
            }
        }
        
        return null;
    }
    
    /**
     * Get removed videos
     */
    public function getRemovedVideos($page = 1): array
    {
        $apiUrl = $this->baseUrl . '/video/removed/';
        $params = [
            'page' => $page,
            'per_page' => 1000,
            'format' => 'json'
        ];
        
        $response = $this->epornerAPICall($apiUrl, $params);
        if ($response) {
            $json = json_decode($response, true);
            return $json['removed_videos'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Process API response for multiple videos
     */
    private function processVideosResponse(array $response): array
    {
        $processedVideos = [];
        
        if (!isset($response['videos']) || !is_array($response['videos'])) {
            return [
                'videos' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => 1
            ];
        }
        
        foreach ($response['videos'] as $video) {
            $processed = $this->processVideoData($video);
            if ($processed) {
                $processedVideos[] = $processed;
            }
        }
        
        return [
            'videos' => $processedVideos,
            'total' => $response['total_videos'] ?? count($processedVideos),
            'pages' => $response['total_pages'] ?? 1,
            'current_page' => $response['page'] ?? 1
        ];
    }
    
    /**
     * Process individual video data (simplified from examples)
     */
    private function processVideoData(array $video): ?array
    {
        if (empty($video['id'])) {
            return null;
        }
        
        return [
            'eporner_id' => $video['id'],
            'title' => html_entity_decode($video['title'] ?? '', ENT_QUOTES, 'UTF-8'),
            'slug' => $this->generateSlug($video['title'] ?? ''),
            'description' => $this->cleanDescription($video['keywords'] ?? ''),
            'duration' => (int)($video['length_sec'] ?? 0),
            'views' => (int)($video['views'] ?? 0),
            'rating' => (float)($video['rate'] ?? 0),
            'thumb_url' => $this->getBestThumbnail($video),
            'embed_url' => $video['embed'] ?? '',
            'video_url' => $video['src']['mp4'] ?? '',
            'hls_url' => $video['src']['hls'] ?? '',
            'added_date' => $this->parseDate($video['added'] ?? null),
            'keywords' => $this->extractKeywords($video['keywords'] ?? ''),
            'category' => $this->extractMainCategory($video['keywords'] ?? ''),
        ];
    }
    
    /**
     * Get best available thumbnail
     */
    private function getBestThumbnail(array $video): string
    {
        // Check for specific thumb sizes
        if (isset($video['thumb'])) {
            return $video['thumb'];
        }
        
        if (isset($video['default_thumb']['src'])) {
            return $video['default_thumb']['src'];
        }
        
        // Fallback to thumbs array
        if (isset($video['thumbs']) && is_array($video['thumbs'])) {
            foreach ($video['thumbs'] as $thumb) {
                if (isset($thumb['src'])) {
                    return $thumb['src'];
                }
            }
        }
        
        return '';
    }
    
    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return substr($slug, 0, 200);
    }
    
    private function cleanDescription(string $keywords): string
    {
        $keywords = html_entity_decode($keywords, ENT_QUOTES, 'UTF-8');
        $keywords = preg_replace('/\s+/', ' ', $keywords);
        $keywords = trim($keywords);
        $words = explode(' ', $keywords);
        $words = array_slice($words, 0, 20);
        return implode(' ', $words);
    }
    
    private function extractKeywords(string $keywords): array
    {
        $keywords = html_entity_decode($keywords, ENT_QUOTES, 'UTF-8');
        $words = preg_split('/[\s,]+/', $keywords);
        $words = array_filter($words, function($word) {
            return strlen($word) > 2 && strlen($word) < 30;
        });
        return array_unique(array_slice($words, 0, 20));
    }
    
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
