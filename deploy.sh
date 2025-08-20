#!/bin/bash
# 自動デプロイスクリプト（COREサーバー用）

# 設定
REPO_DIR="/virtual/nishidasj/public_html/iseyohin.geo.jp"
BACKUP_DIR="/virtual/nishidasj/backups"
LOG_FILE="/virtual/nishidasj/public_html/iseyohin.geo.jp/logs/deploy.log"
BRANCH="main"

# ログ関数
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# エラーハンドリング
set -e
trap 'log "ERROR: Deployment failed at line $LINENO"' ERR

log "=== Starting deployment ==="

# バックアップ作成
log "Creating backup..."
BACKUP_NAME="iseyohin_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
cp -r "$REPO_DIR" "$BACKUP_DIR/$BACKUP_NAME"
log "Backup created: $BACKUP_DIR/$BACKUP_NAME"

# リポジトリディレクトリに移動
cd "$REPO_DIR"

# Git操作
log "Fetching latest changes..."
git fetch origin "$BRANCH"

log "Checking out $BRANCH..."
git reset --hard "origin/$BRANCH"

# 権限設定
log "Setting permissions..."
chmod 755 storage/ storage/product_images/ logs/ 2>/dev/null || true
chmod 644 .env 2>/dev/null || true

# .env ファイル確認
if [ ! -f .env ]; then
    log "WARNING: .env file not found. Copying from .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
        chmod 600 .env
        log "Please update .env with your actual configuration"
    fi
fi

# Composer更新（vendor ディレクトリがある場合）
if [ -f composer.json ] && [ -f composer.lock ]; then
    log "Updating composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# キャッシュクリア（必要に応じて）
if [ -d cache/ ]; then
    log "Clearing cache..."
    rm -rf cache/*
fi

# ログファイル権限
chmod 666 logs/*.log 2>/dev/null || true

log "=== Deployment completed successfully ==="

# デプロイ情報表示
log "Current commit: $(git rev-parse HEAD)"
log "Current branch: $(git branch --show-current)"
log "Last commit message: $(git log -1 --pretty=format:'%s')"

echo "Deployment completed at $(date)"