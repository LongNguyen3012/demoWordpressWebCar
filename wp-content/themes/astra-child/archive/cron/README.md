# Email Queue – WP‑Cron Implementation (Archive)

This is the original email queue system that uses a database queue and WP‑Cron to process emails every 5 minutes.

## Files
- `email-queue.php` – contains all queue logic, cron scheduling, and the batch processor.
- `docker-compose.yml` – the Docker setup without Redis.

## How It Works
1. On post publish, `email_queue_add()` inserts a row into `wp_email_queue` with `sent = 0`.
2. WP‑Cron runs `email_queue_cron_hook` every 5 minutes (if triggered by site visits).
3. `email_queue_process_batch()` fetches all unsent rows, groups by user, and sends one digest email per user.
4. After sending, rows are marked `sent = 1`.

## Why It Was Replaced
- WP‑Cron only runs when someone visits the site. In a low‑traffic Docker environment, emails were never sent automatically.
- We needed a more reliable trigger.

## How to Revive
1. Place `email-queue.php` in `inc/`.
2. Ensure your `docker-compose.yml` does **not** include Redis.
3. Set up a system cron (Task Scheduler) to hit `http://localhost:8081/wp-cron.php?doing_wp_cron` every 5 minutes (or daily if you only want once a day).
4. The manual "Process Email Queue Now" button on Settings page will still work.

## Notes
- The queue table `wp_email_queue` is required (created on theme activation).
- Unsubscribe functionality is included via `email_queue_get_unsubscribe_hash()`.

## Commands (for testing)
```bash
# Process queue manually (if you have WP CLI)
docker exec demo_app wp eval "email_queue_process_batch();"

# Or visit the Settings page and click the button.