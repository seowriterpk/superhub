
<?php
namespace VidSocial\Controllers;

/**
 * Static Controller
 * Handles static/legal pages
 */
class StaticController extends BaseController
{
    /**
     * Privacy Policy page
     */
    public function privacy(): void
    {
        $this->render('static/privacy.twig', [
            'page_title' => 'Privacy Policy - ' . $this->getSetting('site_name'),
            'meta_description' => 'Privacy Policy for ' . $this->getSetting('site_name'),
            'canonical_url' => $this->app->url('privacy-policy'),
            'breadcrumb_items' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Privacy Policy', 'url' => '/privacy-policy']
            ]
        ]);
    }
    
    /**
     * DMCA page
     */
    public function dmca(): void
    {
        $this->render('static/dmca.twig', [
            'page_title' => 'DMCA - ' . $this->getSetting('site_name'),
            'meta_description' => 'DMCA Notice and Takedown Policy',
            'canonical_url' => $this->app->url('dmca'),
            'breadcrumb_items' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'DMCA', 'url' => '/dmca']
            ]
        ]);
    }
    
    /**
     * 2257 Records page
     */
    public function records(): void
    {
        $this->render('static/2257.twig', [
            'page_title' => '18 U.S.C. 2257 - ' . $this->getSetting('site_name'),
            'meta_description' => '18 U.S.C. 2257 Record-Keeping Requirements Compliance Statement',
            'canonical_url' => $this->app->url('2257'),
            'breadcrumb_items' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => '18 U.S.C. 2257', 'url' => '/2257']
            ]
        ]);
    }
    
    /**
     * Contact page
     */
    public function contact(): void
    {
        $this->render('static/contact.twig', [
            'page_title' => 'Contact Us - ' . $this->getSetting('site_name'),
            'meta_description' => 'Contact ' . $this->getSetting('site_name'),
            'canonical_url' => $this->app->url('contact'),
            'breadcrumb_items' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => 'Contact', 'url' => '/contact']
            ]
        ]);
    }
}
