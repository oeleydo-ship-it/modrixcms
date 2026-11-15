#!/usr/bin/env bash
#
# Modrix git auto-deploy: install dependencies, build the front end, run migrations, publish
# theme and plugin assets and warm the caches. One script for every git-based host.
#
#   bash deploy/deploy.sh build      composer install (no dev) + npm ci + npm run build
#   bash deploy/deploy.sh release    migrate, cms:sync, storage:link, caches, queue restart
#   bash deploy/deploy.sh all        build, then a short maintenance window around release
#   bash deploy/deploy.sh help       this text with per-platform setup
#
# Settings (environment variables, all optional):
#   PHP_BIN=php              PHP binary (Forge: $FORGE_PHP, Ploi: {PHP})
#   COMPOSER_BIN=composer    Composer binary, or "php composer.phar"
#   NPM_BIN=npm              npm binary
#   SKIP_COMPOSER=1          skip composer install (dependencies already installed)
#   SKIP_NODE=1              skip the front-end build (public/build already present)
#   SKIP_MIGRATE=1           skip database migrations
#   NO_DOWNTIME=1            do not use maintenance mode in "all" (zero-downtime releases)
#   UP_ON_FAILURE=1          bring the site back up even when the deploy failed (default: stay in
#                            maintenance mode so visitors never meet new code on an old database)
#   HEALTHCHECK_URL=https:// request this URL at the end and fail the deploy if it errors
#
# ---------------------------------------------------------------------------------------------
# Laravel Cloud  (Environment > Settings > Deployments)
#   Build commands:   bash deploy/deploy.sh build
#   Deploy commands:  bash deploy/deploy.sh release
#   Use a MySQL or Postgres database resource. Cloud rebuilds the file system on every deploy,
#   so SQLite and uploaded themes, plugins and files do not survive. Keep uploads on a bucket.
#
# Laravel Forge  (Site > Deployments > Deploy Script)        see deploy/forge-deploy-script.sh
# Ploi           (Site > Deployments > Deploy script)         see deploy/ploi-deploy-script.sh
# Plesk          (Websites and Domains > Git > Additional deployment actions)
#                   bash deploy/deploy.sh all
# cPanel         (Git Version Control with .cpanel.yml)       see deploy/cpanel.yml.example
# Any SSH host   git pull && bash deploy/deploy.sh all
# ---------------------------------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

step() { printf '\n\033[1;34m==>\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33mwarning:\033[0m %s\n' "$*" >&2; }
fail() { printf '\033[1;31merror:\033[0m %s\n' "$*" >&2; exit 1; }

artisan() { $PHP_BIN artisan "$@"; }

usage() { sed -n '2,/^set -euo/p' "$0" | sed '$d' | sed 's/^# \{0,1\}//'; }

build() {
    step "Installing PHP dependencies"
    if [ "${SKIP_COMPOSER:-0}" = "1" ]; then
        echo "skipped (SKIP_COMPOSER=1)"
    else
        $COMPOSER_BIN install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    fi

    step "Building the front end"
    if [ "${SKIP_NODE:-0}" = "1" ]; then
        echo "skipped (SKIP_NODE=1)"
    elif command -v "$NPM_BIN" >/dev/null 2>&1; then
        $NPM_BIN ci --ignore-scripts --no-audit --no-fund
        $NPM_BIN run build
    elif [ -f public/build/manifest.json ] || [ -f public/build/.vite/manifest.json ]; then
        warn "npm not found: keeping the existing public/build"
    else
        fail "npm not found and public/build is missing. Build on a machine with Node (npm ci && npm run build) and upload public/build, or install from the release zip (php deploy/build-release.php)."
    fi
}

release() {
    [ -f .env ] || [ -n "${APP_KEY:-}" ] || fail "No .env file and no APP_KEY in the environment. Create .env first, or run the web installer at /install."
    [ -f vendor/autoload.php ] || fail "vendor/ is missing: run 'bash deploy/deploy.sh build' first."

    local connection
    connection="$(artisan config:show database.default 2>/dev/null | grep -Eo '[a-z]+$' | tail -1 || true)"
    if [ "$connection" = "sqlite" ]; then
        warn "The database is SQLite. That is fine on a server with a persistent disk, but data is lost on hosts that rebuild the file system on every deploy (for example Laravel Cloud)."
    fi

    if [ "${SKIP_MIGRATE:-0}" = "1" ]; then
        step "Migrations skipped (SKIP_MIGRATE=1)"
    else
        step "Running database migrations"
        artisan migrate --force
    fi

    step "Publishing theme and plugin assets"
    artisan cms:sync

    step "Linking storage"
    artisan storage:link --force >/dev/null 2>&1 || warn "Could not create the public/storage link (symlinks may be disabled on this host)."

    step "Warming caches"
    artisan optimize:clear
    artisan config:cache
    artisan route:cache
    artisan view:cache
    artisan event:cache

    step "Restarting queue workers"
    artisan queue:restart || warn "queue:restart failed (no queue workers or no cache store): ignoring."

    if [ -n "${HEALTHCHECK_URL:-}" ]; then
        step "Health check: $HEALTHCHECK_URL"
        curl -fsS --max-time 30 -o /dev/null "$HEALTHCHECK_URL" || fail "Health check failed."
    fi

    step "Release finished"
}

all() {
    build

    if [ "${NO_DOWNTIME:-0}" = "1" ]; then
        release
        return
    fi

    step "Maintenance mode on"
    artisan down --retry=30 --refresh=15 || warn "Could not enter maintenance mode: continuing."
    trap finish EXIT
    release
}

# Runs when "all" ends: back online after a good deploy; after a failed one stay in maintenance mode.
finish() {
    local code=$?
    trap - EXIT

    if [ "$code" -eq 0 ]; then
        artisan up || true
    elif [ "${UP_ON_FAILURE:-0}" = "1" ]; then
        artisan up || true
        warn "The deploy failed. The site was brought back up (UP_ON_FAILURE=1): check it now."
    else
        warn "The deploy failed. The site is left in maintenance mode so visitors do not meet a half-updated app. Fix the problem and deploy again, or run: $PHP_BIN artisan up"
    fi

    exit "$code"
}

case "${1:-help}" in
    build) build ;;
    release) release ;;
    all) all ;;
    help|-h|--help) usage ;;
    *) usage; exit 2 ;;
esac
