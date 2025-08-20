<?php
/**
 * 双康幼稚園用品申込サイト - 設定ファイル
 * .envファイルから環境変数を読み込み、アプリケーション設定を定義
 */

// .envファイルの読み込み
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception('.env file not found: ' . $filePath);
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // コメント行をスキップ
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // KEY=VALUE形式の解析
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // クォートの除去
        $value = trim($value, '"\'');
        
        // 環境変数として設定
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

// .envファイルの読み込み
$envPath = dirname(__DIR__) . '/.env';
try {
    loadEnv($envPath);
} catch (Exception $e) {
    error_log("Config Error: " . $e->getMessage());
    die("設定ファイルの読み込みに失敗しました。");
}

// 基本設定
define('ENV', $_ENV['ENV'] ?? 'production');
define('DEBUG', filter_var($_ENV['DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN));
define('ROOT_PATH', dirname(__DIR__));

// データベース設定
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? '');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// メール設定
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'localhost');
define('MAIL_PORT', (int)($_ENV['MAIL_PORT'] ?? 25));
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? '');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? '');

// 管理者設定
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? '');

// セキュリティ設定
define('APP_KEY', $_ENV['APP_KEY'] ?? '');
define('CSRF_TOKEN_EXPIRE', (int)($_ENV['CSRF_TOKEN_EXPIRE'] ?? 3600));

// アップロード設定
define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 5242880)); // 5MB
define('ALLOWED_IMAGE_EXTENSIONS', explode(',', $_ENV['ALLOWED_IMAGE_EXTENSIONS'] ?? 'jpg,jpeg,png,gif'));
define('UPLOAD_PATH', ROOT_PATH . '/storage/product_images/');

// 申込設定
define('ORDER_ENABLED', filter_var($_ENV['ORDER_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('ORDER_START_DATE', $_ENV['ORDER_START_DATE'] ?? '2025-01-01');
define('ORDER_END_DATE', $_ENV['ORDER_END_DATE'] ?? '2025-12-31');

// 年齢区分設定
define('AGE_GROUPS', [
    '2' => '2歳児(ひよこ)',
    '3' => '3歳児(年少)',
    '4' => '4歳児(年中)',
    '5' => '5歳児(年長)'
]);

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// エラーハンドリング設定
if (ENV === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// ストレージディレクトリの確認・作成
$storageDirs = [
    ROOT_PATH . '/storage',
    ROOT_PATH . '/storage/product_images',
    ROOT_PATH . '/logs'
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 必須設定の検証
function validateConfig() {
    $required = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD', 'ADMIN_EMAIL'];
    $missing = [];
    
    foreach ($required as $key) {
        if (empty(constant($key))) {
            $missing[] = $key;
        }
    }
    
    if (!empty($missing)) {
        throw new Exception('必須の設定が不足しています: ' . implode(', ', $missing));
    }
}

try {
    validateConfig();
} catch (Exception $e) {
    error_log("Config Validation Error: " . $e->getMessage());
    if (ENV !== 'production') {
        die($e->getMessage());
    }
}
?>