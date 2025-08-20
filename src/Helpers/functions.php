<?php
/**
 * ヘルパー関数群
 * 
 * アプリケーション全体で使用する汎用関数を定義
 */

/**
 * CSRFトークンのHTMLフィールドを生成
 */
function csrf_field() {
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * CSRFトークンを取得
 */
function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * URLを生成
 */
function url($path = '') {
    return App\Core\Router::url($path);
}

/**
 * 古い入力値を取得（バリデーションエラー時の値保持用）
 */
function old($key, $default = '') {
    return $_SESSION['old_input'][$key] ?? $default;
}

/**
 * 古い入力値を設定
 */
function set_old_input($data) {
    $_SESSION['old_input'] = $data;
}

/**
 * 古い入力値をクリア
 */
function clear_old_input() {
    unset($_SESSION['old_input']);
}

/**
 * エラーメッセージを取得
 */
function error($key) {
    return $_SESSION['errors'][$key] ?? [];
}

/**
 * エラーメッセージの存在確認
 */
function has_error($key) {
    return isset($_SESSION['errors'][$key]) && !empty($_SESSION['errors'][$key]);
}

/**
 * エラーメッセージを設定
 */
function set_errors($errors) {
    $_SESSION['errors'] = $errors;
}

/**
 * エラーメッセージをクリア
 */
function clear_errors() {
    unset($_SESSION['errors']);
}

/**
 * フラッシュメッセージを取得
 */
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

/**
 * フラッシュメッセージを設定
 */
function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * 現在の日時を取得（フォーマット済み）
 */
function now($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * 日付フォーマット
 */
function format_date($date, $format = 'Y年m月d日') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * 金額フォーマット
 */
function format_price($amount) {
    return number_format($amount) . '円';
}

/**
 * 安全なHTMLエスケープ
 */
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * 配列から安全に値を取得
 */
function array_get($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * 画像URLを生成
 */
function image_url($filename) {
    if (empty($filename)) {
        return url('assets/images/no-image.png');
    }
    return url('storage/product_images/' . $filename);
}

/**
 * ファイルサイズを人間が読みやすい形式に変換
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $power = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}

/**
 * ランダムな文字列を生成
 */
function generate_random_string($length = 10) {
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
}

/**
 * 申込番号を生成
 */
function generate_order_number() {
    return 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 年齢区分ラベルを取得
 */
function get_age_group_label($age) {
    return AGE_GROUPS[$age] ?? '';
}

/**
 * デバッグ用：変数をダンプ
 */
function dd($var) {
    if (ENV !== 'production') {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        exit;
    }
}

/**
 * ログに記録
 */
function write_log($message, $level = 'info') {
    $logFile = ROOT_PATH . '/logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // ログディレクトリが存在しない場合は作成
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * リクエストがAjaxかどうか判定
 */
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * ページネーション用のURL生成
 */
function pagination_url($page, $params = []) {
    $params['page'] = $page;
    $queryString = http_build_query($params);
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    return $currentUrl . '?' . $queryString;
}

/**
 * 管理者権限チェック
 */
function is_admin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * ログイン必須チェック
 */
function require_admin() {
    if (!is_admin()) {
        header('Location: ' . url('admin/login'));
        exit;
    }
}

/**
 * 申込期間チェック
 */
function is_order_period() {
    $now = date('Y-m-d');
    return ORDER_ENABLED && 
           $now >= ORDER_START_DATE && 
           $now <= ORDER_END_DATE;
}

/**
 * 入力値のトリムとサニタイズ
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * 全角カタカナチェック
 */
function is_katakana($str) {
    return preg_match('/^[ァ-ヶー　\s]+$/u', $str);
}

/**
 * ひらがなをカタカナに変換
 */
function hiragana_to_katakana($str) {
    return mb_convert_kana($str, 'C', 'UTF-8');
}

/**
 * 半角英数字チェック
 */
function is_alphanumeric($str) {
    return preg_match('/^[a-zA-Z0-9]+$/', $str);
}

/**
 * JSONレスポンス送信
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * CSVダウンロード用ヘッダー送信
 */
function csv_download_headers($filename) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
}
?>