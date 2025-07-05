
<?php
namespace VidSocial\Models;

use VidSocial\Core\Database;

/**
 * Category Model
 * Handles video categories
 */
class Category
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all categories with video counts
     */
    public function getAllWithCounts(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM categories WHERE is_active = 1 ORDER BY video_count DESC, name ASC"
        );
    }
    
    /**
     * Get category by slug
     */
    public function getBySlug(string $slug): ?array
    {
        return $this->db->fetch("SELECT * FROM categories WHERE slug = :slug AND is_active = 1", ['slug' => $slug]);
    }
    
    /**
     * Get top categories by video count
     */
    public function getTopCategories(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM categories WHERE is_active = 1 ORDER BY video_count DESC LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    /**
     * Get total count of categories
     */
    public function getTotalCount(): int
    {
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM categories WHERE is_active = 1");
        return (int)$result['total'];
    }
    
    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetch("SELECT * FROM categories WHERE slug = :slug AND is_active = 1", ['slug' => $slug]);
    }
    
    /**
     * Get popular categories
     */
    public function getPopular(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM categories WHERE is_active = 1 ORDER BY video_count DESC, name ASC LIMIT :limit",
            ['limit' => $limit]
        );
    }
    
    /**
     * Find or create category by name
     */
    public function findOrCreate(string $name): int
    {
        $slug = $this->generateSlug($name);
        
        $existing = $this->db->fetch("SELECT id FROM categories WHERE slug = :slug", ['slug' => $slug]);
        
        if ($existing) {
            return $existing['id'];
        }
        
        return $this->db->insert('categories', [
            'name' => ucfirst($name),
            'slug' => $slug,
            'description' => "Videos in the {$name} category",
            'is_active' => 1
        ]);
    }
    
    /**
     * Update video count for category
     */
    public function updateVideoCount(int $categoryId): void
    {
        $sql = "UPDATE categories SET video_count = (
                    SELECT COUNT(*) FROM videos 
                    WHERE category_id = :category_id AND is_active = 1 AND is_removed = 0
                ) WHERE id = :category_id";
        
        $this->db->query($sql, ['category_id' => $categoryId]);
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
