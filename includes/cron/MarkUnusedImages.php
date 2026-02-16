<?php

namespace AlingsasCustomisation\Includes\Cron;

use WP_CLI;

/**
 * CLI command to mark unused images in WordPress media library for later review
 */
class MarkUnusedImages
{
    const META_KEY = '_marked_unused';
    const META_VALUE = '1';

    /**
     * Mark unreferenced images from the media library based on a JSON report
     *
     * ## OPTIONS
     *
     * <json-file>
     * : Path to the JSON report file containing unreferenced images
     *
     * [--dry-run]
     * : Preview what would be marked without actually marking
     *
     * [--verbose]
     * : Show detailed progress information for each image
     *
     * [--unmark]
     * : Remove the unused marker from images instead of adding it
     *
     * ## EXAMPLES
     *
     *     # Dry run to see what would be marked
     *     wp alingsas mark-unused-images data/image-report.json --dry-run
     *
     *     # Mark images with progress bar
     *     wp alingsas mark-unused-images data/image-report.json
     *
     *     # Mark images with verbose output
     *     wp alingsas mark-unused-images data/image-report.json --verbose
     *
     *     # Unmark previously marked images
     *     wp alingsas mark-unused-images data/image-report.json --unmark
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function __invoke($args, $assoc_args)
    {
        list($json_file) = $args;
        
        $dry_run = isset($assoc_args['dry-run']);
        $verbose = isset($assoc_args['verbose']);
        $unmark = isset($assoc_args['unmark']);

        // Validate file exists
        if (!file_exists($json_file)) {
            WP_CLI::error("JSON file not found: {$json_file}");
            return;
        }

        // Load and parse JSON
        WP_CLI::log("Loading JSON report from: {$json_file}");
        $json_content = file_get_contents($json_file);
        $data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            WP_CLI::error("Failed to parse JSON: " . json_last_error_msg());
            return;
        }

        // Validate JSON structure
        if (!isset($data['final_unreferenced_images']) || !is_array($data['final_unreferenced_images'])) {
            WP_CLI::error("Invalid JSON structure: 'final_unreferenced_images' not found or not an array");
            return;
        }

        $images = $data['final_unreferenced_images'];
        $total_images = count($images);

        if ($total_images === 0) {
            WP_CLI::success("No unreferenced images found in the report.");
            return;
        }

        // Display summary
        WP_CLI::log("");
        WP_CLI::log(WP_CLI::colorize("%B=== Summary ===%n"));
        WP_CLI::log("Total unreferenced images: " . WP_CLI::colorize("%Y{$total_images}%n"));
        
        if ($dry_run) {
            WP_CLI::warning("DRY RUN MODE: No images will be " . ($unmark ? "unmarked" : "marked"));
        }
        
        if ($unmark) {
            WP_CLI::log(WP_CLI::colorize("%CMode: UNMARK%n - Removing unused markers from images"));
        } else {
            WP_CLI::log(WP_CLI::colorize("%CMode: MARK%n - Adding unused markers to images"));
        }
        
        WP_CLI::log("");

        // Process images
        $processed_count = 0;
        $failed_count = 0;
        $not_found_count = 0;
        $already_processed_count = 0;
        $failed_images = [];

        // Initialize progress bar if not verbose
        if (!$verbose) {
            $action = $unmark ? 'Unmarking images' : 'Marking images';
            if ($dry_run) {
                $action = 'Analyzing images';
            }
            $progress = \WP_CLI\Utils\make_progress_bar($action, $total_images);
        }

        foreach ($images as $index => $image) {
            if (!isset($image['attachment_id'])) {
                if ($verbose) {
                    WP_CLI::warning("Skipping image without attachment_id at index {$index}");
                }
                $failed_count++;
                continue;
            }

            $attachment_id = $image['attachment_id'];
            $title = $image['title'] ?? 'Untitled';
            $file_path = $image['file_path'] ?? 'Unknown path';

            // Check if attachment exists
            $post = get_post($attachment_id);
            if (!$post || $post->post_type !== 'attachment') {
                if ($verbose) {
                    WP_CLI::log(WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %RNot found%n: ID {$attachment_id} - {$title}"));
                }
                $not_found_count++;
                
                if (!$verbose) {
                    $progress->tick();
                }
                continue;
            }

            // Check current state
            $current_value = get_post_meta($attachment_id, self::META_KEY, true);
            $is_marked = ($current_value === self::META_VALUE);

            if ($dry_run) {
                if ($unmark) {
                    if ($is_marked) {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %CWould unmark%n: ID {$attachment_id} - {$title}")
                            );
                            WP_CLI::log("  Path: {$file_path}");
                        }
                        $processed_count++;
                    } else {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %YAlready unmarked%n: ID {$attachment_id} - {$title}")
                            );
                        }
                        $already_processed_count++;
                    }
                } else {
                    if (!$is_marked) {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %CWould mark%n: ID {$attachment_id} - {$title}")
                            );
                            WP_CLI::log("  Path: {$file_path}");
                        }
                        $processed_count++;
                    } else {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %YAlready marked%n: ID {$attachment_id} - {$title}")
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
                                    WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %GUnmarked%n: ID {$attachment_id} - {$title}")
                                );
                                WP_CLI::log("  Path: {$file_path}");
                            }
                            $processed_count++;
                        } else {
                            if ($verbose) {
                                WP_CLI::log(
                                    WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %RFailed to unmark%n: ID {$attachment_id} - {$title}")
                                );
                                WP_CLI::log("  Path: {$file_path}");
                            }
                            $failed_count++;
                            $failed_images[] = [
                                'id' => $attachment_id,
                                'title' => $title,
                                'path' => $file_path
                            ];
                        }
                    } else {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %YAlready unmarked%n: ID {$attachment_id} - {$title}")
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
                                    WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %GMarked%n: ID {$attachment_id} - {$title}")
                                );
                                WP_CLI::log("  Path: {$file_path}");
                            }
                            $processed_count++;
                        } else {
                            if ($verbose) {
                                WP_CLI::log(
                                    WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %RFailed to mark%n: ID {$attachment_id} - {$title}")
                                );
                                WP_CLI::log("  Path: {$file_path}");
                            }
                            $failed_count++;
                            $failed_images[] = [
                                'id' => $attachment_id,
                                'title' => $title,
                                'path' => $file_path
                            ];
                        }
                    } else {
                        if ($verbose) {
                            WP_CLI::log(
                                WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %YAlready marked%n: ID {$attachment_id} - {$title}")
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
            WP_CLI::log("{$action}: " . WP_CLI::colorize("%G{$processed_count}%n") . " images");
        } else {
            $action = $unmark ? "Successfully unmarked" : "Successfully marked";
            WP_CLI::log("{$action}: " . WP_CLI::colorize("%G{$processed_count}%n") . " images");
        }
        
        if ($already_processed_count > 0) {
            $status = $unmark ? "already unmarked" : "already marked";
            WP_CLI::log("Skipped ({$status}): " . WP_CLI::colorize("%Y{$already_processed_count}%n") . " images");
        }
        
        if ($not_found_count > 0) {
            WP_CLI::log("Not found in database: " . WP_CLI::colorize("%Y{$not_found_count}%n") . " images");
        }
        
        if ($failed_count > 0) {
            WP_CLI::log("Failed: " . WP_CLI::colorize("%R{$failed_count}%n") . " images");
            
            if (!empty($failed_images)) {
                WP_CLI::log("");
                WP_CLI::log(WP_CLI::colorize("%RFailed images:%n"));
                foreach ($failed_images as $failed) {
                    WP_CLI::log("  - ID {$failed['id']}: {$failed['title']} ({$failed['path']})");
                }
            }
        }

        WP_CLI::log("");
        
        if ($dry_run) {
            WP_CLI::success("Dry run completed. Run without --dry-run to actually " . ($unmark ? "unmark" : "mark") . " images.");
        } else {
            WP_CLI::success("Image " . ($unmark ? "unmarking" : "marking") . " completed!");
            
            if (!$unmark && $processed_count > 0) {
                WP_CLI::log("");
                WP_CLI::log(WP_CLI::colorize("%BNext steps:%n"));
                WP_CLI::log("1. Go to the Media Library in WordPress admin");
                WP_CLI::log("2. Use the filter to show only marked unused images");
                WP_CLI::log("3. Review and delete images manually as needed");
                WP_CLI::log("");
                WP_CLI::log("To unmark images, run: wp alingsas mark-unused-images {$json_file} --unmark");
            }
        }
    }
}

/**
 * Register the WP-CLI command
 */
if (defined('WP_CLI') && constant('WP_CLI') === true) {
    WP_CLI::add_command('alingsas mark-unused-images', MarkUnusedImages::class);
}
