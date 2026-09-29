#!/usr/bin/env bash
#
# Container entrypoint for the Laravel CMS Blog image.
#
# Prepares the runtime environment (dotenv file, application key, storage
# symlink, TLS certificate, database schema) and then hands over to the
# command from CMD.
#
set -euo pipefail

cd /var/www/html

# ---------------------------------------------------------------------------
# 1. Environment file — the repository ships .env.example, not .env.
# ---------------------------------------------------------------------------
if [ ! -f .env ]; then
    echo "==> Creating .env from .env.example"
    cp .env.example .env
fi

# ---------------------------------------------------------------------------
# 2. Application key.
# ---------------------------------------------------------------------------
if ! grep -qE '^APP_KEY=base64:.+' .env; then
    echo "==> Generating application key"
    php artisan key:generate --force --ansi
fi

# ---------------------------------------------------------------------------
# 3. Public storage symlink (idempotent).
# ---------------------------------------------------------------------------
if [ ! -L public/storage ]; then
    echo "==> Linking public storage"
    php artisan storage:link || true
fi

# ---------------------------------------------------------------------------
# 4. TLS certificate for nginx (port 443). Generates a self-signed pair on
#    first boot only; it is never regenerated, so you can replace the files
#    with real certificates and they will be kept on the next start.
# ---------------------------------------------------------------------------
TLS_DIR="${TLS_DIR:-/etc/nginx/certs}"
TLS_CRT="${TLS_CRT:-$TLS_DIR/self-signed.crt}"
TLS_KEY="${TLS_KEY:-$TLS_DIR/self-signed.key}"

if [ ! -s "$TLS_CRT" ] || [ ! -s "$TLS_KEY" ]; then
    echo "==> Generating self-signed TLS certificate"
    mkdir -p "$TLS_DIR"
    openssl req -x509 -nodes -newkey rsa:2048 \
        -days 365 \
        -keyout "$TLS_KEY" \
        -out "$TLS_CRT" \
        -subj "/C=ID/ST=Local/L=Local/O=Laravel CMS Blog/CN=${TLS_CN:-localhost}" \
        -addext "subjectAltName=DNS:${TLS_CN:-localhost},DNS:localhost,IP:127.0.0.1"
    chmod 600 "$TLS_KEY"
    chmod 644 "$TLS_CRT"
else
    echo "==> TLS certificate already present, keeping it"
fi

# ---------------------------------------------------------------------------
# 5. Wait for the database, run migrations and seeders.
# ---------------------------------------------------------------------------
wait_for_database() {
    local attempts=0
    local max_attempts="${DB_WAIT_ATTEMPTS:-60}"
    until php -r '
        $env = function (string $key, string $default): string {
            $value = getenv($key);
            return ($value === false || $value === "") ? $default : $value;
        };
        $connection = $env("DB_CONNECTION", "mysql");
        try {
            new PDO(
                sprintf(
                    "%s:host=%s;port=%s;dbname=%s",
                    $connection,
                    $env("DB_HOST", "127.0.0.1"),
                    $env("DB_PORT", "3306"),
                    $env("DB_DATABASE", "")
                ),
                $env("DB_USERNAME", "root"),
                $env("DB_PASSWORD", ""),
                [PDO::ATTR_TIMEOUT => 3]
            );
        } catch (Throwable $e) {
            exit(1);
        }
    ' 2>/dev/null; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge "$max_attempts" ]; then
            echo "==> Database not reachable after ${attempts} attempts, continuing anyway" >&2
            return 0
        fi
        echo "==> Waiting for database (${attempts}/${max_attempts})..."
        sleep 2
    done
}

if [ -n "${DB_CONNECTION:-}" ] && [ "${DB_CONNECTION}" != "sqlite" ]; then
    wait_for_database
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "==> Running migrations"
    php artisan migrate --force --ansi

    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        echo "==> Seeding database"
        php artisan db:seed --force --ansi
    fi
fi

# ---------------------------------------------------------------------------
# 6. Hand over to CMD (or to the command passed on "docker run").
# ---------------------------------------------------------------------------
exec "$@"