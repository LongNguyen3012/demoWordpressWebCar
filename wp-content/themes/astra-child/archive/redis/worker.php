#!/usr/bin/env php
<?php

$log_file = '/tmp/worker.log';
function wlog($msg) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

wlog('=== WORKER STARTED ===');

if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SERVER_NAME'] = 'localhost';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['REQUEST_URI'] = '/';
}

define('ASTRA_CHILD_DIR', __DIR__);
wlog('ASTRA_CHILD_DIR: ' . ASTRA_CHILD_DIR);

$wp_load = ASTRA_CHILD_DIR . '/../../../wp-load.php';
if (!file_exists($wp_load)) {
    fwrite(STDERR, "WordPress bootstrap not found at $wp_load\n");
    wlog('ERROR: WordPress bootstrap not found at ' . $wp_load);
    exit(1);
}
require_once $wp_load;
wlog('WordPress loaded');

$eq_path = ASTRA_CHILD_DIR . '/inc/email-queue.php';
if (!file_exists($eq_path)) {
    fwrite(STDERR, "email-queue.php not found at $eq_path\n");
    wlog('ERROR: email-queue.php not found at ' . $eq_path);
    exit(1);
}
require_once $eq_path;
wlog('email-queue.php loaded');

$vendor_autoload = ASTRA_CHILD_DIR . '/vendor/autoload.php';
if (!file_exists($vendor_autoload)) {
    fwrite(STDERR, "Predis autoloader not found at $vendor_autoload\n");
    wlog('ERROR: Predis autoloader not found at ' . $vendor_autoload);
    exit(1);
}
require_once $vendor_autoload;
wlog('Predis autoloader loaded from ' . $vendor_autoload);

if (!class_exists('Predis\Client')) {
    fwrite(STDERR, "Predis\Client class not found\n");
    wlog('ERROR: Predis\Client class not found');
    exit(1);
}
wlog('Predis\Client class exists');

$redis_host = getenv('REDIS_HOST') ?: 'redis';
$redis_port = getenv('REDIS_PORT') ?: 6379;
wlog("Connecting to Redis at $redis_host:$redis_port");

try {
    $redis = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => $redis_host,
        'port'   => $redis_port,
    ]);
    $redis->ping();
    wlog("Redis connection successful");
} catch (Exception $e) {
    fwrite(STDERR, "Redis connection failed: " . $e->getMessage() . "\n");
    wlog("ERROR: Redis connection failed: " . $e->getMessage());
    exit(1);
}

wlog("Email worker started, waiting for jobs...");

while (true) {
    try {
        $job = $redis->brpop('email:queue', 1);
        if ($job) {
            list($queue, $payload) = $job;
            wlog("Received job: " . $payload);
            $data = json_decode($payload, true);
            if ($data && isset($data['user_id'])) {
                global $wpdb;
                $table = $wpdb->prefix . 'email_queue';
                $items = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table WHERE user_id = %d AND sent = 0 ORDER BY queued_at ASC",
                    $data['user_id']
                ));
                if (!empty($items)) {
                    wlog("Found " . count($items) . " unsent items for user " . $data['user_id']);
                    $success = email_queue_send_digest($data['user_id'], $items);
                    if ($success) {
                        $ids = array_map(function($item) { return $item->id; }, $items);
                        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE $table SET sent = 1, sent_at = %s WHERE id IN ($ids_placeholder)",
                                array_merge([current_time('mysql')], $ids)
                            )
                        );
                        wlog("Sent digest for user " . $data['user_id'] . " (" . count($items) . " items)");
                    } else {
                        wlog("Failed to send digest for user " . $data['user_id']);
                    }
                } else {
                    wlog("No unsent items for user " . $data['user_id']);
                }
            } else {
                wlog("Invalid job payload: " . $payload);
            }
        }
    } catch (Exception $e) {
        wlog("Worker error: " . $e->getMessage());
        sleep(5);
    }
}