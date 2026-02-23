<?php

namespace AlingsasCustomisation\Includes\Cron;

use WP_CLI;

/**
 * CLI command to cross-reference unreferenced PDFs against markdown exports
 *
 * Takes the raw PDF report and scans a directory of markdown files for
 * references. Produces a final report of PDFs that are truly unreferenced
 * (not found in markdown either), ready for deletion.
 */
class CheckUnusedPdfs
{
    /**
     * Check unreferenced PDFs against exported markdown files.
     *
     * Reads the raw PDF report, scans a directory of markdown files for
     * references to unreferenced PDFs, and produces a final report with
     * only the PDFs that remain unreferenced after the markdown scan.
     *
     * ## OPTIONS
     *
     * [--report=<path>]
     * : Path to the raw PDF report JSON file. Relative paths are resolved
     *   from the plugin's root directory.
     * ---
     * default: data/pdf-report-raw.json
     * ---
     *
     * [--markdown-dir=<path>]
     * : Path to the directory containing markdown export files. Relative paths
     *   are resolved from the plugin's root directory.
     * ---
     * default: worddown-export
     * ---
     *
     * [--output=<path>]
     * : Path to write the final report JSON. Relative paths are resolved from
     *   the plugin's root directory.
     * ---
     * default: data/pdf-report.json
     * ---
     *
     * ## EXAMPLES
     *
     *     # Check with default paths
     *     wp alingsas check-unused-pdfs
     *
     *     # Specify custom report and markdown directory
     *     wp alingsas check-unused-pdfs --report=data/pdf-report-raw.json --markdown-dir=/tmp/markdown-export
     *
     *     # Save output to a custom location
     *     wp alingsas check-unused-pdfs --output=data/final-pdf-report.json
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function __invoke(array $args, array $assoc_args): void
    {
        $reportPath  = $assoc_args['report'] ?? 'data/pdf-report-raw.json';
        $markdownDir = $assoc_args['markdown-dir'] ?? 'worddown-export';
        $outputPath  = $assoc_args['output'] ?? 'data/pdf-report.json';
        $pluginRoot  = dirname(__DIR__, 2);

        // Resolve relative paths from plugin root
        if ($reportPath[0] !== '/') {
            $reportPath = $pluginRoot . '/' . $reportPath;
        }
        if ($markdownDir[0] !== '/') {
            $markdownDir = $pluginRoot . '/' . $markdownDir;
        }
        if ($outputPath[0] !== '/') {
            $outputPath = $pluginRoot . '/' . $outputPath;
        }

        // ── Header ───────────────────────────────────────────
        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%BCheck Unused PDFs Against Markdown Export%n'));
        WP_CLI::log(str_repeat('─', 50));

        // ── Load raw report ──────────────────────────────────
        if (!file_exists($reportPath)) {
            WP_CLI::error("Raw report not found: $reportPath");
        }

        $report = json_decode(file_get_contents($reportPath), true);

        if (!isset($report['pdfs']) || !is_array($report['pdfs'])) {
            WP_CLI::error("Invalid report structure — missing 'pdfs' array in: $reportPath");
        }

        // Filter to only unreferenced PDFs
        $unreferencedPdfs = array_values(array_filter($report['pdfs'], function ($pdf) {
            return empty($pdf['referenced']);
        }));

        WP_CLI::log(sprintf('Unreferenced PDFs in raw report: %d', count($unreferencedPdfs)));

        // ── Collect markdown files ───────────────────────────
        if (!is_dir($markdownDir)) {
            WP_CLI::error("Markdown directory not found: $markdownDir");
        }

        $markdownFiles = $this->collectMarkdownFiles($markdownDir);
        WP_CLI::log(sprintf('Markdown files found: %d', count($markdownFiles)));
        WP_CLI::log('');

        // ── Search for references in markdown ────────────────
        $foundIds = $this->searchMarkdownForReferences($unreferencedPdfs, $markdownFiles, $markdownDir);

        // ── Build final unreferenced list ────────────────────
        $finalUnreferenced = array_values(array_filter($unreferencedPdfs, function ($pdf) use ($foundIds) {
            $id = isset($pdf['attachment_id']) ? (int) $pdf['attachment_id'] : null;
            if ($id === null) {
                return false;
            }
            return !in_array($id, $foundIds, true);
        }));

        // ── Build report ─────────────────────────────────────
        $result = [
            'summary' => [
                'original_unreferenced_count' => count($unreferencedPdfs),
                'found_in_markdown_count'     => count($foundIds),
                'final_unreferenced_count'    => count($finalUnreferenced),
            ],
            'found_in_markdown_ids'     => array_values($foundIds),
            'final_unreferenced_pdfs'   => $finalUnreferenced,
        ];

        // ── Write output ─────────────────────────────────────
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $written = file_put_contents(
            $outputPath,
            json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($written === false) {
            WP_CLI::error("Failed to write final report to: $outputPath");
        }

        // ── Summary ──────────────────────────────────────────
        WP_CLI::log('');
        WP_CLI::log(str_repeat('─', 50));
        WP_CLI::log(sprintf('Original unreferenced:  %d', count($unreferencedPdfs)));
        WP_CLI::log(sprintf('Found in markdown:      %d', count($foundIds)));
        WP_CLI::log(sprintf('Final unreferenced:     %d', count($finalUnreferenced)));
        WP_CLI::log('');
        WP_CLI::success("Report written to: $outputPath");
    }

    /**
     * Recursively collect all markdown files from a directory.
     *
     * @param string $directory Path to the directory.
     * @return string[] Array of absolute file paths.
     */
    private function collectMarkdownFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(md|markdown)$/i', $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Search markdown files for references to unreferenced PDFs.
     *
     * For each unreferenced PDF, builds search patterns (full path and
     * basename) and checks all markdown files for matches.
     *
     * @param array    $unreferencedPdfs The unreferenced PDFs from the raw report.
     * @param string[] $markdownFiles    Absolute paths to markdown files.
     * @param string   $markdownDir      Base directory for relative path display.
     * @return int[] Array of attachment IDs found in markdown.
     */
    private function searchMarkdownForReferences(array $unreferencedPdfs, array $markdownFiles, string $markdownDir): array
    {
        // Pre-load all markdown file contents to avoid re-reading per PDF
        $markdownContents = [];
        foreach ($markdownFiles as $mdFile) {
            $markdownContents[$mdFile] = file_get_contents($mdFile);
        }

        $foundIds = [];
        $total    = count($unreferencedPdfs);
        $checked  = 0;

        foreach ($unreferencedPdfs as $pdf) {
            $checked++;
            $id = (int) $pdf['attachment_id'];

            $patterns = $this->buildSearchPatterns($pdf);

            foreach ($markdownContents as $mdFile => $content) {
                foreach ($patterns as $pattern) {
                    if (strlen($pattern) >= 3 && stripos($content, $pattern) !== false) {
                        $relativePath = str_replace($markdownDir . '/', '', $mdFile);
                        WP_CLI::log(sprintf(
                            '  ✓ Found ID %d (%s) in %s',
                            $id,
                            $pdf['file'] ?? 'unknown',
                            $relativePath
                        ));
                        $foundIds[] = $id;
                        break 2; // Move to next PDF
                    }
                }
            }

            // Progress indicator every 50 PDFs
            if ($checked % 50 === 0) {
                WP_CLI::log(sprintf('  Checked %d / %d PDFs...', $checked, $total));
            }
        }

        return array_values(array_unique($foundIds));
    }

    /**
     * Build search patterns for a single PDF.
     *
     * @param array $pdf PDF data from the raw report.
     * @return string[] Unique search patterns.
     */
    private function buildSearchPatterns(array $pdf): array
    {
        $patterns = [];

        if (!empty($pdf['file'])) {
            // Full relative path (e.g. 2025/12/myfile.pdf)
            $patterns[] = $pdf['file'];
            // Basename only (e.g. myfile.pdf)
            $patterns[] = basename($pdf['file']);
        }

        return array_values(array_filter(array_unique($patterns)));
    }
}

/**
 * Register the WP-CLI command.
 */
if (defined('WP_CLI') && constant('WP_CLI') === true) {
    WP_CLI::add_command('alingsas check-unused-pdfs', CheckUnusedPdfs::class);
}
