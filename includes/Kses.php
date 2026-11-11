<?php

namespace AlingsasCustomisation\Includes;

/**
 * Allows iframes from mediaflow.com (and any subdomain) to be saved by users
 * without the unfiltered_html capability (i.e. everyone but super admins in
 * multisite). Iframes from other hosts are still removed.
 */
class Kses
{
    const ALLOWED_DOMAIN = 'mediaflow.com';

    public function __construct()
    {
        add_filter('wp_kses_allowed_html', [$this, 'allowIframe'], 10, 2);
        add_filter('pre_kses', [$this, 'removeForeignIframes'], 10, 2);
    }

    /**
     * Add iframe and its attributes to the allowed tags for post content.
     *
     * @param array        $tags    Allowed tags.
     * @param string|array $context Context name.
     * @return array
     */
    public function allowIframe($tags, $context)
    {
        if ($context !== 'post' || !is_array($tags)) {
            return $tags;
        }

        $tags['iframe'] = [
            'src'             => true,
            'title'           => true,
            'style'           => true,
            'class'           => true,
            'id'              => true,
            'width'           => true,
            'height'          => true,
            'allow'           => true,
            'allowfullscreen' => true,
            'frameborder'     => true,
            'loading'         => true,
        ];

        return $tags;
    }

    /**
     * Strip every iframe whose src is not on the allowed domain, before kses
     * runs. kses itself cannot validate attribute values like hostnames.
     *
     * @param string       $content Content about to be filtered.
     * @param array|string $allowedHtml Allowed html (array) or context name (e.g. 'post').
     * @return string
     */
    public function removeForeignIframes($content, $allowedHtml)
    {
        // $allowedHtml is either a context name ('post') or an array of allowed tags.
        $iframeAllowed = $allowedHtml === 'post' || (is_array($allowedHtml) && isset($allowedHtml['iframe']));

        if (!$iframeAllowed || stripos($content, '<iframe') === false) {
            return $content;
        }

        $result = preg_replace_callback(
            '#<iframe\b[^>]*>(?:.*?</iframe\s*>)?#is',
            function ($matches) {
                if (!preg_match('#\ssrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))#i', $matches[0], $src)) {
                    return '';
                }

                $url = html_entity_decode(trim($src[1] . $src[2] . $src[3]), ENT_QUOTES);

                return $this->isAllowedUrl($url) ? $matches[0] : '';
            },
            $content
        );

        // On regex failure, fail closed by keeping kses' default behaviour (iframe removed).
        return $result ?? preg_replace('#<iframe\b#i', '<removed-iframe', $content);
    }

    private function isAllowedUrl(string $url): bool
    {
        // Allow protocol-relative URLs, e.g. //play.mediaflow.com/...
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        $parts = wp_parse_url($url);
        if (empty($parts['host']) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);

        return $host === self::ALLOWED_DOMAIN || str_ends_with($host, '.' . self::ALLOWED_DOMAIN);
    }
}
