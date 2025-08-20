<?php
/**
 * GitHub Webhook 受信スクリプト
 * 
 * GitHubからのpushイベントを受信して自動更新を実行
 */

// ログ設定
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/webhook.log');

// Webhook設定
define('WEBHOOK_SECRET', 'your_webhook_secret_here'); // GitHubで設定したSecret
define('REPO_PATH', __DIR__);
define('BRANCH', 'main'); // 対象ブランチ

/**
 * ログ記録関数
 */
function writeLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    // ログディレクトリ作成
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents(__DIR__ . '/logs/webhook.log', $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * セキュリティ検証
 */
function verifySignature($payload, $signature) {
    if (empty(WEBHOOK_SECRET)) {
        return false;
    }
    
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Git更新実行
 */
function executeGitPull() {
    $commands = [
        'cd ' . REPO_PATH,
        'git fetch origin ' . BRANCH,
        'git reset --hard origin/' . BRANCH,
        'chmod 755 storage storage/product_images logs 2>/dev/null || true',
        'chmod 600 .env 2>/dev/null || true'
    ];
    
    $fullCommand = implode(' && ', $commands) . ' 2>&1';
    
    writeLog("Executing: {$fullCommand}");
    
    $output = shell_exec($fullCommand);
    
    writeLog("Git pull output: " . trim($output));
    
    return $output;
}

// メイン処理開始
try {
    writeLog("Webhook received from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // HTTPメソッド確認
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        writeLog("Error: Invalid method " . $_SERVER['REQUEST_METHOD']);
        exit('Method not allowed');
    }
    
    // ペイロード取得
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    writeLog("Payload length: " . strlen($payload));
    writeLog("Signature: " . $signature);
    
    // 署名検証（Secretが設定されている場合）
    if (!empty(WEBHOOK_SECRET) && !verifySignature($payload, $signature)) {
        http_response_code(401);
        writeLog("Error: Invalid signature");
        exit('Unauthorized');
    }
    
    // JSON解析
    $data = json_decode($payload, true);
    
    if (!$data) {
        http_response_code(400);
        writeLog("Error: Invalid JSON payload");
        exit('Bad Request');
    }
    
    // プッシュイベント確認
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
    writeLog("GitHub event: " . $event);
    
    if ($event !== 'push') {
        writeLog("Info: Ignoring non-push event: " . $event);
        exit('Event ignored');
    }
    
    // ブランチ確認
    $ref = $data['ref'] ?? '';
    $expectedRef = 'refs/heads/' . BRANCH;
    
    writeLog("Push ref: " . $ref);
    writeLog("Expected ref: " . $expectedRef);
    
    if ($ref !== $expectedRef) {
        writeLog("Info: Ignoring push to different branch");
        exit('Branch ignored');
    }
    
    // リポジトリ確認
    $repoName = $data['repository']['name'] ?? '';
    writeLog("Repository: " . $repoName);
    
    if ($repoName !== 'iseyohindata') {
        writeLog("Error: Invalid repository: " . $repoName);
        exit('Invalid repository');
    }
    
    writeLog("Starting git pull for valid push event");
    
    // Git更新実行
    $output = executeGitPull();
    
    // 成功レスポンス
    http_response_code(200);
    
    $response = [
        'status' => 'success',
        'message' => 'Deployment completed',
        'timestamp' => date('Y-m-d H:i:s'),
        'output' => trim($output)
    ];
    
    writeLog("Deployment completed successfully");
    
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    writeLog("Exception: " . $e->getMessage());
    
    http_response_code(500);
    
    $response = [
        'status' => 'error',
        'message' => 'Deployment failed',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>