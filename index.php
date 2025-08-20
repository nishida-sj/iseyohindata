<?php
/**
 * 双康幼稚園用品申込サイト メインエントリーポイント
 * 
 * Author: [Your Name]
 * Date: 2025-08-18
 * Version: 1.0
 */

// エラー報告設定（本番環境では無効にする）
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// セッション開始
session_start();

// オートローダー設定
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 設定ファイル読み込み
require_once __DIR__ . '/config/config.php';

// コア機能読み込み
require_once __DIR__ . '/src/Core/Router.php';
require_once __DIR__ . '/src/Core/Controller.php';
require_once __DIR__ . '/src/Core/Model.php';
require_once __DIR__ . '/src/Core/Database.php';

// ヘルパー関数読み込み
require_once __DIR__ . '/src/Helpers/functions.php';

use App\Core\Router;

try {
    $router = new Router();
    $router->dispatch();
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    
    // 本番環境では汎用エラーメッセージを表示
    if (defined('ENV') && ENV === 'production') {
        http_response_code(500);
        include __DIR__ . '/views/error.php';
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>