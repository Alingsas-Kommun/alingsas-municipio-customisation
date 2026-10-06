<?php

/**
 * Standalone regression tests: php tests/media-scan-safety.php
 * Uses SQLite in memory for attachment selection and a fake database for
 * reference lookups. Requires pdo_sqlite; never connects to a live database.
 */
define('ARRAY_A', 'ARRAY_A');
define('WP_CLI', true);

class CliFailure extends RuntimeException {}

class WP_CLI
{
    public static array $messages = [];
    public static function log($message): void { self::$messages[] = $message; }
    public static function warning($message): void { self::$messages[] = $message; }
    public static function success($message): void { self::$messages[] = $message; }
    public static function colorize($message): string { return $message; }
    public static function add_command($name, $command): void {}
    public static function error($message): void { throw new CliFailure($message); }
}

function wp_mkdir_p($path): bool
{
    return is_dir($path) || mkdir($path, 0700, true);
}

function get_post_meta($id, $key, $single)
{
    global $wpdb;
    $query = $wpdb->selection->prepare('SELECT meta_value FROM test_postmeta WHERE post_id = ? AND meta_key = ?');
    $query->execute([$id, $key]);
    return $query->fetchColumn() ?: '';
}

function update_post_meta($id, $key, $value)
{
    global $wpdb;
    $wpdb->historyWrites++;
    if ($wpdb->historyWrites === $wpdb->historyFailAt) {
        $wpdb->last_error = 'Simulated history write failure';
        return false;
    }
    $wpdb->last_error = '';
    if (get_post_meta($id, $key, true) === $value) {
        return false;
    }
    $query = $wpdb->selection->prepare('DELETE FROM test_postmeta WHERE post_id = ? AND meta_key = ?');
    $query->execute([$id, $key]);
    $query = $wpdb->selection->prepare('INSERT INTO test_postmeta (post_id, meta_key, meta_value) VALUES (?, ?, ?)');
    return $query->execute([$id, $key, $value]);
}

class wpdb
{
    public string $dbname;
    public string $prefix = 'test_';
    public string $posts = 'test_posts';
    public string $postmeta = 'test_postmeta';
    public string $termmeta = 'test_termmeta';
    public string $last_error = '';
    public int $calls = 0;
    public int $failAt = 0;
    public bool $empty = false;
    public bool $invalidUtf8 = false;
    public $onQuery = null;
    public PDO $selection;
    public bool $customAttachments = false;
    public int $historyWrites = 0;
    public int $historyFailAt = 0;

    public function __construct(string $database)
    {
        $this->dbname = $database;
        $this->selection = new PDO('sqlite::memory:');
        $this->selection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->selection->exec('CREATE TABLE test_posts (ID INTEGER PRIMARY KEY, post_title TEXT, post_mime_type TEXT, guid TEXT, post_date TEXT, post_parent INTEGER, post_type TEXT)');
        $this->selection->exec('CREATE TABLE test_postmeta (post_id INTEGER, meta_key TEXT, meta_value TEXT)');
        $this->addAttachment(101, 'image/jpeg');
        $this->addAttachment(102, 'image/jpeg');
    }

    public function addAttachment(int $id, string $mime): void
    {
        $query = $this->selection->prepare('INSERT INTO test_posts VALUES (?, ?, ?, ?, ?, 0, ?)');
        $query->execute([$id, "Attachment {$id}", $mime, "https://example.test/{$id}", '2026-01-01 00:00:00', 'attachment']);
    }

    public function prepare($sql, ...$args): string
    {
        $index = 0;
        return preg_replace_callback('/%%|%[ds]/', function ($match) use ($args, &$index) {
            if ($match[0] === '%%') { return '%'; }
            $value = $args[$index++];
            return $match[0] === '%d' ? (string) (int) $value : $this->selection->quote($value);
        }, $sql);
    }
    public function _real_escape($value): string { return addslashes($value); }

    public function get_results($sql, $output): array
    {
        $this->calls++;
        $this->last_error = '';
        if ($this->onQuery !== null) {
            ($this->onQuery)();
        }
        if ($this->calls === $this->failAt) {
            // wpdb returns an empty array on SQL errors as well as no matches.
            $this->last_error = 'Simulated query timeout';
            return [];
        }
        if ($this->empty) {
            return [];
        }
        if (str_contains($sql, 'SELECT p.ID')) {
            $pdf = str_contains($sql, 'application/pdf');
            if (!$this->customAttachments) {
                $this->selection->exec("UPDATE test_posts SET post_mime_type = '" . ($pdf ? 'application/pdf' : 'image/jpeg') . "'");
            }
            // Run the generated SQL, including NOT EXISTS, ORDER BY and LIMIT,
            // rather than duplicating the production eligibility logic in PHP.
            $rows = $this->selection->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            if ($this->invalidUtf8) {
                foreach ($rows as &$row) { $row['post_title'] = "\xB1"; }
            }
            return $rows;
        }
        if (str_contains($sql, "meta_key = '_wp_attached_file'")) {
            return [
                ['post_id' => 101, 'meta_value' => '2026/01/used-file.jpg'],
                ['post_id' => 102, 'meta_value' => '2026/01/unused-file.jpg'],
            ];
        }
        if (str_contains($sql, "meta_key = '_wp_attachment_metadata'")) {
            return [];
        }
        if (str_contains($sql, 'FROM test_postmeta') && str_contains($sql, 'meta_value IN (') && str_contains($sql, "'101'")) {
            return [['meta_id' => 1, 'post_id' => 999, 'meta_key' => '_thumbnail_id', 'meta_value' => '101']];
        }
        return [];
    }
}

