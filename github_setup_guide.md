# GitHub連携設定ガイド

## 🎯 目標
- GitHubリポジトリへのプッシュで自動更新
- COREサーバーでWebhook受信
- セキュアな自動デプロイ

## 🔧 手順1: COREサーバーでSSHキー設定

### 1-1. SSH接続でキー生成
```bash
# COREサーバーにSSH接続後
cd /virtual/nishidasj/public_html/iseyohin.geo.jp

# SSHキー生成
ssh-keygen -t rsa -b 4096 -C "nishida-sj@github-deploy"

# 公開キー表示
cat ~/.ssh/id_rsa.pub
```

### 1-2. GitHubにデプロイキー追加
1. GitHub repository → Settings
2. Deploy keys → Add deploy key
3. 上記の公開キーを貼り付け
4. "Allow write access" にチェック

## 🔧 手順2: GitHubリポジトリをクローン

### 2-1. 既存ファイルをバックアップ
```bash
# 現在のファイルをバックアップ
mv /virtual/nishidasj/public_html/iseyohin.geo.jp /virtual/nishidasj/backup_$(date +%Y%m%d)

# 新しくクローン
cd /virtual/nishidasj/public_html
git clone git@github.com:nishida-sj/iseyohindata.git iseyohin.geo.jp
```

### 2-2. 権限設定
```bash
cd iseyohin.geo.jp
chmod 755 storage storage/product_images logs
chmod 600 .env
```

## 🔧 手順3: Webhook設定

### 3-1. 自動更新スクリプト作成
```bash
# webhook受信用スクリプト
vi webhook.php
```

### 3-2. GitHubでWebhook設定
1. GitHub repository → Settings → Webhooks
2. Payload URL: `http://iseyohin.geo.jp/webhook.php`
3. Content type: `application/json`
4. Secret: 安全なパスワード設定
5. Events: Push events

## 🔧 手順4: 自動更新の動作確認

### 4-1. テストプッシュ
```bash
# ローカルで変更
echo "# Test update" >> README.md
git add README.md
git commit -m "Test webhook deployment"
git push origin main
```

### 4-2. サーバーで確認
- Webhookが実行されたか
- ファイルが更新されたか
- 権限が保持されているか

## ⚠️ 注意事項

1. **.env ファイルはリポジトリに含めない**
2. **ログファイルは .gitignore に追加**
3. **Webhook URLは外部に漏らさない**
4. **定期的にバックアップを取る**

## 🔍 トラブルシューティング

### SSH接続できない場合
- COREサーバーのSSH設定確認
- ファイアウォール設定確認

### Webhook実行されない場合
- GitHub側のWebhook履歴確認
- サーバーのエラーログ確認

### 権限エラーの場合
- storage/, logs/ の権限再設定
- .env ファイルの権限確認