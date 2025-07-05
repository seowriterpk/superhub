
<?php
namespace VidSocial\Controllers;

use VidSocial\Models\Video;
use VidSocial\Models\Category;
use VidSocial\Services\SitemapGenerator;

/**
 * Sitemap Controller
 * Handles XML sitemap generation
 */
class SitemapController extends BaseController
{
    public function index(): void
    {
        $generator = new SitemapGenerator();
        
        header('Content-Type: application/xml; charset=utf-8');
        echo $generator->generateMainSitemap();
    }
    
    public function videos(): void
    {
        $generator = new SitemapGenerator();
        
        header('Content-Type: application/xml; charset=utf-8');
        echo $generator->generateVideoSitemap();
    }
    
    public function pages(): void
    {
        $generator = new SitemapGenerator();
        
        header('Content-Type: application/xml; charset=utf-8');
        echo $generator->generatePagesSitemap();
    }
}
