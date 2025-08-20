<?php
// 簡易セットアップページ - エラー原因特定用
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>簡易セットアップ</h1>";

try {
    echo "<h2>1. 基本情報</h2>";
    echo "PHPバージョン: " . phpversion() . "<br>";
    echo "現在ディレクトリ: " . __DIR__ . "<br>";
    
    echo "<h2>2. .envファイル確認</h2>";
    if (file_exists('.env')) {
        echo "✓ .envファイル存在<br>";
        
        // .env読み込みテスト
        $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env = [];
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $env[trim($name)] = trim($value, '"\'');
            }
        }
        
        echo "DB_HOST: " . ($env['DB_HOST'] ?? '未設定') . "<br>";
        echo "DB_NAME: " . ($env['DB_NAME'] ?? '未設定') . "<br>";
        echo "DB_USERNAME: " . ($env['DB_USERNAME'] ?? '未設定') . "<br>";
        echo "DB_PASSWORD: " . (isset($env['DB_PASSWORD']) ? '設定済み' : '未設定') . "<br>";
        
    } else {
        echo "✗ .envファイルが見つかりません<br>";
    }
    
    echo "<h2>3. データベース接続テスト</h2>";
    if (isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USERNAME'], $env['DB_PASSWORD'])) {
        try {
            $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
            $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
            echo "✓ データベース接続成功<br>";
            
            // テーブル確認
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "テーブル数: " . count($tables) . "<br>";
            
        } catch (Exception $e) {
            echo "✗ データベース接続失敗: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "データベース設定が不完全です<br>";
    }
    
    echo "<h2>4. 設定ファイル確認</h2>";
    if (file_exists('config/config.php')) {
        echo "✓ config/config.php 存在<br>";
        
        // config.phpの読み込みテスト
        try {
            ob_start();
            include 'config/config.php';
            $output = ob_get_clean();
            echo "✓ config.php 読み込み成功<br>";
        } catch (Exception $e) {
            echo "✗ config.php 読み込みエラー: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✗ config/config.php が見つかりません<br>";
    }
    
    echo "<h2>完了</h2>";
    echo "<p>この画面が表示されれば基本的なPHP動作は正常です。</p>";
    echo "<p><a href='test_minimal.php'>最小テストページ</a></p>";
    
} catch (Exception $e) {
    echo "<h2>エラー発生</h2>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>