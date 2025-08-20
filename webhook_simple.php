<?php
/**
 * 簡易Webhook（SSH不要版）
 * 
 * GitHubからのプッシュを受信して手動デプロイページを呼び出し
 */

// 設定
$WEBHOOK_SECRET = 'your_webhook_secret_2025';
$DEPLOY_SECRET = 'NishidaSJ'; // deploy_manual.phpと同じパスワード

/**
 * ログ記録
 */
function writeLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents(__DIR__ . '/logs/webhook.log', $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * 署名検証
 */
function verifySignature($payload, $signature) {
    global $WEBHOOK_SECRET;
    
    if (empty($WEBHOOK_SECRET)) {
        return false;
    }
    
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $WEBHOOK_SECRET);
    return hash_equals($expectedSignature, $signature);
}

try {
    writeLog("Webhook received from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // POSTメソッドチェック
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        writeLog("Error: Invalid method");
        exit('Method not allowed');
    }
    
    // ペイロード取得
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    // 署名検証
    if (!empty($WEBHOOK_SECRET) && !verifySignature($payload, $signature)) {
        http_response_code(401);
        writeLog("Error: Invalid signature");
        exit('Unauthorized');
    }
    
    // JSON解析
    $data = json_decode($payload, true);
    if (!$data) {
        http_response_code(400);
        writeLog("Error: Invalid JSON");
        exit('Bad Request');
    }
    
    // プッシュイベント確認
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
    if ($event !== 'push') {
        writeLog("Info: Ignoring non-push event: " . $event);
        exit('Event ignored');
    }
    
    // ブランチ確認
    $ref = $data['ref'] ?? '';
    if ($ref !== 'refs/heads/main') {
        writeLog("Info: Ignoring push to branch: " . $ref);
        exit('Branch ignored');
    }
    
    writeLog("Valid push event received, triggering deployment");
    
    // 手動デプロイページを呼び出し
    $deployUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/deploy_manual.php';
    
    $deployContext = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Webhook-Deploy/1.0'
            ],
            'timeout' => 10
        ]
    ]);
    
    $deployParams = http_build_query([
        'auto' => '1',
        'secret' => $DEPLOY_SECRET
    ]);
    
    $fullDeployUrl = $deployUrl . '?' . $deployParams;
    
    // バックグラウンドでデプロイ実行
    $deployResult = file_get_contents($fullDeployUrl, false, $deployContext);
    
    if ($deployResult !== false) {
        writeLog("Deploy triggered successfully");
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Deployment triggered',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        writeLog("Deploy trigger failed");
        
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Deploy trigger failed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    writeLog("Exception: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Webhook processing failed',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>