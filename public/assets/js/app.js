
// VidSocial Main JavaScript
(function() {
    'use strict';
    
    // Age verification handler
    function handleAgeVerification() {
        const ageModal = document.getElementById('age-verification');
        if (ageModal) {
            const yesBtn = document.getElementById('age-yes');
            const noBtn = document.getElementById('age-no');
            
            if (yesBtn) {
                yesBtn.addEventListener('click', function() {
                    fetch('/age/verify', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'verified=yes&csrf_token=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]')?.content || '')
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ageModal.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Age verification error:', error));
                });
            }
            
            if (noBtn) {
                noBtn.addEventListener('click', function() {
                    window.location.href = 'https://www.google.com';
                });
            }
        }
    }
    
    // Lazy loading for video thumbnails
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('loading-skeleton');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    // Search functionality
    function initSearch() {
        const searchForm = document.getElementById('search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                const query = this.querySelector('input[name="q"]')?.value?.trim();
                if (!query) {
                    e.preventDefault();
                    alert('Please enter a search term');
                }
            });
        }
    }
    
    // Video view tracking
    function trackVideoView(videoId) {
        if (videoId && 'IntersectionObserver' in window) {
            const videoElement = document.querySelector('[data-video-id="' + videoId + '"]');
            if (videoElement) {
                const viewObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
                            fetch('/api/video/' + videoId + '/view', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                }
                            }).catch(error => console.error('View tracking error:', error));
                            viewObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });
                
                viewObserver.observe(videoElement);
            }
        }
    }
    
    // Admin panel functionality
    function initAdminPanel() {
        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('admin-sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
            });
        }
        
        // Confirm delete actions
        document.querySelectorAll('[data-confirm]').forEach(element => {
            element.addEventListener('click', function(e) {
                const message = this.dataset.confirm || 'Are you sure?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Performance monitoring
    function initPerformanceMonitoring() {
        if ('PerformanceObserver' in window) {
            // Monitor Largest Contentful Paint
            const lcpObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.startTime > 2500) {
                        console.warn('LCP is slow:', entry.startTime + 'ms');
                    }
                }
            });
            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            
            // Monitor First Input Delay
            const fidObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.processingStart - entry.startTime > 100) {
                        console.warn('FID is slow:', entry.processingStart - entry.startTime + 'ms');
                    }
                }
            });
            fidObserver.observe({ entryTypes: ['first-input'] });
        }
    }
    
    // Initialize everything when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        handleAgeVerification();
        initLazyLoading();
        initSearch();
        initAdminPanel();
        initPerformanceMonitoring();
        
        // Track video views on video pages
        const videoId = document.querySelector('[data-video-id]')?.dataset.videoId;
        if (videoId) {
            trackVideoView(videoId);
        }
    });
    
    // Error handling
    window.addEventListener('error', function(e) {
        console.error('JavaScript error:', e.error);
        // In production, you might want to send this to a logging service
    });
    
})();
