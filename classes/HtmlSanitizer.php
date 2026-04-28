<?php

namespace Golem15\Apparatus\Classes;

/**
 * HTML sanitizer wrapping HTMLPurifier with the D-12 security allowlist.
 *
 * Allowed tags: p, br, strong, em, a[href], h1-h6, ul, ol, li,
 * blockquote, img[src|alt], iframe[src].
 *
 * Iframe src restricted to youtube.com, youtube-nocookie.com, vimeo.com
 * via anchored URI.SafeIframeRegexp (Pitfall 4: prevents bypass via
 * query-string embedding of allowlisted hosts).
 *
 * URL schemes restricted to http, https, mailto.
 */
class HtmlSanitizer
{
    private static ?\HTMLPurifier $purifier = null;

    /**
     * Sanitize HTML through the D-12 allowlist.
     *
     * @param string $html Raw HTML input
     * @return string Sanitized HTML
     */
    public static function clean(string $html): string
    {
        return self::getPurifier()->purify($html);
    }

    /**
     * Get or create the singleton HTMLPurifier instance.
     */
    private static function getPurifier(): \HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache/htmlpurifier'));
        $config->set('HTML.Allowed',
            'p,br,strong,em,a[href],h1,h2,h3,h4,h5,h6,'
            . 'ul,ol,li,blockquote,'
            . 'img[src|alt],iframe[src]'
        );
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.SafeIframe', true);
        // Iframe host allowlist: youtube.com, youtube-nocookie.com, vimeo.com (D-12)
        // Anchored regex (^) prevents bypass via query-string embedding (Pitfall 4)
        $config->set('URI.SafeIframeRegexp',
            '%^https?://(www\.)?(youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%'
        );

        $cachePath = $config->get('Cache.SerializerPath');
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0755, true);
        }

        return self::$purifier = new \HTMLPurifier($config);
    }
}
