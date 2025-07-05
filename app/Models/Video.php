<?php
namespace VidSocial\Models;

use VidSocial\Core\Database;

/**
 * Video Model
 * Handles video data operations
 */
class Video
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all videos with pagination
     */
    public function getAll(int $limit = 24, int $offset = 0): array
    {
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug 
                FROM videos v 
                LEFT JOIN categories c ON v.category_id = c.id 
                WHERE v.is_active = 1 AND v.is_removed = 0 
                ORDER BY v.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);
    }
    
    /**
     * Get video count by date
     */
    public function getCountByDate(string $date): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as total FROM videos WHERE DATE(created_at) = :date",
            ['date' => $date]
        );
        return (int)$result['total'];
    }
    
    /**
     * Count videos by category
     */
    public function countByCategory(int $categoryId): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as total FROM videos WHERE category_id = :category_id AND is_active = 1 AND is_removed = 0",
            ['category_id' => $categoryId]
        );
        return (int)$result['total'];
    }
    
    /**
     * Get search count
     */
    public function getSearchCount(string $query): int
    {
        $searchTerm = '%' . $query . '%';
        $result = $this->db->fetch(
            "SELECT COUNT(*) as total FROM videos WHERE is_active = 1 AND is_removed = 0 AND (title LIKE :search OR description LIKE :search)",
            ['search' => $searchTerm]
        );
        return (int)$result['total'];
    }
    
    /**
     * Find video by slug and ID
     */
    public function findBySlugAndId(string $slug, string $id): ?array
    {
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug 
                FROM videos v 
                LEFT JOIN categories c ON v.category_id = c.id 
                WHERE v.slug = :slug AND v.eporner_id = :id AND v.is_active = 1 AND v.is_removed = 0";
        
        return $this->db->fetch($sql, ['slug' => $slug, 'id' => $id]);
    }
    
    /**
     * Get trending videos (by views)
     */
    public function getTrending(int $limit = 24, int $offset = 0): array
    {
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug 
                FROM videos v 
                LEFT JOIN categories c ON v.category_id = c.id 
                WHERE v.is_active = 1 AND v.is_removed = 0 
                ORDER BY v.views DESC, v.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);
    }
    
    /**
     * Get recent videos
     */
    public function getRecent(int $limit = 24, int $offset = 0): array
    {
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug 
                FROM videos v 
                LEFT JOIN categories c ON v.category_id = c.id 
                WHERE v.is_active = 1 AND v.is_removed = 0 
                ORDER BY v.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);
    }
    
    /**
     * Search videos
     */
    public function search(string $query, int $limit = 24, int $offset = 0): array
    {
        $searchTerm = '%' . $query . '%';
        
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug 
                FROM videos v 
                LEFT JOIN categories c ON v.category_id = c.id 
                WHERE v.is_active = 1 AND v.is_removed = 0 
                AND (v.title LIKE :search OR v.description LIKE :search) 
                ORDER BY v.views DESC, v.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            'search' => $searchTerm, 
            'limit' => $limit, 
            'offset' => $offset
        ]);
    }
    
    /**
     * Get videos by category
     */
    public function getByCategory(int $categoryId, int $limit = 24, int $offset = 0): array
    {
        $sql = "SELECT v.*, c.name as category_name, c.slug as category_slug 
                FROM videos v 
                INNER JOIN categories c ON v.category_id = c.id 
                WHERE v.category_id = :category_id AND v.is_active = 1 AND v.is_removed = 0 
                ORDER BY v.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            'category_id' => $categoryId, 
            'limit' => $limit, 
            'offset' => $offset
        ]);
    }
    
    /**
     * Get related videos (by category and tags)
     */
    public function getRelated(int $videoId, int $categoryId = null, int $limit = 12): array
    {
        $sql = "SELECT DISTINCT v.*, c.name as category_name, c.slug as category_slug 
                FROM videos v 
                LEFT JOIN categories c ON v.category_id = c.id 
                LEFT JOIN video_tag vt ON v.id = vt.video_id 
                LEFT JOIN video_tag vt2 ON vt.tag_id = vt2.tag_id 
                WHERE v.id != :video_id AND v.is_active = 1 AND v.is_removed = 0";
        
        $params = ['video_id' => $videoId];
        
        if ($categoryId) {
            $sql .= " AND (v.category_id = :category_id OR vt2.video_id = :video_id2)";
            $params['category_id'] = $categoryId;
            $params['video_id2'] = $videoId;
        }
        
        $sql .= " ORDER BY v.views DESC LIMIT :limit";
        $params['limit'] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Create or update video
     */
    public function createOrUpdate(array $data): int
    {
        // Check if video exists
        $existing = $this->db->fetch(
            "SELECT id FROM videos WHERE eporner_id = :eporner_id", 
            ['eporner_id' => $data['eporner_id']]
        );
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($existing) {
            // Update existing video
            $this->db->update('videos', $data, ['id' => $existing['id']]);
            return $existing['id'];
        } else {
            // Create new video
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert('videos', $data);
        }
    }
    
    /**
     * Mark videos as removed
     */
    public function markAsRemoved(array $epornerIds): int
    {
        if (empty($epornerIds)) {
            return 0;
        }
        
        $placeholders = str_repeat('?,', count($epornerIds) - 1) . '?';
        $sql = "UPDATE videos SET is_removed = 1, updated_at = NOW() 
                WHERE eporner_id IN ({$placeholders})";
        
        $stmt = $this->db->query($sql, $epornerIds);
        return $stmt->rowCount();
    }
    
    /**
     * Get total count for pagination
     */
    public function getTotalCount(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM videos WHERE is_active = 1 AND is_removed = 0";
        $params = [];
        
        if (!empty($conditions['search'])) {
            $sql .= " AND (title LIKE :search OR description LIKE :search)";
            $params['search'] = '%' . $conditions['search'] . '%';
        }
        
        if (!empty($conditions['category_id'])) {
            $sql .= " AND category_id = :category_id";
            $params['category_id'] = $conditions['category_id'];
        }
        
        $result = $this->db->fetch($sql, $params);
        return (int)$result['total'];
    }
    
    /**
     * Get videos for sitemap
     */
    public function getForSitemap(int $limit = 50000, int $offset = 0): array
    {
        $sql = "SELECT eporner_id, slug, title, updated_at, thumb_url 
                FROM videos 
                WHERE is_active = 1 AND is_removed = 0 
                ORDER BY updated_at DESC 
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);
    }
    
    /**
     * Update video view count
     */
    public function incrementViews(int $videoId): void
    {
        $sql = "UPDATE videos SET views = views + 1 WHERE id = :id";
        $this->db->query($sql, ['id' => $videoId]);
    }
    
    /**
     * Generate SEO-optimized title
     */
    public function generateSeoTitle(array $video): string
    {
        $title = $video['title'];
        
        // Remove excessive punctuation
        $title = preg_replace('/[!@#$%^&*()_+=\[\]{}|;\':",./<>?~`]+/', ' ', $title);
        $title = preg_replace('/\s+/', ' ', trim($title));
        
        // Capitalize first letter of each word
        $title = ucwords(strtolower($title));
        
        // Add category if available
        if (!empty($video['category_name'])) {
            $title .= ' - ' . ucfirst($video['category_name']) . ' Video';
        }
        
        // Limit length for SEO
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
        
        return $title;
    }
    
    /**
     * Generate SEO-optimized description
     */
    public function generateSeoDescription(array $video): string
    {
        $description = $video['description'] ?: $video['title'];
        
        // Clean up description
        $description = strip_tags($description);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description);
        
        // Add context
        $description = "Watch " . $description;
        
        if (!empty($video['category_name'])) {
            $description .= " in " . $video['category_name'] . " category";
        }
        
        $description .= " and thousands more adult videos on VidSocial.";
        
        // Limit length for SEO
        if (strlen($description) > 160) {
            $description = substr($description, 0, 157) . '...';
        }
        
        return $description;
    }
}
