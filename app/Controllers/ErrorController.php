
<?php
namespace VidSocial\Controllers;

/**
 * Error Controller
 * Handles error pages and exceptions
 */
class ErrorController extends BaseController
{
    /**
     * 404 Not Found page
     */
    public function notFound(): void
    {
        http_response_code(404);
        
        $this->render('404.twig', [
            'page_title' => 'Page Not Found - ' . $this->getSetting('site_name'),
            'meta_description' => 'The requested page could not be found.',
            'canonical_url' => $this->app->url($_SERVER['REQUEST_URI'] ?? '/')
        ]);
    }
    
    /**
     * 500 Internal Server Error page
     */
    public function serverError(): void
    {
        http_response_code(500);
        
        $this->render('500.twig', [
            'page_title' => 'Server Error - ' . $this->getSetting('site_name'),
            'meta_description' => 'An internal server error occurred.'
        ]);
    }
    
    /**
     * 403 Forbidden page
     */
    public function forbidden(): void
    {
        http_response_code(403);
        
        $this->render('403.twig', [
            'page_title' => 'Access Forbidden - ' . $this->getSetting('site_name'),
            'meta_description' => 'Access to this resource is forbidden.'
        ]);
    }
}
