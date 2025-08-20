<?php
/**
 * 双康幼稚園用品申込サイト - セットアップスクリプト
 * 
 * 初回インストール時の環境チェックとディレクトリ作成を行います
 */

echo "<!DOCTYPE html>\n<html lang='ja'>\n<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "<title>双康幼稚園用品申込サイト - セットアップ</title>\n";
echo "<style>\n";
echo "body{font-family:sans-serif;max-width:800px;margin:0 auto;padding:20px;}\n";
echo ".success{color:#28a745;} .warning{color:#ffc107;} .error{color:#dc3545;}\n";
echo ".box{border:1px solid #ddd;padding:15px;margin:10px 0;border-radius:5px;}\n";
echo "</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🎯 双康幼稚園用品申込サイト セットアップ</h1>\n";
echo "<p>システムの初期セットアップを行います。</p>\n";

// 1. ディレクトリ作成
echo "<div class='box'>\n";
echo "<h2>📁 ディレクトリ作成</h2>\n";

$directories = [
    'storage' => '755',
    'storage/product_images' => '755',
    'logs' => '755'
];

foreach ($directories as $dir => $permission) {
    $fullPath = __DIR__ . '/' . $dir;
    
    echo "<p><strong>{$dir}</strong>: ";
    
    if (is_dir($fullPath)) {
        echo "<span class='warning'>既に存在します</span>";
    } else {
        if (mkdir($fullPath, octdec($permission), true)) {
            echo "<span class='success'>作成成功 (権限: {$permission})</span>";
        } else {
            echo "<span class='error'>作成失敗</span>";
        }
    }
    
    // 権限確認
    if (is_dir($fullPath)) {
        $currentPerm = substr(sprintf('%o', fileperms($fullPath)), -3);
        echo " - 現在の権限: {$currentPerm}";
        
        if (is_writable($fullPath)) {
            echo " <span class='success'>✓書き込み可</span>";
        } else {
            echo " <span class='error'>✗書き込み不可</span>";
        }
    }
    
    echo "</p>\n";
}
echo "</div>\n";

// 2. 環境変数ファイル確認
echo "<div class='box'>\n";
echo "<h2>⚙️ 環境設定ファイル</h2>\n";

$envExample = __DIR__ . '/.env.example';
$envFile = __DIR__ . '/.env';

echo "<p><strong>.env.example</strong>: ";
if (file_exists($envExample)) {
    echo "<span class='success'>存在します</span></p>\n";
} else {
    echo "<span class='error'>見つかりません</span></p>\n";
}

echo "<p><strong>.env</strong>: ";
if (file_exists($envFile)) {
    echo "<span class='success'>設定済み</span></p>\n";
} else {
    echo "<span class='warning'>未設定</span> - .env.exampleをコピーして作成してください</p>\n";
    echo "<pre style='background:#f8f9fa;padding:10px;border-radius:3px;'>cp .env.example .env</pre>\n";
}
echo "</div>\n";

// 3. PHP環境チェック
echo "<div class='box'>\n";
echo "<h2>🐘 PHP環境</h2>\n";

$phpVersion = phpversion();
echo "<p><strong>PHPバージョン</strong>: {$phpVersion}";
if (version_compare($phpVersion, '8.0.0', '>=')) {
    echo " <span class='success'>✓対応</span></p>\n";
} else {
    echo " <span class='warning'>⚠️PHP 8.0以上推奨</span></p>\n";
}

$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'gd', 'fileinfo'];
foreach ($extensions as $ext) {
    echo "<p><strong>{$ext}</strong>: ";
    if (extension_loaded($ext)) {
        echo "<span class='success'>✓有効</span></p>\n";
    } else {
        echo "<span class='error'>✗無効</span></p>\n";
    }
}

