<?php

namespace Kanboard\Core\Security;

/**
 * Content Security Policy
 */
class ContentSecurityPolicy
{
    /**
     * Get CSP header value
     *
     * @return string
     */
    public static function getPolicy()
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "connect-src 'self'",
            "media-src 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'"
        ]);
    }
} 