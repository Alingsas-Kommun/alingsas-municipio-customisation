<?php
namespace AlingsasCustomisation\Includes;

/**
 * Shape divider embeds SVG attachments from disk. Locally those files are
 * proxied from production, and wp_remote_get rejects the DDEV certificate.
 */
class RemoteUploads {
    public function __construct() {
        add_filter('https_ssl_verify', [$this, 'allowDdevUploadProxy'], 10, 2);
    }

    /**
     * @param bool $verify
     * @param string $url
     * @return bool
     */
    public function allowDdevUploadProxy($verify, $url) {
        if (!is_string($url)) {
            return $verify;
        }

        $host = wp_parse_url($url, PHP_URL_HOST);
        $path = wp_parse_url($url, PHP_URL_PATH);
        if (!is_string($host) || !is_string($path)) {
            return $verify;
        }

        if (str_ends_with($host, '.ddev.site') && str_contains($path, '/wp-content/uploads/')) {
            return false;
        }

        return $verify;
    }
}
