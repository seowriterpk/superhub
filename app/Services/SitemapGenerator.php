
<?php
namespace VidSocial\Services;

use VidSocial\Models\Video;
use VidSocial\Models\Category;
use VidSocial\Core\Database;

/**
 * Sitemap Generator
 * Generates XML sitemaps for SEO
 */
class SitemapGenerator
{
    private $db;
    private $baseUrl;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->baseUrl = rtrim($_ENV['APP_URL'] ?? 'https://example.com', '/');
    }
    
    public function generateMainSitemap(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Main sitemap
        $xml .= '  <sitemap>' . "\n";
        $xml .= '    <loc>' . $this->baseUrl . '/sitemap-pages.xml</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d\TH:i:s\Z') . '</lastmod>' . "\n";
        $xml .= '  </sitemap>' . "\n";
        
        // Video sitemap
        $xml .= '  <sitemap>' . "\n";
        $xml .= '    <loc>' . $this->baseUrl . '/video-sitemap.xml</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d\TH:i:s\Z') . '</lastmod>' . "\n";
        $xml .= '  </sitemap>' . "\n";
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }
    
    public function generateVideoSitemap(): string
    {
        $videoModel = new Video();
        $videos = $videoModel->getForSitemap(50000);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";
        
        foreach ($videos as $video) {
            $videoUrl = $this->baseUrl . "/video/{$video['slug']}-{$video['eporner_id']}";
            
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($videoUrl) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d\TH:i:s\Z', strtotime($video['updated_at'])) . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.8</priority>' . "\n";
            
            // Video-specific data
            $xml .= '    <video:video>' . "\n";
            $xml .= '      <video:thumbnail_loc>' . htmlspecialchars($video['thumb_url']) . '</video:thumbnail_loc>' . "\n";
            $xml .= '      <video:title>' . htmlspecialchars($video['title']) . '</video:title>' . "\n";
            $xml .= '      <video:description>' . htmlspecialchars(substr($video['title'], 0, 200)) . '</video:description>' . "\n";
            $xml .= '      <video:content_loc>' . htmlspecialchars($videoUrl) . '</video:content_loc>' . "\n";
            $xml .= '      <video:duration>' . intval($video['duration']) . '</video:duration>' . "\n";
            $xml .= '      <video:family_friendly>no</video:family_friendly>' . "\n";
            $xml .= '    </video:video>' . "\n";
            
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
}
