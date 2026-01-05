# NewsDesk Sitemap

**Professional-grade news sitemap generator for maximum search exposure. Complies with Google News, Bing News, and IndexNow protocols.**

## Description

NewsDesk Sitemap is a robust WordPress plugin designed to supercharge your site's visibility in news aggregators. It automatically generates a Google News compliant XML sitemap and instantly notifies search engines when you publish or update content.

## Features

-   **Google News Compliant**: Follows the strict sitemap protocol required by Google News.
-   **Instant Indexing**: Supports IndexNow, Google Ping, and Bing Ping for immediate discovery.
-   **Performance Optimized**: Built-in caching and intelligent query optimization for high-traffic sites.
-   **Breaking News Support**: Prioritizes "Breaking News" content in sitemaps.
-   **Sitemap Indexing**: Automatically handles large sites by splitting sitemaps into an index.
-   **Comprehensive Logging**: Debug logs to track every ping and sitemap generation event.

## Installation

1.  Upload the `newsdesk-sitemap` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **News Sitemap > Settings** to configure your Publication Name (Required for Google News).

## Configuration

1.  **General Settings**: Set your Publication Name and default language.
2.  **Content Filters**: Exclude specific categories or tags from the news sitemap.
3.  **Ping Settings**: Enable/Disable auto-pinging for Google, Bing, and IndexNow.

## Troubleshooting

If you encounter issues during activation, check `wp-content/uploads/nds-debug.log` for detailed error messages. The plugin includes a safe-activation mode that prevents site crashes even if a fatal error occurs.

## Requirements

-   WordPress 5.8 or higher
-   PHP 7.4 or higher
