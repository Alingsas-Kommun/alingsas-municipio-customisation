<?php

namespace AlingsasCustomisation\Includes\Cron;

use WP_CLI;

/**
 * CLI command to mark unused PDF files in WordPress media library for later review
 */
class MarkUnusedPdfs
{
    const META_KEY = '_marked_unused';
    const META_VALUE = '1';

    /**
     * Mark unreferenced PDF files from the media library based on a JSON report
     *
     * ## OPTIONS
     *
     * <json-file>
     * : Path to the JSON report file containing unreferenced PDFs
     *
     * [--dry-run]
     * : Preview what would be marked without actually marking
     *
     * [--verbose]
     * : Show detailed progress information for each PDF
     *
     * [--unmark]
     * : Remove the unused marker from PDFs instead of adding it
     *
     * ## EXAMPLES
     *
     *     # Dry run to see what would be marked
     *     wp alingsas mark-unused-pdfs data/pdf-report.json --dry-run
     *
     *     # Mark PDFs with progress bar
     *     wp alingsas mark-unused-pdfs data/pdf-report.json
     *
     *     # Mark PDFs with verbose output
     *     wp alingsas mark-unused-pdfs data/pdf-report.json --verbose
     *
     *     # Unmark previously marked PDFs
     *     wp alingsas mark-unused-pdfs data/pdf-report.json --unmark
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function __invoke($args, $assoc_args)
    {
        list($json_file) = $args;

        $dry_run = isset($assoc_args['dry-run']);
        $verbose = isset($assoc_args['verbose']);
        $unmark  = isset($assoc_args['unmark']);

        // Validate file exists
        if (!file_exists($json_file)) {
            WP_CLI::error("JSON file not found: {$json_file}");
            return;
        }

        // Load and parse JSON
        WP_CLI::log("Loading JSON report from: {$json_file}");
        $json_content = file_get_contents($json_file);
        $data         = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            WP_CLI::error("Failed to parse JSON: " . json_last_error_msg());
            return;
        }

        // Validate JSON structure
        if (!isset($data['final_unreferenced_pdfs']) || !is_array($data['final_unreferenced_pdfs'])) {
            WP_CLI::error("Invalid JSON structure: 'final_unreferenced_pdfs' not found or not an array");
            return;
        }

        $pdfs       = $data['final_unreferenced_pdfs'];
        $total_pdfs = count($pdfs);

        if ($total_pdfs === 0) {
            WP_CLI::success("No unreferenced PDFs found in the report.");
            return;
        }

        // Display summary
        WP_CLI::log("");
        WP_CLI::log(WP_CLI::colorize("%B=== Summary ===%n"));
        WP_CLI::log("Total unreferenced PDFs: " . WP_CLI::colorize("%Y{$total_pdfs}%n"));

        if ($dry_run) {
            WP_CLI::warning("DRY RUN MODE: No PDFs will be " . ($unmark ? "unmarked" : "marked"));
        }

        if ($unmark) {
            WP_CLI::log(WP_CLI::colorize("%CMode: UNMARK%n - Removing unused markers from PDFs"));
        } else {
            WP_CLI::log(WP_CLI::colorize("%CMode: MARK%n - Adding unused markers to PDFs"));
        }

        WP_CLI::log("");

        // Process PDFs
        $processed_count        = 0;
        $failed_count           = 0;
        $not_found_count        = 0;
        $already_processed_count = 0;
        $failed_pdfs            = [];

        // Initialize progress bar if not verbose
        if (!$verbose) {
            $action = $unmark ? 'Unmarking PDFs' : 'Marking PDFs';
            if ($dry_run) {
                $action = 'Analyzing PDFs';
            }
            $progress = \WP_CLI\Utils\make_progress_bar($action, $total_pdfs);
        }

        foreach ($pdfs as $index => $pdf) {
            if (!isset($pdf['attachment_id'])) {
                if ($verbose) {
                    WP_CLI::warning("Skipping PDF without attachment_id at index {$index}");
                }
                $failed_count++;
                continue;
            }

            $attachment_id = $pdf['attachment_id'];
            $title         = $pdf['title'] ?? 'Untitled';
            $file_path     = $pdf['file'] ?? 'Unknown path';

            // Check if attachment exists
            $post = get_post($attachment_id);
            if (!$post || $post->post_type !== 'attachment') {
                if ($verbose) {
                    WP_CLI::log(WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %RNot found%n: ID {$attachment_id} - {$title}"));
                }
                $not_found_count++;

                if (!$verbose) {
                    $progress->tick();
                }
                continue;
            }

            // Check current state
            $current_value = get_post_meta($attachment_id, self::META_KEY, true);
            $is_marked     = ($current_value === self::META_VALUE);

            if ($dry_run) {
                if ($unmark) {
                    if ($is_marked) {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %CWould unmark%n: ID {$attachment_id} - {$title}")
                            );
                            WP_CLI::log("  Path: {$file_path}");
                        }
                        $processed_count++;
                    } else {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %YAlready unmarked%n: ID {$attachment_id} - {$title}")
                            );
                        }
                        $already_processed_count++;
                    }
                } else {
                    if (!$is_marked) {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %CWould mark%n: ID {$attachment_id} - {$title}")
                            );
                            WP_CLI::log("  Path: {$file_path}");
                        }
                        $processed_count++;
                    } else {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %YAlready marked%n: ID {$attachment_id} - {$title}")
                            );
                        }
                        $already_processed_count++;
                    }
                }
            } else {
                // Actually mark/unmark the attachment
                if ($unmark) {
                    if ($is_marked) {
                        $result = delete_post_meta($attachment_id, self::META_KEY);

                        if ($result) {
                            if ($verbose) {
                                WP_CLI::log(
                                    WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %GUnmarked%n: ID {$attachment_id} - {$title}")
                                );
                                WP_CLI::log("  Path: {$file_path}");
                            }
                            $processed_count++;
                        } else {
                            if ($verbose) {
                                WP_CLI::log(
                                    WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %RFailed to unmark%n: ID {$attachment_id} - {$title}")
                                );
                                WP_CLI::log("  Path: {$file_path}");
                            }
                            $failed_count++;
                            $failed_pdfs[] = [
                                'id'    => $attachment_id,
                                'title' => $title,
                                'path'  => $file_path,
                            ];
                        }
                    } else {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %YAlready unmarked%n: ID {$attachment_id} - {$title}")
                            );
                        }
                        $already_processed_count++;
                    }
                } else {
                    if (!$is_marked) {
                        $result = update_post_meta($attachment_id, self::META_KEY, self::META_VALUE);

                        if ($result) {
                            if ($verbose) {
                                WP_CLI::log(
                                    WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %GMarked%n: ID {$attachment_id} - {$title}")
                                );
                                WP_CLI::log("  Path: {$file_path}");
                            }
                            $processed_count++;
                        } else {
                            if ($verbose) {
                                WP_CLI::log(
                                    WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %RFailed to mark%n: ID {$attachment_id} - {$title}")
                                );
                                WP_CLI::log("  Path: {$file_path}");
                            }
                            $failed_count++;
                            $failed_pdfs[] = [
                                'id'    => $attachment_id,
                                'title' => $title,
                                'path'  => $file_path,
                            ];
                        }
                    } else {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_pdfs}%n] %YAlready marked%n: ID {$attachment_id} - {$title}")
                            );
                        }
                        $already_processed_count++;
                    }
                }
            }

            if (!$verbose) {
                $progress->tick();
            }
        }

        // Finish progress bar
        if (!$verbose) {
            $progress->finish();
        }

        // Display results
        WP_CLI::log("");
        WP_CLI::log(WP_CLI::colorize("%B=== Results ===%n"));

        if ($dry_run) {
            $action = $unmark ? "Would unmark" : "Would mark";
            WP_CLI::log("{$action}: " . WP_CLI::colorize("%G{$processed_count}%n") . " PDFs");
        } else {
            $action = $unmark ? "Successfully unmarked" : "Successfully marked";
            WP_CLI::log("{$action}: " . WP_CLI::colorize("%G{$processed_count}%n") . " PDFs");
        }

        if ($already_processed_count > 0) {
            $status = $unmark ? "already unmarked" : "already marked";
            WP_CLI::log("Skipped ({$status}): " . WP_CLI::colorize("%Y{$already_processed_count}%n") . " PDFs");
        }

        if ($not_found_count > 0) {
            WP_CLI::log("Not found in database: " . WP_CLI::colorize("%Y{$not_found_count}%n") . " PDFs");
        }

        if ($failed_count > 0) {
            WP_CLI::log("Failed: " . WP_CLI::colorize("%R{$failed_count}%n") . " PDFs");

            if (!empty($failed_pdfs)) {
                WP_CLI::log("");
                WP_CLI::log(WP_CLI::colorize("%RFailed PDFs:%n"));
                foreach ($failed_pdfs as $failed) {
                    WP_CLI::log("  - ID {$failed['id']}: {$failed['title']} ({$failed['path']})");
                }
            }
        }

        WP_CLI::log("");

        if ($dry_run) {
            WP_CLI::success("Dry run completed. Run without --dry-run to actually " . ($unmark ? "unmark" : "mark") . " PDFs.");
        } else {
            WP_CLI::success("PDF " . ($unmark ? "unmarking" : "marking") . " completed!");

            if (!$unmark && $processed_count > 0) {
                WP_CLI::log("");
                WP_CLI::log(WP_CLI::colorize("%BNext steps:%n"));
                WP_CLI::log("1. Go to the Media Library in WordPress admin");
                WP_CLI::log("2. Use the filter to show only marked unused PDFs");
                WP_CLI::log("3. Review and delete PDFs manually as needed");
                WP_CLI::log("");
                WP_CLI::log("To unmark PDFs, run: wp alingsas mark-unused-pdfs {$json_file} --unmark");
            }
        }
    }
}

/**
 * Register the WP-CLI command
 */
if (defined('WP_CLI') && constant('WP_CLI') === true) {
    WP_CLI::add_command('alingsas mark-unused-pdfs', MarkUnusedPdfs::class);
}
