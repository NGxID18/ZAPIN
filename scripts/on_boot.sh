#!/bin/bash
# ==============================================================================
# ZAPIN STARTUP & HEALTH CHECK ON BOOT / RESTART
# ==============================================================================

PROJECT_DIR="/home/ngxid18/ZAPIN"
cd "$PROJECT_DIR"

# Tunggu 5 detik agar PostgreSQL, PHP-FPM, dan Nginx benar-benar siap
sleep 5

# Set umask agar file yang dibuat dapat dibaca & ditimpa oleh www-data
umask 000

# 1. Pastikan symlink storage publik selalu aktif
/usr/bin/php artisan storage:link >/dev/null 2>&1 || true

# 2. Pastikan struktur database up-to-date
/usr/bin/php artisan migrate --force >/dev/null 2>&1 || true

# 3. Cache konfigurasi & routing untuk performa maksimal
/usr/bin/php artisan config:cache >/dev/null 2>&1 || true
/usr/bin/php artisan route:cache >/dev/null 2>&1 || true
/usr/bin/php artisan view:cache >/dev/null 2>&1 || true

# 4. Pastikan izin akses storage dan bootstrap/cache selalu terbuka penuh untuk www-data
chmod -R a+rwX "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" >/dev/null 2>&1 || true

# 5. Catat riwayat boot ke storage log
echo "[$(date '+%Y-%m-%d %H:%M:%S')] ZAPIN boot-check completed successfully." >> "$PROJECT_DIR/storage/logs/boot.log"

