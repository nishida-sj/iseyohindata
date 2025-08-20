<?php
/**
 * 双康幼稚園用品申込サイト - 公開エントリーポイント（修正版）
 * 
 * 複雑な処理を除去してシンプルに動作するバージョン
 */

// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// セッション開始
session_start();

// ルートパス設定
define('ROOT_PATH', dirname(__DIR__));

// 基本設定の手動読み込み
function loadEnvSimple($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $env[trim($name)] = trim($value, '"\'');
        }
    }
    
    return $env;
}

// 環境変数読み込み
$env = loadEnvSimple(ROOT_PATH . '/.env');

// 基本定数設定
define('ENV', $env['ENV'] ?? 'production');
define('DEBUG', filter_var($env['DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN));

// データベース設定
define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? '');
define('DB_USERNAME', $env['DB_USERNAME'] ?? '');
define('DB_PASSWORD', $env['DB_PASSWORD'] ?? '');

// 年齢区分設定
define('AGE_GROUPS', [
    '2' => '2歳児(ひよこ)',
    '3' => '3歳児(年少)',
    '4' => '4歳児(年中)',
    '5' => '5歳児(年長)'
]);

// 申込期間設定
define('ORDER_ENABLED', filter_var($env['ORDER_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('ORDER_START_DATE', $env['ORDER_START_DATE'] ?? '2025-01-01');
define('ORDER_END_DATE', $env['ORDER_END_DATE'] ?? '2025-12-31');

// 簡易URL生成関数
function url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/' . ltrim($path, '/');
}

// エスケープ関数
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 日付フォーマット関数
function format_date($date, $format = 'Y年m月d日') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// 申込期間チェック関数
function is_order_period() {
    $now = date('Y-m-d');
    return ORDER_ENABLED && 
           $now >= ORDER_START_DATE && 
           $now <= ORDER_END_DATE;
}

// CSRFトークン生成
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        time() - $_SESSION['csrf_token_time'] > 3600) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

// フラッシュメッセージ関数
function flash($type = null) {
    if ($type === null) {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
    
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}

try {
    // 簡単なルーティング
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    // public/ プレフィックスを除去
    if (strpos($path, '/public') === 0) {
        $path = substr($path, 7);
    }
    
    $path = rtrim($path, '/');
    if (empty($path)) {
        $path = '/';
    }
    
    // ルートページ表示
    if ($path === '/' || $path === '') {
        showHomePage();
    } elseif ($path === '/order') {
        showOrderPage();
    } else {
        // 404
        http_response_code(404);
        echo "<h1>404 - ページが見つかりません</h1>";
        echo "<p><a href='" . url() . "'>トップページに戻る</a></p>";
    }
    
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    
    if (ENV === 'production') {
        http_response_code(500);
        echo "<h1>システムエラー</h1>";
        echo "<p>申し訳ございませんが、システムエラーが発生しました。</p>";
    } else {
        echo "<h1>エラー</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

/**
 * ホームページ表示
 */
function showHomePage() {
    $page_title = '双康幼稚園用品申込サイト';
    $csrf_token = generateCsrfToken();
    $order_enabled = is_order_period();
    $age_groups = AGE_GROUPS;
    
    // 統計データ（簡易版）
    $stats = [
        'total_orders' => 0,
        'today_orders' => 0,
        'active_products' => 0
    ];
    
    // データベース接続で統計取得を試みる
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // 注文数取得
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
        $result = $stmt->fetch();
        $stats['total_orders'] = $result['count'];
        
        // 今日の注文数
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = ?");
        $stmt->execute([date('Y-m-d')]);
        $result = $stmt->fetch();
        $stats['today_orders'] = $result['count'];
        
        // 有効商品数
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
        $result = $stmt->fetch();
        $stats['active_products'] = $result['count'];
        
    } catch (Exception $e) {
        error_log("Database stats error: " . $e->getMessage());
    }
    
    include __DIR__ . '/home_template.php';
}

/**
 * 申込ページ表示
 */
function showOrderPage() {
    echo "<h1>申込ページ</h1>";
    echo "<p>申込機能は準備中です。</p>";
    echo "<p><a href='" . url() . "'>トップページに戻る</a></p>";
}
?>