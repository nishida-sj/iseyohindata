# 双康幼稚園用品申込サイト

COREサーバー CORE-MINI 対応のPHP製用品申込システムです。

## 📋 目次

- [概要](#概要)
- [主要機能](#主要機能)
- [技術仕様](#技術仕様)
- [インストール手順](#インストール手順)
- [設定](#設定)
- [使用方法](#使用方法)
- [セキュリティ](#セキュリティ)
- [トラブルシューティング](#トラブルシューティング)
- [ライセンス](#ライセンス)

## 🎯 概要

双康幼稚園用品申込サイトは、園児の用品申込をオンラインで効率的に行えるWebアプリケーションです。
COREサーバーなどの共用サーバー環境での稼働を前提とした軽量なMVCアーキテクチャで構築されています。

### 特徴

- ✅ **軽量MVC設計**: フレームワーク非依存の高速動作
- ✅ **共用サーバー対応**: COREサーバー CORE-MINI 最適化
- ✅ **セキュリティ重視**: CSRF・SQLインジェクション対策済み
- ✅ **レスポンシブデザイン**: スマートフォン・タブレット対応
- ✅ **年齢別商品管理**: 2歳児〜5歳児の年齢区分対応
- ✅ **メール通知**: 注文完了時の自動メール送信
- ✅ **集計・CSV出力**: 売上データの分析機能

## 🚀 主要機能

### 一般ユーザー機能
- **商品申込**: 年齢に応じた商品選択・注文
- **注文確認**: 入力内容の確認・修正
- **注文完了**: 申込番号発行・完了画面

### 管理機能
- **商品管理**: 商品マスタのCRUD操作
- **年齢別設定**: 年齢区分ごとの表示商品管理
- **注文管理**: 注文データの閲覧・検索
- **集計レポート**: 期間指定での売上分析
- **CSV出力**: 注文データ・集計データのエクスポート

### システム機能
- **画像アップロード**: 商品画像の管理
- **メール送信**: 注文通知・お問い合わせ対応
- **ログ管理**: システムログ・エラーログ
- **セッション管理**: 管理者認証・CSRF対策

## ⚙️ 技術仕様

### 基本環境
- **PHP**: 8.0 以上
- **データベース**: MySQL 5.7 以上
- **Webサーバー**: Apache（.htaccess対応）
- **メール**: PHP mail() / mb_send_mail()

### ライブラリ・フレームワーク
- **フロントエンド**: Bootstrap 5.1、Font Awesome 6
- **バックエンド**: 独自軽量MVCフレームワーク
- **データベース**: PDO（プリペアドステートメント）
- **セキュリティ**: 独自CSRF・XSS対策実装

### ファイル構成
```
iseyohin/
├── index.php                 # アプリケーション起動
├── public/                   # Webルート
│   ├── index.php            # 公開エントリーポイント
│   └── .htaccess            # リライト設定
├── config/                   # 設定ファイル
│   └── config.php           # アプリケーション設定
├── src/                      # ソースコード
│   ├── Core/                # コアクラス
│   ├── Controllers/         # コントローラー
│   ├── Models/              # モデル
│   ├── Services/            # サービスクラス
│   └── Helpers/             # ヘルパー関数
├── views/                    # ビューファイル
├── database/                 # データベース関連
│   ├── migrations/          # マイグレーションSQL
│   └── test_connection.php  # 接続テスト
├── storage/                  # ストレージ
│   └── product_images/      # 商品画像
├── logs/                     # ログファイル
├── .env.example             # 環境変数サンプル
├── .htaccess                # ルートレベル設定
└── README.md                # このファイル
```

## 🛠 インストール手順

### 1. ファイルアップロード
```bash
# GitHubからクローン
git clone https://github.com/nishida-sj/iseyohindata.git
cd iseyohindata

# またはZIPファイルをアップロード・展開
```

### 2. 環境変数設定
```bash
# .env.example をコピーして .env を作成
cp .env.example .env

# .env ファイルを編集
nano .env
```

### 3. データベース準備
```sql
-- phpMyAdminまたはMySQLクライアントで実行
-- データベース作成（既に作成済みの場合はスキップ）
CREATE DATABASE nishidasj_iseyohin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- マイグレーション実行
SOURCE database/migrations/001_create_tables.sql;
```

### 4. ディレクトリ権限設定
```bash
# 書き込み権限を設定
chmod 755 storage/
chmod 755 storage/product_images/
chmod 755 logs/
chmod 600 .env
```

### 5. 動作確認
```bash
# データベース接続テスト
php database/test_connection.php

# Webブラウザでアクセス
http://yourdomain.com/
```

## ⚙️ 設定

### 環境変数（.env）

#### 基本設定
```env
ENV=production
DEBUG=false
```

#### データベース設定
```env
DB_HOST=mysql????.xserver.jp
DB_NAME=nishidasj_iseyohin
DB_USERNAME=nishidasj_iseyohin
DB_PASSWORD=your_secure_password
DB_CHARSET=utf8mb4
```

#### メール設定
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=双康幼稚園用品申込システム
ADMIN_EMAIL=admin@yourdomain.com
```

#### セキュリティ設定
```env
APP_KEY=your_32_character_random_string_here
CSRF_TOKEN_EXPIRE=3600
```

#### 申込設定
```env
ORDER_ENABLED=true
ORDER_START_DATE=2025-01-01
ORDER_END_DATE=2025-03-31
```

### Webサーバー設定

#### Apache .htaccess
プロジェクトには適切な .htaccess ファイルが含まれていますが、
共用サーバーの仕様に応じて調整が必要な場合があります。

#### PHP設定
```ini
; 最小要件
upload_max_filesize = 5M
post_max_size = 8M
memory_limit = 128M
max_execution_time = 30
```

## 📖 使用方法

### 初期セットアップ

1. **管理者ログイン**
   - URL: `http://yourdomain.com/admin`
   - ユーザー名: `admin`
   - パスワード: `admin123`（初回ログイン後変更推奨）

2. **商品登録**
   - 管理画面 > 商品管理
   - 商品情報・画像を登録

3. **年齢別設定**
   - 管理画面 > 年齢別商品設定
   - 各年齢区分で表示する商品を選択

### 日常運用

#### 注文確認
1. 管理画面 > 注文管理
2. 新着注文を確認
3. 必要に応じてCSV出力

#### 集計・分析
1. 管理画面 > 集計レポート
2. 期間を指定して集計実行
3. CSVダウンロードで詳細分析

## 🔐 セキュリティ

### 実装済み対策
- ✅ **CSRFトークン**: 全フォームでトークン検証
- ✅ **SQLインジェクション**: プリペアドステートメント使用
- ✅ **XSS対策**: 出力時の適切なエスケープ
- ✅ **セッション管理**: 適切なセッション設定
- ✅ **ファイルアップロード**: 拡張子・MIMEタイプ検証
- ✅ **アクセス制御**: 管理画面の認証・認可
- ✅ **エラーハンドリング**: 本番環境での情報漏洩防止

### セキュリティチェックリスト

#### インストール後の確認事項
- [ ] `.env` ファイルのパーミッション（600）
- [ ] デフォルト管理者パスワードの変更
- [ ] `database/test_connection.php` の削除
- [ ] エラーログの定期確認
- [ ] HTTPSの有効化（推奨）

#### 定期メンテナンス
- [ ] システムログの確認（週1回）
- [ ] データベースバックアップ（日次）
- [ ] 古いログファイルの削除（月次）
- [ ] セキュリティアップデートの適用

## 🔧 トラブルシューティング

### よくある問題と解決方法

#### データベース接続エラー
```
エラー: データベース接続に失敗しました
```
**解決方法:**
1. `.env` の接続情報を確認
2. データベースサーバーの稼働状況確認
3. `database/test_connection.php` で接続テスト

#### 画像アップロードエラー
```
エラー: ファイルのアップロードに失敗しました
```
**解決方法:**
1. `storage/product_images/` の権限確認（755）
2. PHP設定の `upload_max_filesize` 確認
3. ディスク容量の確認

#### メール送信エラー
```
エラー: メールの送信に失敗しました
```
**解決方法:**
1. `.env` のメール設定確認
2. SMTPサーバー設定確認
3. `src/Services/MailService.php` のテストメール送信

#### 管理画面にアクセスできない
```
エラー: 404 Not Found
```
**解決方法:**
1. `.htaccess` の設定確認
2. `mod_rewrite` の有効化確認
3. パスの設定確認

### ログファイルの確認
```bash
# エラーログ確認
tail -f logs/error.log

# アプリケーションログ確認
tail -f logs/app.log
```

### デバッグモード
開発環境では `.env` で `DEBUG=true` に設定することで
詳細なエラー情報を表示できます（本番環境では無効にしてください）。

## 📊 パフォーマンス

### 最適化設定
- データベースインデックス最適化済み
- 画像リサイズ機能（800x600px）
- CSS/JS最小化（CDN使用）
- 適切なキャッシュヘッダー設定

### 推奨環境
- **メモリ**: 128MB以上
- **ストレージ**: 500MB以上
- **CPU**: 共用サーバー標準スペック
- **同時接続**: 100接続以下での使用を想定

## 📚 API仕様

### 年齢別商品取得
```
GET /api/products/age/{age_group}

Response:
{
  "success": true,
  "age_group": "3",
  "age_group_label": "3歳児(年少)",
  "products": [...],
  "count": 8
}
```

### CSRFトークン取得
```
POST /api/csrf-token

Response:
{
  "success": true,
  "csrf_token": "..."
}
```

## 🚀 拡張・カスタマイズ

### 新機能追加の手順

1. **モデル作成**: `src/Models/` に新しいモデルクラス
2. **コントローラー作成**: `src/Controllers/` に機能別コントローラー
3. **ルート追加**: `src/Core/Router.php` にルート定義
4. **ビュー作成**: `views/` にテンプレートファイル
5. **テスト作成**: `tests/` にユニットテスト

### カスタマイズ例

#### 新しい商品カテゴリ追加
1. データベーススキーマ更新
2. `Product` モデルの拡張
3. 管理画面への機能追加

#### 決済機能追加
1. 決済サービス連携
2. `Order` モデルの拡張
3. 決済フロー追加

## 🧪 テスト

### ユニットテスト実行
```bash
# テスト実行
php tests/run_tests.php

# 特定のテスト実行
php tests/models/ProductTest.php
```

### 手動テスト項目
- [ ] 商品申込フロー
- [ ] 管理者ログイン
- [ ] 商品CRUD操作
- [ ] メール送信
- [ ] CSV出力
- [ ] 画像アップロード

## 📝 変更履歴

### v1.0.0 (2025-08-18)
- 初回リリース
- 基本的な申込機能実装
- 管理機能実装
- セキュリティ対策実装

## 📄 ライセンス

このプロジェクトはMITライセンスの下でライセンスされています。
詳細は [LICENSE](LICENSE) ファイルをご覧ください。

## 🤝 サポート

### お問い合わせ
- 技術的な質問: GitHub Issues
- 緊急時サポート: 管理者まで直接連絡

### 貢献方法
1. このリポジトリをフォーク
2. 機能ブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add amazing feature'`)
4. ブランチをプッシュ (`git push origin feature/amazing-feature`)
5. Pull Requestを開く

## 📞 緊急時対応

### システム停止時
1. エラーログ確認
2. データベース接続確認
3. サーバー容量確認
4. 必要に応じてメンテナンス画面表示

### データ破損時
1. 最新バックアップからの復旧
2. データ整合性チェック
3. ログ分析による原因調査

---

**開発者**: nishida-sj  
**最終更新**: 2025年8月18日  
**バージョン**: 1.0.0

このREADMEは継続的に更新されます。最新情報はGitHubリポジトリをご確認ください。