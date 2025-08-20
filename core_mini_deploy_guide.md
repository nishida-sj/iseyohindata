# CORE-MINI対応GitHub連携ガイド

## 🎯 CORE-MINIプランでの制約
- SSH接続: ❌ 不可
- Git機能: ❌ 不可  
- Cron: ❌ 不可

## 🚀 推奨方法: Webベース手動デプロイ

### 方法1: deploy_manual.php（最推奨）

#### メリット
- ✅ CORE-MINIで完全動作
- ✅ ワンクリックデプロイ
- ✅ 自動バックアップ
- ✅ セキュア認証

#### 設定手順
1. **deploy_manual.php をFTPでアップロード**
2. **パスワード設定（ファイル内で編集）**
   ```php
   $DEPLOY_SECRET = 'YourSecurePassword2025!';
   ```
3. **http://iseyohin.geo.jp/deploy_manual.php でアクセス**

#### 運用フロー
```
ローカル編集 → GitHub push → サーバーでデプロイ実行
```

### 方法2: FTP同期ツール

#### FileZilla Pro / WinSCP Pro
- **ローカルフォルダ監視**
- **変更時自動アップロード**
- **除外パターン設定**

#### 設定例（FileZilla）
```
監視フォルダ: C:\dev\iseyohindata
リモートフォルダ: /public_html/iseyohin.geo.jp
除外: .git/, logs/, .env, storage/product_images/*
```

### 方法3: VS Code拡張機能

#### SFTP拡張機能
```json
{
    "name": "iseyohin-deploy",
    "host": "ftp.coreserver.jp",
    "protocol": "sftp",
    "port": 22,
    "username": "nishidasj",
    "remotePath": "/public_html/iseyohin.geo.jp",
    "uploadOnSave": true,
    "ignore": [".git", "logs", ".env", "storage/product_images"]
}
```

## 🔧 Webhook対応（制限あり）

### 簡易Webhook受信
CORE-MINIでもPHPスクリプトでWebhook受信は可能：

#### webhook_simple.php の動作
1. **GitHubからPUSH通知受信**
2. **管理者にメール通知**
3. **ログに記録**
4. **手動デプロイを促すメッセージ**

```php
// Webhook受信後の動作例
function onGitHubPush($data) {
    // メール通知
    mail(ADMIN_EMAIL, '更新通知', 'GitHubが更新されました。デプロイしてください。');
    
    // ログ記録
    writeLog('GitHub push received: ' . $data['head_commit']['message']);
    
    // 自動デプロイは不可（CORE-MINI制限）
}
```

## 📋 CORE-MINI最適化運用

### 推奨ワークフロー

1. **開発環境（ローカル）**
   ```
   コード編集 → Git commit → GitHub push
   ```

2. **本番反映**
   ```
   deploy_manual.php アクセス → パスワード入力 → デプロイ実行
   ```

3. **緊急対応**
   ```
   FTPで直接ファイル編集 → 後でGitHubに反映
   ```

### 権限・セキュリティ

#### deploy_manual.php のセキュリティ設定
```php
// IP制限（オプション）
$ALLOWED_IPS = ['your.office.ip', 'your.home.ip'];

// アクセス時間制限（オプション）
$ALLOWED_HOURS = [9, 10, 11, 12, 13, 14, 15, 16, 17]; // 9-17時のみ

// デプロイ後の自動削除（セキュリティ重視の場合）
$DELETE_AFTER_DEPLOY = true;
```

#### ファイル権限
```bash
# FTPで設定すべき権限
deploy_manual.php: 644
.env: 600
storage/: 755
logs/: 755
```

## 🎯 運用コスト比較

### CORE-MINI継続の場合
- **月額費用**: 220円
- **デプロイ**: 手動（deploy_manual.php）
- **運用負荷**: 中程度

### V1プランアップグレード
- **月額費用**: 528円
- **デプロイ**: 完全自動（Git機能）
- **運用負荷**: 低

### 判断基準
- **更新頻度が高い** → V1推奨
- **コスト重視** → CORE-MINI + deploy_manual.php
- **完全自動化したい** → V1推奨

## 💡 CORE-MINI最大活用のコツ

1. **deploy_manual.php を活用**
2. **FTP同期ツールとの併用**
3. **緊急時はFTP直接編集**
4. **定期的なバックアップ実行**

この方法でCORE-MINIでも効率的なGitHub連携が実現できます！