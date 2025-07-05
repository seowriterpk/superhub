<?php
namespace VidSocial\Controllers;

use VidSocial\Models\Video;
use VidSocial\Models\Tag;

/**
 * Video Controller
 * Handles individual video page display
 */
class VideoController extends BaseController
{
    public function show(string $slug, string $id): void
    {
        $videoModel = new Video();
        $tagModel = new Tag();
        
        // Find video by slug and ID
        $video = $videoModel->findBySlugAndId($slug, $id);
        
        if (!$video) {
            http_response_code(404);
            $this->render('404.twig', [
                'title' => 'Video Not Found',
                'message' => 'The requested video could not be found.',
                'canonical_url' => $this->getCurrentUrl()
            ]);
            return;
        }
        
        // Increment view count
        $videoModel->incrementViews($video['id']);
        
        // Get related videos
        $relatedVideos = $videoModel->getRelated($video['id'], $video['category_id'], 8);
        
        // Get video tags
        $tags = $tagModel->getVideoTags($video['id']);
        
        // Generate structured data
        $structuredData = $this->generateVideoStructuredData($video);
        
        // Generate breadcrumbs
        $breadcrumbs = $this->generateVideoBreadcrumbs($video);
        
        // SEO optimization - ensure unique titles
        $seoTitle = $this->generateUniqueVideoTitle($video);
        $seoDescription = $this->generateUniqueVideoDescription($video);
        
        $this->render('video.twig', [
            'title' => $seoTitle,
            'meta_description' => $seoDescription,
            'video' => $video,
            'related_videos' => $relatedVideos,
            'tags' => $tags,
            'structured_data' => $structuredData,
            'breadcrumbs' => $breadcrumbs,
            'breadcrumb_items' => [
                ['name' => 'Home', 'url' => '/'],
                ['name' => $video['category_name'] ?: 'Videos', 'url' => $video['category_slug'] ? "/category/{$video['category_slug']}" : '/'],
                ['name' => $video['title']]
            ],
            'canonical_url' => $this->app->url("video/{$video['slug']}-{$video['eporner_id']}"),
            'og_image' => $video['thumb_url'],
        ]);
    }
    
    private function generateUniqueVideoTitle(array $video): string
    {
        $title = $video['seo_title'] ?: $video['title'];
        
        // Clean and normalize title
        $title = preg_replace('/[^\w\s-]/', '', $title);
        $title = preg_replace('/\s+/', ' ', trim($title));
        
        // Add unique differentiators to prevent duplicates
        $suffix = '';
        if (!empty($video['category_name'])) {
            $suffix = ' - ' . ucfirst($video['category_name']) . ' Video';
        }
        
        // Add duration if available for uniqueness
        if (!empty($video['duration']) && $video['duration'] > 0) {
            $minutes = floor($video['duration'] / 60);
            if ($minutes > 0) {
                $suffix .= " ({$minutes} min)";
            }
        }
        
        // Add site name
        $suffix .= ' | ' . $this->settings['site_name'];
        
        // Ensure total length is SEO-friendly
        $maxLength = 60 - strlen($suffix);
        if (strlen($title) > $maxLength) {
            $title = substr($title, 0, $maxLength - 3) . '...';
        }
        
        return $title . $suffix;
    }
    
    private function generateUniqueVideoDescription(array $video): string
    {
        $description = $video['seo_description'] ?: $video['description'] ?: $video['title'];
        
        // Clean description
        $description = strip_tags($description);
        $description = preg_replace('/\s+/', ' ', trim($description));
        
        // Add unique context
        $context = "Watch this ";
        if (!empty($video['category_name'])) {
            $context .= strtolower($video['category_name']) . " ";
        }
        $context .= "video";
        
        if (!empty($video['duration']) && $video['duration'] > 0) {
            $minutes = floor($video['duration'] / 60);
            $context .= " ({$minutes} minutes)";
        }
        
        $context .= " and thousands more on " . $this->settings['site_name'] . ".";
        
        // Combine and limit length
        $fullDescription = $description . " " . $context;
        
        if (strlen($fullDescription) > 160) {
            $maxDescLength = 160 - strlen($context) - 4;
            $description = substr($description, 0, $maxDescLength) . '... ';
            $fullDescription = $description . $context;
        }
        
        return $fullDescription;
    }
    
    private function generateVideoStructuredData(array $video): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $video['title'],
            'description' => $video['description'] ?: substr($video['title'], 0, 200),
            'thumbnailUrl' => $video['thumb_url'],
            'uploadDate' => date('c', strtotime($video['created_at'])),
            'duration' => 'PT' . $video['duration'] . 'S',
            'contentUrl' => $this->app->url("video/{$video['slug']}-{$video['eporner_id']}"),
            'embedUrl' => $video['embed_url'],
            'interactionStatistic' => [
                '@type' => 'InteractionCounter',
                'interactionType' => 'http://schema.org/WatchAction',
                'userInteractionCount' => $video['views']
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => $video['rating'],
                'ratingCount' => max(1, intval($video['views'] / 10))
            ],
            'isFamilyFriendly' => 'false',
            'contentRating' => 'adult',
            'genre' => $video['category_name'] ?: 'Adult',
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->settings['site_name'],
                'url' => $this->app->url()
            ]
        ];
    }
    
    private function generateVideoBreadcrumbs(array $video): array
    {
        $items = [
            ['name' => 'Home', 'url' => $this->app->url()],
        ];
        
        if ($video['category_name']) {
            $items[] = [
                'name' => $video['category_name'],
                'url' => $this->app->url("category/{$video['category_slug']}")
            ];
        }
        
        $items[] = ['name' => $video['title']];
        
        return $this->generateBreadcrumbs($items);
    }
}
