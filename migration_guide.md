# COREサーバー サブドメイン環境への配置ガイド

## 🎯 現在の状況
- サブドメイン: `iseyohin.geo.jp`
- ドキュメントルート: `/public_html/iseyohin.geo.jp/`
- 現在のアプリ配置: `/public_html/iseyohin.geo.jp/iseyohin/`

## 🚀 推奨解決方法：ファイル移動

### 手順1: FTPでファイル移動

```
移動前の構造:
/public_html/iseyohin.geo.jp/
└── iseyohin/
    ├── index.php
    ├── public/
    ├── config/
    ├── src/
    ├── views/
    ├── database/
    ├── storage/
    ├── logs/
    ├── .htaccess
    └── setup.php

移動後の構造:
/public_html/iseyohin.geo.jp/
├── index.php        ← iseyohin/ から移動
├── public/          ← iseyohin/public/ から移動
├── config/          ← iseyohin/config/ から移動  
├── src/             ← iseyohin/src/ から移動
├── views/           ← iseyohin/views/ から移動
├── database/        ← iseyohin/database/ から移動
├── storage/         ← iseyohin/storage/ から移動
├── logs/            ← iseyohin/logs/ から移動
├── .htaccess        ← iseyohin/.htaccess から移動
├── .env             ← 新規作成（.env.example をコピー）
└── setup.php        ← iseyohin/setup.php から移動
```

### 手順2: FTPでの具体的移動方法

1. **FTPクライアント（FileZilla等）でサーバー接続**
2. **`/public_html/iseyohin.geo.jp/iseyohin/` に移動**
3. **全ファイル・フォルダを選択**
4. **`/public_html/iseyohin.geo.jp/` に移動・貼り付け**
5. **空になった `iseyohin/` フォルダを削除**

### 手順3: .env ファイル作成

`/public_html/iseyohin.geo.jp/.env` を作成：
```env
ENV=production
DEBUG=false

DB_HOST=mysql????.xserver.jp
DB_NAME=nishidasj_iseyohin
DB_USERNAME=nishidasj_iseyohin
DB_PASSWORD=your_password_here

MAIL_FROM_ADDRESS=noreply@iseyohin.geo.jp
MAIL_FROM_NAME=双康幼稚園用品申込システム
ADMIN_EMAIL=admin@yourdomain.com

APP_KEY=generate_random_32_character_string
ORDER_ENABLED=true
ORDER_START_DATE=2025-01-01
ORDER_END_DATE=2025-03-31
```

## 🔄 代替方法：リダイレクト設定

移動が困難な場合、`/public_html/iseyohin.geo.jp/.htaccess` を作成：

```apache
RewriteEngine On
RewriteBase /

# iseyohin ディレクトリ内のアプリにリダイレクト
RewriteCond %{REQUEST_URI} !^/iseyohin/
RewriteRule ^(.*)$ iseyohin/$1 [L]
```

## ✅ 動作確認

移動後、以下でアクセス確認：

1. **http://iseyohin.geo.jp/setup.php**
2. **http://iseyohin.geo.jp/**
3. **http://iseyohin.geo.jp/public/**

## 🛠️ トラブルシューティング

### 権限エラーが発生する場合
- storage/ を755に設定
- logs/ を755に設定
- .env を600に設定

### 404が続く場合
- ファイル移動が完了しているか確認
- .htaccess の構文エラーがないか確認
- PHPエラーログを確認

## 📞 最終確認

移動完了後：
1. `setup.php` で環境チェック
2. データベースマイグレーション実行
3. `setup.php` 削除
4. アプリケーション動作確認