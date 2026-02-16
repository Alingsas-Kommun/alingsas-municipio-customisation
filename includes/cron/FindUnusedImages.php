<?php

namespace AlingsasCustomisation\Includes\Cron;

use WP_CLI;

/**
 * CLI command to find unreferenced images in WordPress media library
 *
 * Searches post content, post meta, and term meta for references to each
 * image attachment (by ID, filename, wp-image class, and serialized arrays).
 */
class FindUnusedImages
{
    private \wpdb $db;
    private array $images = [];
    private array $imageIds = [];

    /**
     * Find unreferenced images in the media library and generate a JSON report.
     *
     * Scans image attachments and checks whether they are referenced in
     * post content, post meta (including serialized ACF fields), or term meta.
     *
     * ## OPTIONS
     *
     * [--limit=<number>]
     * : Number of images to check. Use "all" to check every image.
     * ---
     * default: 10
     * ---
     *
     * [--output=<path>]
     * : Path to write the JSON report. Relative paths are resolved from the
     *   plugin's root directory.
     * ---
     * default: data/image-report-raw.json
     * ---
     *
     * [--batch-size=<number>]
     * : Number of images to process per batch.
     * ---
     * default: 50
     * ---
     *
     * [--fields-file=<path>]
     * : Path to ACF field definitions JSON file. When provided, the count of
     *   loaded fields is included in the report metadata.
     *
     * ## EXAMPLES
     *
     *     # Quick check of 10 most recent images
     *     wp alingsas find-unused-images
     *
     *     # Scan all images and save to a custom location
     *     wp alingsas find-unused-images --limit=all --output=/tmp/report.json
     *
     *     # Scan 500 images in batches of 100
     *     wp alingsas find-unused-images --limit=500 --batch-size=100
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function __invoke(array $args, array $assoc_args): void
    {
        global $wpdb;
        $this->db = $wpdb;

        $limitArg   = $assoc_args['limit'] ?? '10';
        $batchSize  = max(1, (int) ($assoc_args['batch-size'] ?? 50));
        $outputPath = $assoc_args['output'] ?? 'data/image-report-raw.json';
        $fieldsFile = $assoc_args['fields-file'] ?? null;

        // Resolve relative output path from plugin root
        if ($outputPath[0] !== '/') {
            $outputPath = dirname(__DIR__, 2) . '/' . $outputPath;
        }

        // ── Header ───────────────────────────────────────────
        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%B╔══════════════════════════════════════════════╗%n'));
        WP_CLI::log(WP_CLI::colorize('%B║  WordPress Unreferenced Image Finder         ║%n'));
        WP_CLI::log(WP_CLI::colorize('%B╚══════════════════════════════════════════════╝%n'));
        WP_CLI::log('');
        WP_CLI::log("  Database:     {$this->db->dbname}");
        WP_CLI::log("  Table prefix: {$this->db->prefix}");
        WP_CLI::log("  Image limit:  {$limitArg}");
        WP_CLI::log("  Batch size:   {$batchSize}");
        WP_CLI::log("  Output:       {$outputPath}");
        WP_CLI::log('');

        // ── Optional: ACF field definitions ──────────────────
        $acfFieldCount = 0;
        if ($fieldsFile) {
            if (!file_exists($fieldsFile)) {
                WP_CLI::warning("ACF fields file not found: {$fieldsFile} — skipping.");
            } else {
                $acfFields = json_decode(file_get_contents($fieldsFile), true);
                if (is_array($acfFields)) {
                    $acfFieldCount = count($acfFields);
                    $byType = [];
                    foreach ($acfFields as $f) {
                        $byType[$f['field_type']][] = $f['field_name'];
                    }
                    WP_CLI::log("Loaded {$acfFieldCount} ACF field definitions");
                    foreach ($byType as $type => $names) {
                        $unique = count(array_unique($names));
                        WP_CLI::log("  {$type}: {$unique} unique field names");
                    }
                    WP_CLI::log('');
                }
            }
        }

        // ── Step 1: Fetch image attachments ──────────────────
        WP_CLI::log(WP_CLI::colorize('%GStep 1:%n Fetching image attachments...'));

        $this->fetchImageAttachments($limitArg);
        $imageCount = count($this->images);

        WP_CLI::log("  Found {$imageCount} image attachments");
        WP_CLI::log('');

        if ($imageCount === 0) {
            WP_CLI::success('No images to check.');
            return;
        }

        // ── Step 2: Load metadata & build search patterns ────
        WP_CLI::log(WP_CLI::colorize('%GStep 2:%n Loading image metadata...'));

        $this->loadImageMetadata();

        $withFile = count(array_filter($this->images, fn($img) => $img['file'] !== null));
        WP_CLI::log("  Loaded metadata for {$withFile} / {$imageCount} images");
        WP_CLI::log('');

        // ── Step 3: Search for references ────────────────────
        $batches      = array_chunk($this->imageIds, $batchSize);
        $totalBatches = count($batches);
        $batchLabel   = $totalBatches === 1 ? 'batch' : 'batches';

        WP_CLI::log(WP_CLI::colorize("%GStep 3:%n Searching for references ({$totalBatches} {$batchLabel})..."));
        WP_CLI::log('');

        $startTime = microtime(true);

        foreach ($batches as $batchIdx => $batchIds) {
            $batchNum = $batchIdx + 1;
            $pct      = round($batchNum / $totalBatches * 100);
            $minId    = min($batchIds);
            $maxId    = max($batchIds);

            WP_CLI::log("  Batch {$batchNum}/{$totalBatches} [{$pct}%] — IDs {$minId}–{$maxId}");

            $counts = $this->searchBatchReferences($batchIds);

            foreach ($counts as $label => $c) {
                $padded = str_pad($label . ':', 26);
                WP_CLI::log("    {$padded}{$c}");
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        WP_CLI::log('');
        WP_CLI::log("Search completed in {$elapsed}s");
        WP_CLI::log('');

        // ── Step 4: Build report ─────────────────────────────
        WP_CLI::log(WP_CLI::colorize('%GStep 4:%n Building report...'));

        $report = $this->buildReport($elapsed, $limitArg, $acfFieldCount);

        // Ensure output directory exists
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            wp_mkdir_p($outputDir);
        }

        $written = file_put_contents(
            $outputPath,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($written === false) {
            WP_CLI::error("Failed to write report to: {$outputPath}");
            return;
        }

        // ── Summary ──────────────────────────────────────────
        $referenced   = $report['metadata']['total_referenced'];
        $unreferenced = $report['metadata']['total_unreferenced'];

        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%B╔══════════════════════════════════════════════╗%n'));
        WP_CLI::log(WP_CLI::colorize('%B║  Summary                                     ║%n'));
        WP_CLI::log(WP_CLI::colorize('%B╚══════════════════════════════════════════════╝%n'));
        WP_CLI::log('');
        WP_CLI::log("  Total images checked:  {$imageCount}");
        WP_CLI::log("  Referenced:            {$referenced}");
        WP_CLI::log("  Unreferenced:          {$unreferenced}");
        WP_CLI::log("  Search duration:       {$elapsed}s");
        WP_CLI::log('');
        WP_CLI::log("  Report saved to: {$outputPath}");

        if ($unreferenced > 0) {
            WP_CLI::log('');
            WP_CLI::log('  Unreferenced images:');
            foreach ($report['unreferenced_ids'] as $uid) {
                $file  = $this->images[$uid]['file'] ?? 'unknown';
                $title = $this->images[$uid]['title'];
                WP_CLI::log(WP_CLI::colorize("    %R✗%n ID {$uid} — {$file} — \"{$title}\""));
            }
        }

        if ($referenced > 0) {
            WP_CLI::log('');
            WP_CLI::log('  Referenced images (first 5):');
            $shown = 0;
            foreach ($this->images as $id => $img) {
                if (empty($img['references'])) {
                    continue;
                }
                if ($shown >= 5) {
                    break;
                }
                $file     = $img['file'] ?? 'unknown';
                $refCount = count($img['references']);
                $types    = implode(', ', array_unique(array_column($img['references'], 'type')));
                WP_CLI::log(WP_CLI::colorize("    %G✓%n ID {$id} — {$file} — {$refCount} ref(s) via {$types}"));
                $shown++;
            }
            if ($referenced > 5) {
                $more = $referenced - 5;
                WP_CLI::log("    … and {$more} more");
            }
        }

        WP_CLI::log('');
        WP_CLI::success('Done.');
    }

    // ================================================================
    // Step 1: Fetch Image Attachments
    // ================================================================

    private function fetchImageAttachments(string $limitArg): void
    {
        $limitClause = ($limitArg === 'all') ? '' : 'LIMIT ' . max(1, (int) $limitArg);

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "
            SELECT p.ID, p.post_title, p.post_mime_type, p.guid, p.post_date, p.post_parent
            FROM {$this->db->posts} p
            WHERE p.post_type = 'attachment'
              AND p.post_mime_type LIKE 'image/%%'
              AND NOT EXISTS (
                  SELECT 1 FROM {$this->db->postmeta} pm
                  WHERE pm.post_id = p.ID
                    AND pm.meta_key = 'event-manager-media'
                    AND pm.meta_value = '1'
              )
            ORDER BY p.ID DESC
            {$limitClause}
        ";
        // phpcs:enable

        $rows = $this->db->get_results($sql, ARRAY_A);

        foreach ($rows as $row) {
            $id = (int) $row['ID'];
            $this->images[$id] = [
                'attachment_id'    => $id,
                'title'            => $row['post_title'],
                'mime_type'        => $row['post_mime_type'],
                'guid'             => $row['guid'],
                'post_date'        => $row['post_date'],
                'post_parent'      => (int) $row['post_parent'],
                'file'             => null,
                'file_variants'    => [],
                'search_filenames' => [],
                'references'       => [],
            ];
        }

        $this->imageIds = array_keys($this->images);
    }

    // ================================================================
    // Step 2: Load Image Metadata & Build Search Patterns
    // ================================================================

    private function loadImageMetadata(): void
    {
        if (empty($this->imageIds)) {
            return;
        }

        $idPlaceholders = implode(',', array_fill(0, count($this->imageIds), '%d'));

        // _wp_attached_file
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT post_id, meta_value FROM {$this->db->postmeta}
                 WHERE meta_key = '_wp_attached_file' AND post_id IN ({$idPlaceholders})",
                ...$this->imageIds
            ),
            ARRAY_A
        );
        // phpcs:enable

        foreach ($rows as $row) {
            $id = (int) $row['post_id'];
            if (isset($this->images[$id])) {
                $this->images[$id]['file'] = $row['meta_value'];
            }
        }

        // _wp_attachment_metadata (thumbnails, sizes)
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT post_id, meta_value FROM {$this->db->postmeta}
                 WHERE meta_key = '_wp_attachment_metadata' AND post_id IN ({$idPlaceholders})",
                ...$this->imageIds
            ),
            ARRAY_A
        );
        // phpcs:enable

        foreach ($rows as $row) {
            $id = (int) $row['post_id'];
            if (!isset($this->images[$id])) {
                continue;
            }

            $meta = @unserialize($row['meta_value']);
            if (!is_array($meta)) {
                continue;
            }

            $dir = '';
            if (isset($meta['file'])) {
                $dir = dirname($meta['file']);
                $dir = ($dir === '.') ? '' : $dir . '/';
            }

            if (isset($meta['sizes']) && is_array($meta['sizes'])) {
                foreach ($meta['sizes'] as $sizeData) {
                    if (isset($sizeData['file'])) {
                        $this->images[$id]['file_variants'][] = $dir . $sizeData['file'];
                    }
                }
            }

            if (isset($meta['original_image'])) {
                $this->images[$id]['file_variants'][] = $dir . $meta['original_image'];
            }
        }

        // Build search filenames per image
        foreach ($this->images as $id => &$img) {
            $filenames = [];
            if ($img['file']) {
                $filenames[] = $img['file'];
                $filenames[] = basename($img['file']);
            }
            foreach ($img['file_variants'] as $v) {
                $filenames[] = $v;
                $filenames[] = basename($v);
            }
            $img['search_filenames'] = array_values(array_unique($filenames));
        }
        unset($img);
    }

    // ================================================================
    // Step 3: Search for References (single batch)
    // ================================================================

    /**
     * @param int[] $batchIds
     * @return array<string, int> Counts keyed by search type label.
     */
    private function searchBatchReferences(array $batchIds): array
    {
        $counts = [];

        // Sanitised ID list for raw SQL (all values are already int-cast)
        $idList = implode(',', array_map('intval', $batchIds));
        $idStrList = "'" . implode("','", array_map('intval', $batchIds)) . "'";

        // ── 3a. Direct ID match in postmeta ──────────────────
        $count = 0;
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $this->db->get_results(
            "SELECT post_id, meta_key, meta_value
             FROM {$this->db->postmeta}
             WHERE meta_value IN ({$idStrList})",
            ARRAY_A
        );
        // phpcs:enable

        foreach ($rows as $row) {
            $refId  = (int) $row['meta_value'];
            $postId = (int) $row['post_id'];
            if (isset($this->images[$refId]) && $postId !== $refId) {
                $this->images[$refId]['references'][] = [
                    'type'         => 'postmeta_id',
                    'source_table' => 'postmeta',
                    'source_id'    => $postId,
                    'meta_key'     => $row['meta_key'],
                ];
                $count++;
            }
        }
        $counts['postmeta (ID)'] = $count;

        // ── 3b. Direct ID match in termmeta ──────────────────
        $count = 0;
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $this->db->get_results(
            "SELECT term_id, meta_key, meta_value
             FROM {$this->db->termmeta}
             WHERE meta_value IN ({$idStrList})",
            ARRAY_A
        );
        // phpcs:enable

        foreach ($rows as $row) {
            $refId = (int) $row['meta_value'];
            if (isset($this->images[$refId])) {
                $this->images[$refId]['references'][] = [
                    'type'         => 'termmeta_id',
                    'source_table' => 'termmeta',
                    'source_id'    => (int) $row['term_id'],
                    'meta_key'     => $row['meta_key'],
                ];
                $count++;
            }
        }
        $counts['termmeta (ID)'] = $count;

        // ── 3c. Serialized arrays in postmeta ────────────────
        $orParts = [];
        foreach ($batchIds as $id) {
            $orParts[] = "meta_value LIKE '%i:{$id};%'";
            $orParts[] = "meta_value LIKE '%\"{$id}\"%'";
        }

        $count = 0;
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $this->db->get_results(
            "SELECT post_id, meta_key, meta_value
             FROM {$this->db->postmeta}
             WHERE meta_value LIKE 'a:%'
               AND (" . implode(' OR ', $orParts) . ")",
            ARRAY_A
        );
        // phpcs:enable

        foreach ($rows as $row) {
            $postId = (int) $row['post_id'];
            $data   = @unserialize($row['meta_value']);
            if (!is_array($data)) {
                continue;
            }

            $flat = [];
            array_walk_recursive($data, function ($v) use (&$flat) {
                $flat[] = $v;
            });

            foreach ($batchIds as $id) {
                if (!isset($this->images[$id]) || $postId === $id) {
                    continue;
                }
                if (in_array($id, $flat) || in_array((string) $id, $flat)) {
                    $this->images[$id]['references'][] = [
                        'type'         => 'postmeta_serialized',
                        'source_table' => 'postmeta',
                        'source_id'    => $postId,
                        'meta_key'     => $row['meta_key'],
                    ];
                    $count++;
                }
            }
        }
        $counts['postmeta (serialized)'] = $count;

        // ── 3d. Serialized arrays in termmeta ────────────────
        $count = 0;
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $this->db->get_results(
            "SELECT term_id, meta_key, meta_value
             FROM {$this->db->termmeta}
             WHERE meta_value LIKE 'a:%'
               AND (" . implode(' OR ', $orParts) . ")",
            ARRAY_A
        );
        // phpcs:enable

        foreach ($rows as $row) {
            $termId = (int) $row['term_id'];
            $data   = @unserialize($row['meta_value']);
            if (!is_array($data)) {
                continue;
            }

            $flat = [];
            array_walk_recursive($data, function ($v) use (&$flat) {
                $flat[] = $v;
            });

            foreach ($batchIds as $id) {
                if (!isset($this->images[$id])) {
                    continue;
                }
                if (in_array($id, $flat) || in_array((string) $id, $flat)) {
                    $this->images[$id]['references'][] = [
                        'type'         => 'termmeta_serialized',
                        'source_table' => 'termmeta',
                        'source_id'    => $termId,
                        'meta_key'     => $row['meta_key'],
                    ];
                    $count++;
                }
            }
        }
        $counts['termmeta (serialized)'] = $count;

        // ── Build filename search conditions ─────────────────
        $contentConditions = [];
        $batchStems        = [];

        foreach ($batchIds as $id) {
            $contentConditions[] = "post_content LIKE '%wp-image-{$id}%'";
        }

        foreach ($batchIds as $id) {
            if (!isset($this->images[$id]) || !$this->images[$id]['file']) {
                continue;
            }
            $stem = pathinfo(basename($this->images[$id]['file']), PATHINFO_FILENAME);
            if ($stem && strlen($stem) >= 3) {
                $escaped = $this->db->_real_escape($stem);
                $batchStems[$stem] = true;
                $contentConditions[] = "post_content LIKE '%{$escaped}%'";
            }
        }
        $contentConditions = array_unique($contentConditions);

        // ── 3e. Filenames & wp-image-ID in post_content ──────
        $count = 0;
        if (!empty($contentConditions)) {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows = $this->db->get_results(
                "SELECT ID, post_content
                 FROM {$this->db->posts}
                 WHERE post_type NOT IN ('attachment','revision')
                   AND (" . implode(' OR ', $contentConditions) . ")",
                ARRAY_A
            );
            // phpcs:enable

            foreach ($rows as $row) {
                $content  = $row['post_content'];
                $sourceId = (int) $row['ID'];

                foreach ($batchIds as $id) {
                    if (!isset($this->images[$id])) {
                        continue;
                    }
                    $found = false;

                    // wp-image-{ID} CSS class
                    if (strpos($content, "wp-image-{$id}") !== false) {
                        $this->images[$id]['references'][] = [
                            'type'            => 'post_content_id',
                            'source_table'    => 'posts',
                            'source_id'       => $sourceId,
                            'matched_pattern' => "wp-image-{$id}",
                        ];
                        $count++;
                        $found = true;
                    }

                    // Actual filenames (original + variants)
                    if (!$found) {
                        foreach ($this->images[$id]['search_filenames'] as $fn) {
                            if (stripos($content, $fn) !== false) {
                                $this->images[$id]['references'][] = [
                                    'type'             => 'post_content_filename',
                                    'source_table'     => 'posts',
                                    'source_id'        => $sourceId,
                                    'matched_filename' => $fn,
                                ];
                                $count++;
                                break;
                            }
                        }
                    }
                }
            }
        }
        $counts['post_content'] = $count;

        // ── 3f. Filenames in postmeta ────────────────────────
        $metaConditions = [];
        foreach (array_keys($batchStems) as $stem) {
            $escaped = $this->db->_real_escape($stem);
            $metaConditions[] = "meta_value LIKE '%{$escaped}%'";
        }

        $count = 0;
        if (!empty($metaConditions)) {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows = $this->db->get_results(
                "SELECT post_id, meta_key, meta_value
                 FROM {$this->db->postmeta}
                 WHERE meta_key NOT LIKE '\_%'
                   AND LENGTH(meta_value) > 20
                   AND (" . implode(' OR ', $metaConditions) . ")",
                ARRAY_A
            );
            // phpcs:enable

            foreach ($rows as $row) {
                $postId    = (int) $row['post_id'];
                $metaValue = $row['meta_value'];

                foreach ($batchIds as $id) {
                    if (!isset($this->images[$id]) || $postId === $id) {
                        continue;
                    }
                    foreach ($this->images[$id]['search_filenames'] as $fn) {
                        if (stripos($metaValue, $fn) !== false) {
                            $this->images[$id]['references'][] = [
                                'type'             => 'postmeta_filename',
                                'source_table'     => 'postmeta',
                                'source_id'        => $postId,
                                'meta_key'         => $row['meta_key'],
                                'matched_filename' => $fn,
                            ];
                            $count++;
                            break;
                        }
                    }
                }
            }
        }
        $counts['postmeta (filename)'] = $count;

        // ── 3g. Filenames in termmeta ────────────────────────
        $count = 0;
        if (!empty($metaConditions)) {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows = $this->db->get_results(
                "SELECT term_id, meta_key, meta_value
                 FROM {$this->db->termmeta}
                 WHERE (" . implode(' OR ', $metaConditions) . ")",
                ARRAY_A
            );
            // phpcs:enable

            foreach ($rows as $row) {
                $termId    = (int) $row['term_id'];
                $metaValue = $row['meta_value'];

                foreach ($batchIds as $id) {
                    if (!isset($this->images[$id])) {
                        continue;
                    }
                    foreach ($this->images[$id]['search_filenames'] as $fn) {
                        if (stripos($metaValue, $fn) !== false) {
                            $this->images[$id]['references'][] = [
                                'type'             => 'termmeta_filename',
                                'source_table'     => 'termmeta',
                                'source_id'        => $termId,
                                'meta_key'         => $row['meta_key'],
                                'matched_filename' => $fn,
                            ];
                            $count++;
                            break;
                        }
                    }
                }
            }
        }
        $counts['termmeta (filename)'] = $count;

        return $counts;
    }