echo "<p><strong>メモリ制限</strong>: " . ini_get('memory_limit') . "</p>\n";
echo "<p><strong>最大アップロードサイズ</strong>: " . ini_get('upload_max_filesize') . "</p>\n";
echo "<p><strong>最大POST</strong>: " . ini_get('post_max_size') . "</p>\n";
echo "</div>\n";

// 4. データベース接続テスト
echo "<div class='box'>\n";
echo "<h2>🗄️ データベース接続</h2>\n";

if (file_exists($envFile)) {
    try {
        // 環境変数読み込み
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env = [];
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
            
            list($name, $value) = explode('=', $line, 2);
            $env[trim($name)] = trim($value, '"\'');
        }
        
        if (isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USERNAME'], $env['DB_PASSWORD'])) {
            $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
            
            echo "<p><span class='success'>✓データベース接続成功</span></p>\n";
            echo "<p>ホスト: {$env['DB_HOST']}<br>データベース名: {$env['DB_NAME']}</p>\n";
            
        } else {
            echo "<p><span class='warning'>データベース設定が不完全です</span></p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p><span class='error'>データベース接続失敗: " . htmlspecialchars($e->getMessage()) . "</span></p>\n";
    }
} else {
    echo "<p><span class='warning'>.envファイルが見つかりません</span></p>\n";
}
echo "</div>\n";

// 5. セキュリティチェック
echo "<div class='box'>\n";
echo "<h2>🔒 セキュリティ</h2>\n";

$securityFiles = [
    '.htaccess' => 'ルートレベル制御',
    'public/.htaccess' => '公開ディレクトリ制御', 
    'storage/product_images/.htaccess' => '画像ディレクトリ保護',
    'logs/.htaccess' => 'ログディレクトリ保護'
];

foreach ($securityFiles as $file => $desc) {
    echo "<p><strong>{$file}</strong> ({$desc}): ";
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<span class='success'>✓設定済み</span></p>\n";
    } else {
        echo "<span class='error'>✗未設定</span></p>\n";
    }
}

if (file_exists($envFile)) {
    $perm = substr(sprintf('%o', fileperms($envFile)), -3);
    echo "<p><strong>.envファイル権限</strong>: {$perm} ";
    if ($perm === '600') {
        echo "<span class='success'>✓安全</span></p>\n";
    } else {
        echo "<span class='warning'>⚠️600推奨</span></p>\n";
    }
}
echo "</div>\n";

// 6. 次のステップ
echo "<div class='box'>\n";
echo "<h2>📋 次のステップ</h2>\n";
echo "<ol>\n";

if (!file_exists($envFile)) {
    echo "<li><strong>.envファイルを作成</strong>: .env.exampleをコピーして設定</li>\n";
}

echo "<li><strong>データベースマイグレーション実行</strong>:<br>\n";
echo "<code>database/migrations/001_create_tables.sql</code>をphpMyAdminで実行</li>\n";

echo "<li><strong>接続テスト実行</strong>:<br>\n";
echo "<a href='database/test_connection.php' target='_blank'>database/test_connection.php</a></li>\n";

echo "<li><strong>アプリケーション起動</strong>:<br>\n";
echo "<a href='public/' target='_blank'>public/</a> (またはメインサイト)</li>\n";

echo "<li><strong>管理画面アクセス</strong>:<br>\n";
echo "<a href='public/admin' target='_blank'>public/admin</a> (admin/admin123)</li>\n";

echo "<li><strong>セキュリティ設定</strong>:\n";
echo "<ul>\n";
echo "<li>管理者パスワード変更</li>\n";
echo "<li>このsetup.phpファイルを削除</li>\n";
echo "<li>.envファイルの権限を600に設定</li>\n";
echo "</ul></li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div style='text-align:center;margin:30px 0;'>\n";
echo "<p><strong>🎉 セットアップ準備完了！</strong></p>\n";
echo "<p><small>問題がある場合は、README.mdのトラブルシューティングをご確認ください。</small></p>\n";
echo "</div>\n";

echo "</body>\n</html>";
?>