require_once __DIR__ . '/../includes/cron/FindUnusedImages.php';
require_once __DIR__ . '/../includes/cron/FindUnusedPdfs.php';

use AlingsasCustomisation\Includes\Cron\FindUnusedImages;
use AlingsasCustomisation\Includes\Cron\FindUnusedPdfs;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectFailure(callable $run, string $message): void
{
    try {
        $run();
    } catch (CliFailure $error) {
        check(str_contains($error->getMessage(), $message), $error->getMessage());
        return;
    }
    throw new RuntimeException("Expected failure containing: {$message}");
}

$database = 'media_scan_test_' . bin2hex(random_bytes(8));
$directory = sys_get_temp_dir() . '/' . $database;
mkdir($directory, 0700);
$reportPath = $directory . '/report.json';
$options = ['limit' => 'all', 'pause-ms' => '0', 'output' => $reportPath];
$previous = '{"previous_report":true}';
$checks = 0;

try {
    foreach ([FindUnusedImages::class => 10, FindUnusedPdfs::class => 9] as $class => $queryCount) {
        $command = new $class();
        $wpdb = new wpdb($database);
        $command([], $options);
        $report = json_decode(file_get_contents($reportPath), true, 512, JSON_THROW_ON_ERROR);
        check($report['unreferenced_ids'] === [102], 'Reference classification changed');
        check($report['metadata']['scan_complete'] === true, 'Missing completion marker');
        check($report['metadata']['database_query_count'] === $queryCount, 'Missing query accounting');
        $checks++;

        // Fail each database stage, including reference lookups, independently.
        for ($failAt = 1; $failAt <= $queryCount; $failAt++) {
            file_put_contents($reportPath, $previous);
            $wpdb = new wpdb($database);
            $wpdb->failAt = $failAt;
            expectFailure(fn() => $command([], $options), 'Simulated query timeout');
            check(file_get_contents($reportPath) === $previous, 'SQL failure replaced the previous report');
            check($wpdb->calls === $failAt, 'Scan continued after SQL failure');
            check(glob($directory . '/.media-scan-*') === [], 'SQL failure left a temporary report');
            check($wpdb->historyWrites === 0, 'SQL failure saved scan history');
            // Reusing the command must release the lock and reset scan state.
            $wpdb = new wpdb($database);
            $command([], $options);
            $checks++;
        }

        file_put_contents($reportPath, $previous);
        $wpdb = new wpdb($database);
        $wpdb->invalidUtf8 = true;
        expectFailure(fn() => $command([], $options), 'Malformed UTF-8');
        check(file_get_contents($reportPath) === $previous, 'JSON failure damaged the existing report');
        check($wpdb->historyWrites === 0, 'JSON failure saved scan history');
        $checks++;

        $wpdb = new wpdb($database);
        $wpdb->empty = true;
        $command([], $options);
        $report = json_decode(file_get_contents($reportPath), true, 512, JSON_THROW_ON_ERROR);
        check($report['unreferenced_ids'] === [], 'Empty scan left stale candidates');
        check($report['metadata']['scan_complete'] === true, 'Empty scan did not complete');
        $checks++;

        foreach ([['pause-ms' => '-1'], ['pause-ms' => '60001'], ['batch-size' => '0'],
                  ['limit' => 'typo'], ['ids' => '101,garbage'], ['ids' => ''], ['output' => '']] as $invalid) {
            $wpdb = new wpdb($database);
            expectFailure(fn() => $command([], array_replace($options, $invalid)), '--');
            check($wpdb->calls === 0, 'Invalid arguments reached the database');
            $checks++;
        }

        $wpdb = new wpdb($database);
        expectFailure(fn() => $command([], array_replace($options, ['output' => $directory])), 'not writable');
        check($wpdb->calls === 0, 'Invalid output path reached the database');
        $checks++;

        $wpdb = new wpdb($database);
        $start = microtime(true);
        $command([], array_replace($options, ['batch-size' => '1', 'pause-ms' => '100']));
        check(microtime(true) - $start >= 0.09, 'Batch pause was not applied');
        $checks++;

        $wpdb = new wpdb($database);
        $command([], array_replace($options, ['ids' => '101, 102,101']));
        $checks++;

        // Persistent history with a real SQL selection: a second --limit=100
        // must select the remainder, not reselect and then discard the top 100.
        $wpdb = new wpdb($database);
        $mime = $class === FindUnusedImages::class ? 'image/jpeg' : 'application/pdf';
        $itemKey = $class === FindUnusedImages::class ? 'images' : 'pdfs';
        $wpdb->customAttachments = true;
        $wpdb->selection->exec("UPDATE test_posts SET post_mime_type = '{$mime}'");
        for ($id = 103; $id <= 205; $id++) { $wpdb->addAttachment($id, $mime); }
        $readIds = function () use ($reportPath, $itemKey): array {
            $report = json_decode(file_get_contents($reportPath), true, 512, JSON_THROW_ON_ERROR);
            return array_column($report[$itemKey], 'attachment_id');
        };
        $limited = array_replace($options, ['limit' => '100']);
        $command([], $limited);
        check($readIds() === range(205, 106), 'First limited scan did not select the newest 100');
        check($wpdb->historyWrites === 100, 'Not all checked attachments received history');
        $command([], $limited);
        check($readIds() === range(105, 101), 'Second limited scan did not select the remaining five');
        check(get_post_meta(101, '_alingsas_media_last_scanned_at', true) !== '', 'Referenced attachment did not receive history');
        $checks++;

        $command([], $limited);
        check($readIds() === [], 'Recent attachments were selected again');
        check($wpdb->historyWrites === 105, 'Skipped attachments had history refreshed');
        $checks++;

        $command([], array_replace($options, ['limit' => '3', 'force' => true]));
        check($readIds() === [205, 204, 203], 'Force ignored the limit or did not bypass history');
        $checks++;

        $command([], array_replace($options, ['ids' => '101,102']));
        check($readIds() === [], 'Explicit IDs bypassed history without force');
        $command([], array_replace($options, ['ids' => '101,102', 'force' => true]));
        $ids = $readIds();
        sort($ids);
        check($ids === [101, 102], 'Force with explicit IDs selected the wrong attachments');
        $checks++;

        $now = time();
        $query = $wpdb->selection->prepare('UPDATE test_postmeta SET meta_value = ? WHERE post_id = ? AND meta_key = ?');
        $query->execute([(string) ($now - 604800), 104, '_alingsas_media_last_scanned_at']);
        $query->execute([(string) ($now - 604800 + 300), 106, '_alingsas_media_last_scanned_at']);
        $command([], array_replace($options, ['ids' => '104,106']));
        check($readIds() === [104], 'Seven-day boundary was not respected');
        check((int) get_post_meta(104, '_alingsas_media_last_scanned_at', true) >= $now, 'Expired timestamp was not refreshed');
        $checks++;

        $wpdb->addAttachment(300, $mime === 'image/jpeg' ? 'application/pdf' : 'image/jpeg');
        $wpdb->addAttachment(301, $mime);
        $wpdb->selection->exec("INSERT INTO test_postmeta VALUES (301, 'event-manager-media', '1')");
        $command([], array_replace($options, ['ids' => '201,300,301', 'force' => true]));
        check($readIds() === [201], 'Force bypassed MIME or event-manager exclusions');
        $checks++;

        // A history write error occurs after report publication. Only recorded
        // attachments may be skipped on retry; all results remain in the report.
        $wpdb = new wpdb($database);
        $wpdb->historyFailAt = 2;
        expectFailure(fn() => $command([], $options), 'Report saved, but scan history could not be saved');
        check(count($readIds()) === 2, 'History failure lost the successful scan report');
        check(get_post_meta(101, '_alingsas_media_last_scanned_at', true) === '', 'Failed history was recorded');
        $command([], $options);
        check($readIds() === [101], 'History failure prevented retrying an attachment');
        $checks++;
    }

    // Both command types must contend for the same real flock, even on
    // different sites in the same database. The rejected scan executes no SQL.
    foreach ([FindUnusedImages::class => FindUnusedPdfs::class, FindUnusedPdfs::class => FindUnusedImages::class] as $first => $second) {
        $wpdb = new wpdb($database);
        $outerDb = $wpdb;
        $blocked = false;
        $outerDb->onQuery = function () use ($database, $outerDb, $second, $options, &$blocked): void {
            global $wpdb;
            $outerDb->onQuery = null;
            $wpdb = new wpdb($database);
            $wpdb->prefix = 'another_site_';
            expectFailure(fn() => (new $second())([], $options), 'Cannot acquire media scan lock');
            check($wpdb->calls === 0, 'Concurrent scan reached the database');
            $blocked = true;
            $wpdb = $outerDb;
        };
        (new $first())([], $options);
        check($blocked, 'Lock contention was not tested');
        $wpdb = new wpdb($database);
        (new $second())([], $options);
        $checks++;
    }

    echo "Passed {$checks} media scan checks (in-memory SQLite selection; no live database).\n";
} finally {
    foreach (glob($directory . '/*') as $file) {
        unlink($file);
    }
    foreach (glob($directory . '/.media-scan-*') as $file) {
        unlink($file);
    }
    rmdir($directory);
    // Only this test's random database lock; production locks are never deleted.
    $lock = sys_get_temp_dir() . '/alingsas-media-scan-' . hash('sha256', ':' . $database) . '.lock';
    if (file_exists($lock)) {
        unlink($lock);
    }
}
