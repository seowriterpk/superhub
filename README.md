
# VidSocial - Adult Video Social Platform

A comprehensive adult video social platform built with PHP 8.2+, MySQL 8, and modern web technologies. Features content streaming via Eporner API v2 with advanced SEO optimization and compliance frameworks.

## 🚀 Features

### Core Functionality
- **Content Streaming**: Integration with Eporner API v2 for video content
- **Modern Architecture**: MVC pattern with PSR-4 autoloading
- **Advanced SEO**: 2025-standard optimization with structured data
- **Video Playback**: HTML5 player with HLS.js fallback
- **Responsive Design**: Mobile-first Tailwind CSS implementation
- **Age Verification**: Configurable adult content protection

### Technical Features
- **Database**: MySQL 8 with optimized schemas and indexes
- **Templating**: Twig 4 for clean, fast rendering
- **API Integration**: RESTful endpoints for SPA functionality
- **CLI Tools**: Artisan-style console commands
- **Caching**: File-based caching with configurable TTL
- **Security**: CSRF protection, HTTPS enforcement, CSP headers

### SEO & Compliance
- **Structured Data**: JSON-LD for VideoObject and BreadcrumbList
- **XML Sitemaps**: Auto-generated video and standard sitemaps
- **Core Web Vitals**: Optimized for LCP, FID, and CLS
- **Legal Compliance**: DMCA, 2257, and privacy policy frameworks
- **Social Sharing**: OpenGraph and Twitter Card meta tags

## 📋 Requirements

- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL Certificate**: Required for production
- **Composer**: For dependency management

### PHP Extensions
- PDO MySQL
- cURL
- JSON
- MBString
- OpenSSL
- Zip

## 🛠 Installation

### 1. Clone and Setup
```bash
# Clone the repository
git clone <repository-url> vidsocial
cd vidsocial

# Install dependencies
composer install

# Copy environment file
cp .env.example .env
```

### 2. Configure Environment
Edit `.env` file with your settings:
```env
# Database
DB_HOST=localhost
DB_NAME=vidsocial
DB_USER=your_username
DB_PASS=your_password

# Eporner API (if required)
EPORNER_API_KEY=your_api_key

# Application
APP_URL=https://yourdomain.com
APP_KEY=your-32-character-secret-key
```

### 3. Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE vidsocial CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php console migrate
```

### 4. Initial Content Sync
```bash
# Sync videos from Eporner API
php console sync:videos --query="latest" --per_page=500 --max_pages=10

# Generate sitemaps
php console generate:sitemap
```

### 5. Web Server Configuration

#### Apache (.htaccess included)
Ensure mod_rewrite is enabled and AllowOverride is set to All.

#### Nginx
```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /path/to/vidsocial/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options SAMEORIGIN always;

    # PHP handling
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block sensitive files
    location ~ /\.(env|git) { deny all; }
    location ~ /(vendor|app|database)/ { deny all; }
}
```

## 🔧 Configuration

### Admin Panel Setup
1. Access `/admin` with default credentials:
   - Username: `admin`
   - Password: `admin123` (change immediately)

2. Configure site settings:
   - Site name and description
   - Contact and DMCA emails
   - Analytics tracking codes
   - Age verification settings

### Cron Jobs
Add these cron jobs for automated maintenance:

```bash
# Sync new videos daily at 2 AM
0 2 * * * cd /path/to/vidsocial && php console sync:videos --query="latest" --per_page=200 --max_pages=5

# Check video status weekly
0 3 * * 0 cd /path/to/vidsocial && php console sync:status

# Generate sitemaps daily at 4 AM
0 4 * * * cd /path/to/vidsocial && php console generate:sitemap
```

## 🎯 Usage

### Console Commands

#### Video Synchronization
```bash
# Sync latest videos
php console sync:videos --query="latest" --per_page=100 --max_pages=5

# Sync specific category
php console sync:videos --query="amateur" --per_page=50 --max_pages=3

