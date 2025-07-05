
<?php
namespace VidSocial\Models;

use VidSocial\Core\Database;

/**
 * Tag Model
 * Handles video tags and many-to-many relationships
 */
class Tag
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Sync tags for a video
     */
    public function syncVideoTags(int $videoId, array $keywords): void
    {
        // Remove existing tags for this video
        $this->db->delete('video_tag', ['video_id' => $videoId]);
        
        foreach ($keywords as $keyword) {
            $tagId = $this->findOrCreate($keyword);
            
            // Create video-tag relationship
            $this->db->insert('video_tag', [
                'video_id' => $videoId,
                'tag_id' => $tagId
            ]);
        }
        
        // Update tag video counts
        $this->updateTagCounts();
    }
    
    /**
     * Find or create tag by name
     */
    public function findOrCreate(string $name): int
    {
        $name = trim($name);
        if (empty($name) || strlen($name) > 100) {
            return 0; // Skip invalid tags
        }
        
        $slug = $this->generateSlug($name);
        
        $existing = $this->db->fetch("SELECT id FROM tags WHERE slug = :slug", ['slug' => $slug]);
        
        if ($existing) {
            return $existing['id'];
        }
        
        return $this->db->insert('tags', [
            'name' => $name,
            'slug' => $slug,
            'is_active' => 1
        ]);
    }
    
    /**
     * Get tags for a video
     */
    public function getVideoTags(int $videoId): array
    {
        $sql = "SELECT t.* FROM tags t 
                INNER JOIN video_tag vt ON t.id = vt.tag_id 
                WHERE vt.video_id = :video_id AND t.is_active = 1 
                ORDER BY t.name";
        
        return $this->db->fetchAll($sql, ['video_id' => $videoId]);
    }
    
    /**
     * Get popular tags
     */
    public function getPopular(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM tags WHERE is_active = 1 ORDER BY video_count DESC, name ASC LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    /**
     * Update tag video counts
     */
    private function updateTagCounts(): void
    {
        $sql = "UPDATE tags SET video_count = (
                    SELECT COUNT(DISTINCT vt.video_id) FROM video_tag vt 
                    INNER JOIN videos v ON vt.video_id = v.id 
                    WHERE vt.tag_id = tags.id AND v.is_active = 1 AND v.is_removed = 0
                )";
        
        $this->db->query($sql);
    }
    
    /**
     * Generate URL-friendly slug
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}
