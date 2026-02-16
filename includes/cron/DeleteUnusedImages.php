<?php

namespace AlingsasCustomisation\Includes\Cron;

use WP_CLI;

/**
 * CLI command to delete unused images from WordPress media library
 */
class DeleteUnusedImages
{
    /**
     * Delete unreferenced images from the media library based on a JSON report
     *
     * ## OPTIONS
     *
     * <json-file>
     * : Path to the JSON report file containing unreferenced images
     *
     * [--dry-run]
     * : Preview what would be deleted without actually deleting
     *
     * [--verbose]
     * : Show detailed progress information for each image
     *
     * ## EXAMPLES
     *
     *     # Dry run to see what would be deleted
     *     wp delete-unused-images data/image-report.json --dry-run
     *
     *     # Delete images with progress bar
     *     wp delete-unused-images data/image-report.json
     *
     *     # Delete images with verbose output
     *     wp delete-unused-images data/image-report.json --verbose
     *
     *     # Dry run with verbose output
     *     wp delete-unused-images data/image-report.json --dry-run --verbose
     *
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function __invoke($args, $assoc_args)
    {
        list($json_file) = $args;
        
        $dry_run = isset($assoc_args['dry-run']);
        $verbose = isset($assoc_args['verbose']);

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
            WP_CLI::warning("DRY RUN MODE: No images will be deleted");
        }
        
        WP_CLI::log("");

        // Confirm if not dry run
        if (!$dry_run) {
            WP_CLI::confirm(
                WP_CLI::colorize("%RThis will permanently delete {$total_images} images from the media library.%n\nDo you want to continue?"),
                $assoc_args
            );
        }

        // Process images
        $deleted_count = 0;
        $failed_count = 0;
        $not_found_count = 0;
        $failed_images = [];

        // Initialize progress bar if not verbose
        if (!$verbose) {
            $progress = \WP_CLI\Utils\make_progress_bar(
                $dry_run ? 'Analyzing images' : 'Deleting images',
                $total_images
            );
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
                    WP_CLI::log(WP_CLI::colorize("[%y{$index}/%y{$total_images}%n] %RNot found%n: ID {$attachment_id} - {$title}"));
                }
                $not_found_count++;
                
                if (!$verbose) {
                    $progress->tick();
                }
                continue;
            }

            if ($dry_run) {
                if ($verbose) {
                    WP_CLI::log(
                        WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %CWould delete%n: ID {$attachment_id} - {$title}")
                    );
                    WP_CLI::log("  Path: {$file_path}");
                }
                $deleted_count++;
            } else {
                // Actually delete the attachment
                $result = wp_delete_attachment($attachment_id, true);

                if ($result) {
                    if ($verbose) {
                        WP_CLI::log(
                            WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %GDeleted%n: ID {$attachment_id} - {$title}")
                        );
                        WP_CLI::log("  Path: {$file_path}");
                    }
                    $deleted_count++;
                } else {
                    if ($verbose) {
                        WP_CLI::log(
                            WP_CLI::colorize("[%y" . ($index + 1) . "%n/%y{$total_images}%n] %RFailed%n: ID {$attachment_id} - {$title}")
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
            WP_CLI::log("Would delete: " . WP_CLI::colorize("%G{$deleted_count}%n") . " images");
        } else {
            WP_CLI::log("Successfully deleted: " . WP_CLI::colorize("%G{$deleted_count}%n") . " images");
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
            WP_CLI::success("Dry run completed. Run without --dry-run to actually delete images.");
        } else {
            WP_CLI::success("Image deletion completed!");
        }
    }
}

/**
 * Register the WP-CLI command
 */
if (defined('WP_CLI') && constant('WP_CLI') === true) {
    WP_CLI::add_command('alingsas delete-unused-images', DeleteUnusedImages::class);
}