# Check for removed videos
php console sync:status
```

#### Sitemap Generation
```bash
# Generate all sitemaps
php console generate:sitemap
```

### API Endpoints
- `GET /api/v1/videos` - List videos with pagination
- `GET /api/v1/search?q=query` - Search videos
- `GET /sitemap.xml` - Main sitemap
- `GET /video-sitemap.xml` - Video sitemap

### URL Structure
- `/` - Homepage with trending content
- `/video/{slug}-{id}` - Individual video pages
- `/category/{slug}/{page}` - Category listings
- `/search/{query}/{page}` - Search results
- `/privacy-policy`, `/dmca`, `/2257` - Legal pages

## 🔒 Security

### Implemented Protections
- **HTTPS Enforcement**: All traffic redirected to HTTPS
- **CSRF Protection**: Tokens on all forms
- **Content Security Policy**: Restricts external resources
- **Input Sanitization**: All user inputs filtered
- **SQL Injection Prevention**: Prepared statements only
- **File Access Control**: Sensitive directories blocked

### Regular Security Tasks
1. Update dependencies regularly: `composer update`
2. Monitor server logs for suspicious activity
3. Keep PHP and MySQL updated
4. Review and rotate API keys
5. Backup database and files regularly

## 📊 SEO Optimization

### Technical SEO
- **Structured Data**: VideoObject schema for all videos
- **Meta Tags**: Optimized titles and descriptions
- **Canonical URLs**: Prevent duplicate content issues
- **XML Sitemaps**: Regular generation and submission
- **Core Web Vitals**: Optimized loading performance

### Content Strategy
- **Unique Titles**: Auto-generated, SEO-friendly titles
- **Rich Descriptions**: Enhanced from API data
- **Category Organization**: Hierarchical content structure
- **Internal Linking**: Related content suggestions

## 🚨 Legal Compliance

### Age Verification
- Configurable age gate with cookie persistence
- Customizable verification messaging
- Legal disclaimer display

### DMCA Compliance
- Dedicated DMCA takedown process
- Contact information display
- Content removal procedures

### 18 U.S.C. 2257 Compliance
- Required record-keeping statements
- Performer age verification notices
- Compliance officer contact information

## 🔧 Troubleshooting

### Common Issues

#### Database Connection Errors
```bash
# Check MySQL service
systemctl status mysql

# Verify credentials in .env file
# Test connection manually
mysql -h localhost -u username -p database_name
```

#### API Sync Failures
```bash
# Check API credentials
# Verify network connectivity
curl -I https://www.eporner.com/api/v2/

# Check logs
tail -f /var/log/apache2/error.log
```

#### Performance Issues
```bash
# Enable opcache in php.ini
opcache.enable=1
opcache.memory_consumption=256

# Optimize MySQL
# Increase innodb_buffer_pool_size
# Enable query cache
```

### Debug Mode
Enable debug mode in `.env`:
```env
APP_DEBUG=true
```

## 📈 Performance Optimization

### Caching Strategy
- **Twig Templates**: Compiled template caching
- **Database Queries**: Query result caching
- **API Responses**: Response caching with TTL
- **Static Assets**: Browser caching headers

### Image Optimization
- **WebP Support**: Automatic format detection
- **Lazy Loading**: Intersection Observer API
- **Responsive Images**: Multiple size variants
- **CDN Integration**: External asset serving

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create feature branch: `git checkout -b feature/new-feature`
3. Make changes and test thoroughly
4. Submit pull request with detailed description

### Code Standards
- PSR-4 autoloading
- PSR-12 coding standards  
- Comprehensive error handling
- Security-first approach

## 📄 License

This project is proprietary software. All rights reserved.

### Legal Disclaimer
This software is designed for adult content distribution and must be used in compliance with all applicable laws and regulations. Users are responsible for ensuring compliance with local, state, and federal laws regarding adult content distribution.

## 📞 Support

For technical support or questions:
- Email: support@yourdomain.com
- Documentation: [Link to detailed docs]
- Issue Tracker: [Link to issue tracker]

---

**⚠️ Important**: This platform is intended for adult content distribution. Ensure compliance with all applicable laws and regulations in your jurisdiction before deployment.
