# SSH不要のGitHub連携ガイド

## 🎯 COREサーバーのWeb管理でGitHub連携

### 方法1: COREサーバーのGit機能（推奨）

#### 1-1. COREサーバー管理画面ログイン
1. COREサーバーコントロールパネルにログイン
2. 「アプリケーション」→「Git」メニューを探す
3. 「リポジトリ作成/クローン」を選択

#### 1-2. GitHubリポジトリをクローン
- **リポジトリURL**: `https://github.com/nishida-sj/iseyohindata.git`
- **デプロイ先**: `/public_html/iseyohin.geo.jp`
- **ブランチ**: `main`

#### 1-3. 自動デプロイ設定
- 「Webhook設定」で自動更新を有効化
- プッシュ時の自動デプロイを設定

### 方法2: FTPでWebhook設定（Manual）

#### 2-1. 現在のファイルをGitHubにアップロード
```bash
# ローカルでリポジトリクローン
git clone https://github.com/nishida-sj/iseyohindata.git
cd iseyohindata

# 作成したファイルをすべてコピー
# （FTPでダウンロードしたファイルをローカルリポジトリに配置）

git add .
git commit -m "Complete kindergarten order system implementation"
git push origin main
```

#### 2-2. Webhook受信スクリプトをFTPでアップロード
- `webhook.php` をFTPで `/public_html/iseyohin.geo.jp/` にアップロード
- `deploy_manual.php` をFTPでアップロード

#### 2-3. GitHub Webhookから手動デプロイページを呼び出し

### 方法3: 簡易自動更新システム

#### 3-1. 更新確認スクリプト
- 定期的にGitHubの最新コミットをチェック
- 変更があった場合にZIPダウンロード＆展開

#### 3-2. 管理画面から手動更新ボタン
- 管理者が管理画面から「更新」ボタンクリック
- GitHubから最新版をダウンロード