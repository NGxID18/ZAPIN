#!/bin/bash
# ==============================================================================
# SKRIP OTOMATISASI DEPLOYMENT ZAPIN (RSJKO Engku Haji Daud)
# Domain: https://zapin.online
# OS: Debian 13 (Trixie) / Ubuntu
# ==============================================================================

set -e

PROJECT_DIR="/home/ngxid18/ZAPIN"
DB_NAME="zapin_db"
DB_USER="zapin_user"
DB_PASS="zapin_secure_2026"
PHP_SOCK="/run/php/php8.4-fpm.sock"

echo "=========================================================="
echo "MEMULAI PROSES DEPLOYMENT ZAPIN (zapin.online)"
echo "=========================================================="

# 1. Update & Install Paket yang Dibutuhkan
echo "[1/6] Menginstal paket sistem (Nginx, PostgreSQL, PHP 8.4, Composer)..."
sudo apt update
sudo apt install -y nginx postgresql postgresql-contrib \
    php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath composer

# 2. Setup Database PostgreSQL
echo "[2/6] Mengonfigurasi basis data PostgreSQL..."
sudo -u postgres psql -tc "SELECT 1 FROM pg_user WHERE usename = '$DB_USER'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASS';"

sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname = '$DB_NAME'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;"

sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;"
sudo -u postgres psql -d $DB_NAME -c "GRANT ALL ON SCHEMA public TO $DB_USER;"

# 3. Setup Hak Akses Direktori
echo "[3/6] Mengatur izin akses direktori untuk Web Server (Nginx)..."
sudo chmod 755 /home/ngxid18
cd "$PROJECT_DIR"
sudo chown -R ngxid18:www-data storage bootstrap/cache database/sertifikat
sudo chmod -R 775 storage bootstrap/cache database/sertifikat
mkdir -p storage/app/public/uploads/kerusakan storage/app/public/uploads/sertifikat

# 4. Inisialisasi Aplikasi Laravel
echo "[4/6] Menjalankan migrasi, seeder Google Spreadsheet, dan optimasi cache..."
php artisan storage:link || true
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R ngxid18:www-data storage bootstrap/cache database/sertifikat
sudo chmod -R 775 storage bootstrap/cache database/sertifikat

# 5. Pasang Konfigurasi Nginx
echo "[5/6] Mengonfigurasi Virtual Host Nginx..."
sudo cp "$PROJECT_DIR/zapin_nginx.conf" /etc/nginx/sites-available/zapin
sudo ln -sfn /etc/nginx/sites-available/zapin /etc/nginx/sites-enabled/zapin
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t

# 6. Restart Layanan
echo "[6/6] Memuat ulang service PHP-FPM dan Nginx..."
sudo systemctl restart php8.4-fpm nginx
sudo systemctl enable php8.4-fpm nginx

echo "=========================================================="
echo "DEPLOYMENT BERHASIL SELESAI"
echo "Aplikasi berjalan di port 80 (http://localhost:80)"
echo "Pastikan Cloudflare Tunnel diarahkan ke: HTTP localhost:80"
echo "Buka: https://zapin.online"
echo "=========================================================="