    // ================================================================
    // Step 4: Build Report
    // ================================================================

    private function buildReport(float $elapsed, string $limitArg, int $acfFieldCount): array
    {
        // Deduplicate references per image
        foreach ($this->images as &$img) {
            $seen   = [];
            $unique = [];
            foreach ($img['references'] as $ref) {
                $key = json_encode($ref);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique[]   = $ref;
                }
            }
            $img['references'] = $unique;
        }
        unset($img);

        $referenced      = 0;
        $unreferenced    = 0;
        $unreferencedIds = [];
        $reportImages    = [];

        foreach ($this->images as $id => $img) {
            $isReferenced = !empty($img['references']);
            if ($isReferenced) {
                $referenced++;
            } else {
                $unreferenced++;
                $unreferencedIds[] = $id;
            }

            $reportImages[] = [
                'attachment_id'   => $img['attachment_id'],
                'title'           => $img['title'],
                'file'            => $img['file'],
                'mime_type'       => $img['mime_type'],
                'guid'            => $img['guid'],
                'post_date'       => $img['post_date'],
                'post_parent'     => $img['post_parent'],
                'file_variants'   => $img['file_variants'],
                'referenced'      => $isReferenced,
                'reference_count' => count($img['references']),
                'references'      => $img['references'],
            ];
        }

        return [
            'metadata' => [
                'generated_at'            => date('c'),
                'database'                => $this->db->dbname,
                'table_prefix'            => $this->db->prefix,
                'limit_used'              => $limitArg,
                'total_images_checked'    => count($this->images),
                'total_referenced'        => $referenced,
                'total_unreferenced'      => $unreferenced,
                'search_duration_seconds' => $elapsed,
                'acf_fields_loaded'       => $acfFieldCount,
            ],
            'unreferenced_ids' => $unreferencedIds,
            'images'           => $reportImages,
        ];
    }
}

/**
 * Register the WP-CLI command.
 */
if (defined('WP_CLI') && constant('WP_CLI') === true) {
    WP_CLI::add_command('alingsas find-unused-images', FindUnusedImages::class);
}