
<?php
namespace VidSocial\Services;

use VidSocial\Models\Video;
use VidSocial\Models\Category;
use VidSocial\Models\Tag;
use VidSocial\Core\Application;
use VidSocial\Core\Database;

/**
 * Optimized Video Sync Service
 * Handles bulk video operations with batch processing
 */
class VideoSyncService
{
    private $apiClient;
    private $videoModel;
    private $categoryModel;
    private $tagModel;
    private $db;
    
    public function __construct()
    {
        $this->apiClient = new SimplifiedEpornerClient();
        $this->videoModel = new Video();
        $this->categoryModel = new Category();
        $this->tagModel = new Tag();
        $this->db = Database::getInstance();
    }
    
    /**
     * Bulk sync videos with optimized batch processing
     */
    public function bulkSync(array $options = []): array
    {
        $defaults = [
            'query' => 'all',
            'order' => 'latest',
            'per_page' => 1000,
            'max_pages' => 10,
            'max_videos' => 10000,
            'category_filter' => null,
            'batch_size' => 100
        ];
        
        $options = array_merge($defaults, $options);
        
        $stats = [
            'total_processed' => 0,
            'new_videos' => 0,
            'updated_videos' => 0,
            'errors' => 0,
            'start_time' => time()
        ];
        
        $page = 1;
        $videoBatch = [];
        
        while ($page <= $options['max_pages'] && $stats['total_processed'] < $options['max_videos']) {
            echo "Processing page {$page}...\n";
            
            $response = $this->apiClient->getVideos(
                $page,
                $options['query'],
                $options['per_page'],
                $options['order']
            );
            
            if (!$response || empty($response['videos'])) {
                echo "No videos found on page {$page}. Stopping.\n";
                break;
            }
            
            foreach ($response['videos'] as $videoData) {
                if ($stats['total_processed'] >= $options['max_videos']) {
                    break;
                }
                
                // Apply category filter if specified
                if ($options['category_filter'] && 
                    strpos(strtolower($videoData['keywords']), $options['category_filter']) === false) {
                    continue;
                }
                
                $videoBatch[] = $videoData;
                $stats['total_processed']++;
                
                // Process batch when it reaches the specified size
                if (count($videoBatch) >= $options['batch_size']) {
                    $batchStats = $this->processBatch($videoBatch);
                    $stats['new_videos'] += $batchStats['new'];
                    $stats['updated_videos'] += $batchStats['updated'];
                    $stats['errors'] += $batchStats['errors'];
                    
                    $videoBatch = []; // Reset batch
                    
                    echo "Batch processed: {$batchStats['new']} new, {$batchStats['updated']} updated\n";
                }
            }
            
            $page++;
            
            // Add small delay to avoid overwhelming the API
            usleep(250000); // 0.25 seconds
        }
        
        // Process remaining videos in batch
        if (!empty($videoBatch)) {
            $batchStats = $this->processBatch($videoBatch);
            $stats['new_videos'] += $batchStats['new'];
            $stats['updated_videos'] += $batchStats['updated'];
            $stats['errors'] += $batchStats['errors'];
        }
        
        $stats['duration'] = time() - $stats['start_time'];
        return $stats;
    }
    
    /**
     * Process a batch of videos
     */
    private function processBatch(array $videoBatch): array
    {
        $stats = ['new' => 0, 'updated' => 0, 'errors' => 0];
        
        try {
            // Start transaction for batch processing
            $this->db->getConnection()->beginTransaction();
            
            foreach ($videoBatch as $videoData) {
                try {
                    // Find or create category
                    $categoryId = null;
                    if (!empty($videoData['category'])) {
                        $categoryId = $this->categoryModel->findOrCreate($videoData['category']);
                    }
                    
                    // Prepare video record
                    $videoRecord = [
                        'eporner_id' => $videoData['eporner_id'],
                        'title' => $videoData['title'],
                        'slug' => $videoData['slug'],
                        'description' => $videoData['description'],
                        'duration' => $videoData['duration'],
                        'views' => $videoData['views'],
                        'rating' => $videoData['rating'],
                        'thumb_url' => $videoData['thumb_url'],
                        'embed_url' => $videoData['embed_url'],
                        'video_url' => $videoData['video_url'],
                        'hls_url' => $videoData['hls_url'],
                        'added_date' => $videoData['added_date'],
                        'category_id' => $categoryId,
                        'is_active' => 1,
                        'is_removed' => 0,
                    ];
                    
                    // Generate SEO fields
                    $app = Application::getInstance();
                    $videoRecord['seo_title'] = $this->videoModel->generateSeoTitle($videoRecord);
                    $videoRecord['seo_description'] = $this->videoModel->generateSeoDescription($videoRecord);
                    $videoRecord['canonical_url'] = $app->url("video/{$videoRecord['slug']}-{$videoRecord['eporner_id']}");
                    
                    // Check if video exists
                    $existing = $this->db->fetch(
                        "SELECT id FROM videos WHERE eporner_id = :eporner_id", 
                        ['eporner_id' => $videoData['eporner_id']]
                    );
                    
                    if ($existing) {
                        // Update existing video
                        $videoRecord['updated_at'] = date('Y-m-d H:i:s');
                        $this->db->update('videos', $videoRecord, ['id' => $existing['id']]);
                        $videoId = $existing['id'];
                        $stats['updated']++;
                    } else {
                        // Create new video
                        $videoRecord['created_at'] = date('Y-m-d H:i:s');
                        $videoId = $this->db->insert('videos', $videoRecord);
                        $stats['new']++;
                    }
                    
                    // Process tags
                    if (!empty($videoData['keywords']) && $videoId) {
                        $this->tagModel->syncVideoTags($videoId, $videoData['keywords']);
                    }
                    
                } catch (Exception $e) {
                    error_log("Error processing video {$videoData['eporner_id']}: " . $e->getMessage());
                    $stats['errors']++;
                }
            }
            
            // Commit transaction
            $this->db->getConnection()->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $this->db->getConnection()->rollBack();
            error_log("Batch processing error: " . $e->getMessage());
            $stats['errors'] += count($videoBatch);
        }
        
        return $stats;
    }
    
    /**
     * Sync removed videos
     */
    public function syncRemovedVideos(): int
    {
        $totalRemoved = 0;
        $page = 1;
        
        while (true) {
            echo "Checking removed videos page {$page}...\n";
            
            $removedIds = $this->apiClient->getRemovedVideos($page);
            
            if (empty($removedIds)) {
                break;
            }
            
            $removedCount = $this->videoModel->markAsRemoved($removedIds);
            $totalRemoved += $removedCount;
            
            echo "Marked {$removedCount} videos as removed on page {$page}\n";
            
            $page++;
            usleep(500000); // 0.5 second delay
        }
        
        return $totalRemoved;
    }
    
    /**
     * Category-specific sync
     */
    public function syncByCategory(string $category, int $maxVideos = 1000): array
    {
        return $this->bulkSync([
            'query' => $category,
            'category_filter' => $category,
            'max_videos' => $maxVideos,
            'order' => 'most-popular'
        ]);
    }
}
