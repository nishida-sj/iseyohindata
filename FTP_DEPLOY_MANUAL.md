# FTPベース手動デプロイガイド

## 🎯 概要
CORE-MINIプラン向けFTPベースのGitHub連携デプロイシステム

## 📋 必要なファイル

### 1. デプロイスクリプト
- `deploy_manual_core_mini.php` → サーバーにアップロード

### 2. 設定情報
- **デプロイパスワード**: `NishidaSJ`
- **GitHubリポジトリ**: `nishida-sj/iseyohindata`
- **アップロード先**: `/public_html/iseyohin.geo.jp/`

## 🚀 FTP実装手順

### ステップ1: FTPでファイルアップロード
```
FTPソフト（FileZilla等）を使用
接続先: ftp.coreserver.jp
ユーザー名: nishidasj
パスワード: [COREサーバーパスワード]
```

### ステップ2: アップロードファイル一覧
```
/public_html/iseyohin.geo.jp/
  ├── deploy_manual_core_mini.php  ← 新規アップロード
  ├── index.php
  ├── .env
  ├── .htaccess
  └── その他既存ファイル
```

### ステップ3: デプロイURL確認
```
http://iseyohin.geo.jp/deploy_manual_core_mini.php
```

### ステップ4: 初回デプロイ実行
1. 上記URLにアクセス
2. パスワード入力: `NishidaSJ`
3. 確認チェック → デプロイ実行

## 🔧 運用フロー

### 日常の更新作業
```
1. ローカル環境でコード修正
   ↓
2. GitHub pushでリポジトリ更新
   ↓
3. デプロイURLにアクセス
   ↓
4. パスワード入力・デプロイ実行
   ↓
5. サイト動作確認
```

### 緊急時対応
```
直接FTP → ファイル編集 → 後でGitHubに反映
```

## 🛡️ セキュリティ設定

### パスワード保護
- デプロイ実行にパスワード必須
- ブルートフォース対策（1時間5回制限）

### ファイル保護
- `.env`ファイル保持
- `storage/product_images/`保持  
- ログファイル保持
- 自動バックアップ作成

## 📱 対応ブラウザ
- Chrome, Firefox, Safari, Edge
- スマートフォンでも操作可能

## ⚡ CORE-MINI最適化
- 軽量HTTPリクエスト
- メモリ使用量最小化
- タイムアウト対策

## 🔍 トラブルシューティング

### デプロイ失敗時
1. ログファイル確認: `/logs/deploy.log`
2. パーミッション確認
3. 一時ファイル削除

### アクセス不可時
1. FTPでファイル存在確認
2. `.htaccess`無効化テスト
3. PHPエラーログ確認

これでCORE-MINIでFTPベースの効率的GitHub連携デプロイが可能です！