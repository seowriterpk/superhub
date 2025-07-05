
<?php
/**
 * VidSocial Routes Configuration
 * Define all application routes here
 */

// Home page
$router->get('/', 'HomeController@index', 'home');

// Search
$router->get('/search/{query}/{page?}', 'SearchController@results', 'search');

// Video pages
$router->get('/video/{slug}-{id}', 'VideoController@show', 'video.show');

// Category pages
$router->get('/category/{slug}/{page?}', 'CategoryController@listing', 'category.show');

// API endpoints with rate limiting
$router->get('/api/v1/videos', 'ApiController@videosJson', 'api.videos');
$router->get('/api/v1/search', 'ApiController@searchJson', 'api.search');

// Legal pages
$router->get('/privacy-policy', 'StaticController@privacy', 'privacy');
$router->get('/dmca', 'StaticController@dmca', 'dmca');
$router->get('/2257', 'StaticController@records', 'records');
$router->get('/contact', 'StaticController@contact', 'contact');

// Age verification
$router->post('/age-verify', 'AgeController@verify', 'age.verify');

// Sitemaps (fixed routes)
$router->get('/sitemap.xml', 'SitemapController@index', 'sitemap');
$router->get('/sitemap-pages.xml', 'SitemapController@pages', 'sitemap.pages');
$router->get('/video-sitemap.xml', 'SitemapController@videos', 'video-sitemap');

// Trending and categories (missing routes)
$router->get('/trending/{page?}', 'HomeController@trending', 'trending');
$router->get('/categories', 'CategoryController@index', 'categories');

// Admin panel
$router->get('/admin', 'AdminController@dashboard', 'admin.dashboard');
$router->post('/admin/login', 'AdminController@login', 'admin.login');
$router->post('/admin/logout', 'AdminController@logout', 'admin.logout');
$router->get('/admin/videos', 'AdminController@videos', 'admin.videos');
$router->get('/admin/settings', 'AdminController@settings', 'admin.settings');
$router->post('/admin/settings', 'AdminController@updateSettings', 'admin.settings.update');
