<?php

namespace AlingsasCustomisation\Includes\Cron;

use RuntimeException;
use WP_CLI;

/**
 * Shared safeguards and scan history. No hooks or side effects
 * when WordPress loads this file for a normal web request.
 */
trait MediaScanSafety
{
    private string $scanHistoryMetaKey = '_alingsas_media_last_scanned_at';
    private int $scanRecheckSeconds = 7 * 24 * 60 * 60;

    /** @var resource|null */
    private $scanLock = null;
    private int $scanQueryCount = 0;
    private float $scanQuerySeconds = 0.0;
    private int $scanStartedAt = 0;
    private bool $forceScan = false;

    private function configureScanHistory(array $args): void
    {
        $this->scanStartedAt = time();
        $this->forceScan = !empty($args['force']);
        WP_CLI::log($this->forceScan
            ? 'Force: checking attachments regardless of previous scans.'
            : 'Skipping attachments checked within the last seven days (use --force to recheck).');
    }

    /** Filter before LIMIT so recently checked attachments do not use up slots. */
    private function scanEligibilitySql(): string
    {
        if ($this->forceScan) {
            return '';
        }
        return $this->db->prepare(
            "AND NOT EXISTS (
                SELECT 1 FROM {$this->db->postmeta} scan_history
                WHERE scan_history.post_id = p.ID
                  AND scan_history.meta_key = %s
                  AND CAST(scan_history.meta_value AS UNSIGNED) > %d
            )",
            $this->scanHistoryMetaKey,
            $this->scanStartedAt - $this->scanRecheckSeconds
        );
    }

    /** Only called after the complete report has been published successfully. */
    private function rememberScannedAttachments(array $ids): void
    {
        // Use the start time so a weekly job is eligible again at its next start.
        $timestamp = (string) $this->scanStartedAt;
        foreach ($ids as $id) {
            $updated = update_post_meta($id, $this->scanHistoryMetaKey, $timestamp);
            $error = $this->db->last_error;
            // WordPress also returns false when the timestamp is unchanged,
            // e.g. a forced recheck during the same second.
            if ($error !== '' || ($updated === false && get_post_meta($id, $this->scanHistoryMetaKey, true) !== $timestamp)) {
                throw new RuntimeException(
                    "Report saved, but scan history could not be saved for attachment {$id}. " .
                    'Attachments without saved history will be checked again. ' . $error
                );
            }
        }
        WP_CLI::log(sprintf('Saved scan history for %d attachments.', count($ids)));
    }

    private function scanIntegerOption(array $args, string $name, int $default, int $min = 1, int $max = PHP_INT_MAX): int
    {
        $value = filter_var($args[$name] ?? $default, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max],
        ]);
        if ($value === false) {
            throw new RuntimeException("--{$name} must be an integer between {$min} and {$max}.");
        }
        return $value;
    }

    /** @return int[] */
    private function scanAttachmentIds(string $value): array
    {
        $ids = [];
        foreach (preg_split('/\s*,\s*/', trim($value)) as $id) {
            $ids[] = $this->scanIntegerOption(['ids' => $id], 'ids', 1);
        }
        return array_values(array_unique($ids));
    }

    private function acquireScanLock(): void
    {
        // Both media types (and sites sharing this database) use the same lock
        // on this host. Keep the file after unlocking to avoid inode races.
        $database = (defined('DB_HOST') ? DB_HOST : '') . ':' . $this->db->dbname;
        $path = sys_get_temp_dir() . '/alingsas-media-scan-' . hash('sha256', $database) . '.lock';
        $handle = @fopen($path, 'c');
        if ($handle === false) {
            throw new RuntimeException("Cannot open media scan lock: {$path}");
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RuntimeException('Cannot acquire media scan lock. Another image/PDF scan may already be running.');
        }
        $this->scanLock = $handle;
        $this->scanQueryCount = 0;
        $this->scanQuerySeconds = 0.0;
        WP_CLI::log('Media scan lock acquired.');
    }

    private function releaseScanLock(): void
    {
        if (is_resource($this->scanLock)) {
            flock($this->scanLock, LOCK_UN);
            fclose($this->scanLock);
        }
        $this->scanLock = null;
    }

    /**
     * wpdb can return [] for both no matches and a failed query. Never turn a
     * failed reference lookup into an "unreferenced" classification.
     */
    private function scanResults(string $sql, string $output = ARRAY_A): array
    {
        if (trim($sql) === '') {
            throw new RuntimeException('Empty database query. No new report was published.');
        }
        $started = microtime(true);
        $rows = $this->db->get_results($sql, $output);
        $seconds = microtime(true) - $started;
        $this->scanQueryCount++;
        $this->scanQuerySeconds += $seconds;

        if ($this->db->last_error !== '' || !is_array($rows)) {
            throw new RuntimeException(sprintf(
                'Database query #%d failed: %s. No new report was published.',
                $this->scanQueryCount,
                $this->db->last_error ?: 'No valid result returned'
            ));
        }
        if ($seconds >= 5) {
            WP_CLI::warning(sprintf('Database query #%d took %.2fs.', $this->scanQueryCount, $seconds));
        }
        return $rows;
    }

    private function prepareScanOutput(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !wp_mkdir_p($directory)) {
            throw new RuntimeException("Cannot create report directory: {$directory}");
        }
        if (!is_writable($directory) || is_dir($path)) {
            throw new RuntimeException("Report path is not writable: {$path}");
        }
        if (file_exists($path)) {
            WP_CLI::warning('An existing report will only be replaced after a successful scan. Check its timestamp before using it.');
        }
    }

    private function writeScanReport(string $path, array $report): void
    {
        $report['metadata']['scan_complete'] = true;
        $report['metadata']['scan_started_at'] = gmdate('c', $this->scanStartedAt);
        $report['metadata']['recheck_after_seconds'] = $this->scanRecheckSeconds;
        $report['metadata']['force_used'] = $this->forceScan;
        $report['metadata']['database_query_count'] = $this->scanQueryCount;
        $report['metadata']['database_query_seconds'] = round($this->scanQuerySeconds, 2);
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        // Write alongside the destination so rename is atomic on the same FS.
        $temporary = tempnam(dirname($path), '.media-scan-');
        if ($temporary === false) {
            throw new RuntimeException("Cannot create temporary report for: {$path}");
        }
        try {
            if (file_put_contents($temporary, $json) !== strlen($json)) {
                throw new RuntimeException("Failed to write complete report: {$path}");
            }
            if (!rename($temporary, $path)) {
                throw new RuntimeException("Failed to publish report: {$path}");
            }
        } finally {
            if (file_exists($temporary)) {
                unlink($temporary);
            }
        }
    }

    private function logScanBatch(float $started): void
    {
        WP_CLI::log(sprintf(
            '    Batch: %.2fs | SQL total: %.2fs (%d queries) | Memory: %.1f MiB, peak: %.1f MiB',
            microtime(true) - $started,
            $this->scanQuerySeconds,
            $this->scanQueryCount,
            memory_get_usage(true) / 1048576,
            memory_get_peak_usage(true) / 1048576
        ));
    }
}
