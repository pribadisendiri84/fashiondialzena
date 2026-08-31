# Deploy pertama ke VPS (Sumopod)

Lakukan sekali. Update berikutnya: [04-update-setelah-git-pull.md](04-update-setelah-git-pull.md).

Lingkungan yang dipakai: Ubuntu, Nginx, MySQL, PHP-FPM, Certbot. Kode di `/var/www/fashiondialzena`. Domain contoh: `fashiondialzena.com`. Beberapa VPS memakai IP publik dulu sebelum DNS.

## 1. Stack di server

```bash
apt update
apt install -y nginx mysql-server unzip git curl
```

PHP di VPS Ubuntu 24 sering 8.3. Lock Composer proyek ini butuh **PHP ≥ 8.4.1**. Pasang 8.4 (contoh via `ppa:ondrej/php`):

```bash
apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-gd php8.4-zip php8.4-bcmath
```

Composer: [https://getcomposer.org/download/](https://getcomposer.org/download/)

MySQL harus `active`. Buat database dan user (ganti password):

```sql
CREATE DATABASE fashiondialzena CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dialzena'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON fashiondialzena.* TO 'dialzena'@'localhost';
FLUSH PRIVILEGES;
```

## 2. Kunci SSH untuk clone GitHub (deploy key)

Di VPS:

```bash
ssh-keygen -t ed25519 -C "vps-fashiondialzena" -f /root/.ssh/id_ed25519 -N ""
cat /root/.ssh/id_ed25519.pub
```

Tempel public key di GitHub: repo → **Settings → Deploy keys → Add** (read-only cukup).

Saat `git clone` pertama kali, konfirmasi host key GitHub dengan ketik **`yes`** (bukan `y`).

```bash
mkdir -p /var/www
cd /var/www
git clone git@github.com:pribadisendiri84/fashiondialzena.git
cd fashiondialzena
```

## 3. `.env` produksi (manual, tidak dari Git)

```bash
cp .env.example .env
nano .env
```

Yang wajib disesuaikan:

| Variabel | Produksi |
|----------|----------|
| `APP_NAME` | `ALZena Fashion` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://fashiondialzena.com` (atau `http://IP` dulu) |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | sesuai MySQL |
| (foto produk) | lokal di `storage/app/public` — pastikan `php artisan storage:link` |

Lalu:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
# php artisan db:seed --force   # HANYA jika database masih kosong dan butuh data contoh
php artisan storage:link
php artisan optimize
chown -R www-data:www-data /var/www/fashiondialzena
chmod -R ug+rwx storage bootstrap/cache
```

Jangan jalankan `db:seed` di toko yang sudah ada data nyata — bisa menimpa/menambah data contoh.

## 4. Nginx

Document root **harus** `.../public`, bukan folder proyek.

Contoh server block (sesuaikan `php8.4-fpm.sock`):

```nginx
server {
    listen 80;
    server_name fashiondialzena.com www.fashiondialzena.com;
    root /var/www/fashiondialzena/public;

    add_header X-Frame-Options "SAMEORIGIN";
    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/fashiondialzena /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

Beberapa situs di satu IP: tiap domain punya `server_name` sendiri. Akses lewat IP saja hanya menampilkan default site.

## 5. DNS dan SSL

- A record `@` (dan `www` jika dipakai) ke IP publik VPS.
- Cek: `dig +short fashiondialzena.com`
- SSL:

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d fashiondialzena.com -d www.fashiondialzena.com
```

Setelah HTTPS, `APP_URL` di `.env` harus `https://...` lalu `php artisan config:cache`.

## 6. PHP-FPM vs CLI

`php -v` di SSH harus versi yang sama dengan sock Nginx (`php8.4-fpm`). Kalau CLI 8.4 tapi FPM masih 8.3, situs bisa error sementara `composer` di SSH lolos.
