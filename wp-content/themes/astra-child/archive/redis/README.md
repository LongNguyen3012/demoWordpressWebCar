
---

### Archive: Redis Version – `archive/redis/README.md`

```markdown
# Email Queue – Redis + Worker Implementation (Archive)

This is the real‑time email queue system that uses Redis as a message broker and a long‑running worker to send emails immediately.

## Files
- `email-queue.php` – inserts into database **and** pushes job to Redis.
- `worker.php` – continuous PHP script that listens to Redis and processes jobs.
- `composer.json` – for installing Predis (PHP Redis client).
- `docker-compose.yml` – includes `redis` service.

## How It Works
1. On post publish, `email_queue_add()` inserts a row into `wp_email_queue` and pushes a JSON job to Redis list `email:queue`.
2. The worker (`worker.php`) runs an infinite loop, popping jobs from Redis via `brpop`.
3. For each job, it fetches all unsent rows for that user and sends one digest email.
4. After sending, rows are marked `sent = 1`.

## Why It Was Replaced
- We needed a **daily digest**, not real‑time emails.
- The worker required manual management (starting/stopping) and had path/autoloader issues in Docker.
- A simpler scheduled endpoint approach is more reliable and easier to maintain.

## How to Revive
1. Install Predis: `composer require predis/predis` inside the theme folder.
2. Start Redis: `docker-compose up -d redis` (if using the provided compose file).
3. Start the worker: `docker exec -d demo_app php /var/www/html/wp-content/themes/astra-child/worker.php`
4. Publish a new Car – the worker will process it immediately.
5. Check logs: `docker exec -it demo_app tail -f /tmp/worker.log`

## Commands (for testing)
```bash
# Manually push a test job to Redis
docker exec -it demo_redis redis-cli RPUSH email:queue '{"user_id":1,"db_id":0,"type":"new_car","post_id":999,"content":"Test Car","link":"http://localhost","queued_at":"2026-07-21 07:00:00"}'

# Check worker log
docker exec -it demo_app tail -f /tmp/worker.log

# Stop the worker
docker exec demo_app pkill -f worker.php

