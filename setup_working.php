<?php
/**
 * 動作する簡易セットアップページ
 * 複雑な処理を除去してエラーを回避
 */

// エラー表示を有効化（デバッグ用）
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>セットアップ</title>";
echo "<style>body{font-family:sans-serif;max-width:800px;margin:0 auto;padding:20px;}";
echo ".ok{color:green;} .ng{color:red;} .warn{color:orange;}</style></head><body>";

echo "<h1>🎯 双康幼稚園用品申込サイト セットアップ</h1>";

// 1. 基本情報
echo "<h2>📋 基本情報</h2>";
echo "<p>PHPバージョン: " . phpversion() . "</p>";
echo "<p>現在ディレクトリ: " . __DIR__ . "</p>";
echo "<p>サーバー時刻: " . date('Y-m-d H:i:s') . "</p>";

// 2. ファイル確認
echo "<h2>📁 ファイル確認</h2>";
$files = [
    '.env' => '環境変数ファイル',
    'config/config.php' => '設定ファイル',
    'public/index.php' => 'メインアプリ',
    'database/migrations/001_create_tables.sql' => 'データベーススキーマ'
];

foreach ($files as $file => $desc) {
    $exists = file_exists($file);
    $class = $exists ? 'ok' : 'ng';
    $status = $exists ? '✓' : '✗';
    echo "<p class='{$class}'>{$status} {$desc}: {$file}</p>";
}

// 3. 環境変数確認
echo "<h2>⚙️ 環境変数確認</h2>";
if (file_exists('.env')) {
    echo "<p class='ok'>✓ .envファイル存在</p>";
    
    // 手動で.env読み込み
    $envContent = file_get_contents('.env');
    $lines = explode("\n", $envContent);
    $env = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value, '"\'');
        }
    }
    
    $required = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];
    foreach ($required as $key) {
        $exists = isset($env[$key]) && !empty($env[$key]);
        $class = $exists ? 'ok' : 'ng';
        $status = $exists ? '✓' : '✗';
        $value = $exists ? '設定済み' : '未設定';
        echo "<p class='{$class}'>{$status} {$key}: {$value}</p>";
    }
} else {
    echo "<p class='ng'>✗ .envファイルが見つかりません</p>";
}

// 4. データベース接続テスト
echo "<h2>🗄️ データベース接続テスト</h2>";
if (isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USERNAME'], $env['DB_PASSWORD'])) {
    try {
        $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo "<p class='ok'>✓ データベース接続成功</p>";
        echo "<p>ホスト: {$env['DB_HOST']}</p>";
        echo "<p>データベース名: {$env['DB_NAME']}</p>";
        
        // テーブル一覧取得
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>既存テーブル数: " . count($tables) . "</p>";
        
        if (count($tables) === 0) {
            echo "<p class='warn'>⚠️ テーブルが作成されていません</p>";
            echo "<p>database/migrations/001_create_tables.sql を実行してください</p>";
        } else {
            echo "<p class='ok'>✓ テーブルが存在します</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>{$table}</li>";
            }
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<p class='ng'>✗ データベース接続失敗</p>";
        echo "<p>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='ng'>✗ データベース設定が不完全です</p>";
}

// 5. 次のステップ
echo "<h2>📋 次のステップ</h2>";
echo "<ol>";

if (!isset($pdo)) {
    echo "<li><strong>データベース接続を修正</strong></li>";
}

if (isset($pdo) && count($tables ?? []) === 0) {
    echo "<li><strong>データベーステーブル作成</strong><br>";
    echo "phpMyAdminで database/migrations/001_create_tables.sql を実行</li>";
}

echo "<li><strong>メインアプリケーションテスト</strong><br>";
echo "<a href='public/index.php'>public/index.php</a> にアクセス</li>";

echo "<li><strong>管理画面アクセス</strong><br>";
echo "public/admin にアクセス（admin/admin123）</li>";

echo "</ol>";

echo "<h2>🔗 リンク</h2>";
echo "<ul>";
echo "<li><a href='test_minimal.php'>最小テストページ</a></li>";
echo "<li><a href='public/index.php'>メインアプリケーション</a></li>";
echo "<li><a href='database/test_connection.php'>データベース接続テスト</a></li>";
echo "</ul>";

echo "<p><small>動作確認後、このファイルは削除してください。</small></p>";
echo "</body></html>";
?>