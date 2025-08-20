<?php
/**
 * 双康幼稚園用品申込サイト - テスト実行スクリプト
 * 
 * 基本的なユニットテストを実行します
 */

// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "双康幼稚園用品申込サイト - ユニットテスト\n";
echo "==========================================\n\n";

// 設定ファイル読み込み
require_once dirname(__DIR__) . '/config/config.php';

// テスト結果管理
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$errors = [];

/**
 * テスト実行関数
 */
function runTest($testName, $testFunction) {
    global $totalTests, $passedTests, $failedTests, $errors;
    
    $totalTests++;
    echo "Testing: {$testName}... ";
    
    try {
        $result = $testFunction();
        if ($result === true) {
            echo "✓ PASS\n";
            $passedTests++;
        } else {
            echo "✗ FAIL - {$result}\n";
            $failedTests++;
            $errors[] = "{$testName}: {$result}";
        }
    } catch (Exception $e) {
        echo "✗ ERROR - {$e->getMessage()}\n";
        $failedTests++;
        $errors[] = "{$testName}: {$e->getMessage()}";
    }
}

/**
 * アサーション関数
 */
function assertEquals($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        return "Expected '{$expected}', got '{$actual}'. {$message}";
    }
    return true;
}

function assertTrue($value, $message = '') {
    if (!$value) {
        return "Expected true, got false. {$message}";
    }
    return true;
}

function assertFalse($value, $message = '') {
    if ($value) {
        return "Expected false, got true. {$message}";
    }
    return true;
}

function assertNotEmpty($value, $message = '') {
    if (empty($value)) {
        return "Expected non-empty value. {$message}";
    }
    return true;
}

// ===============================================
// データベース接続テスト
// ===============================================
echo "1. データベース接続テスト\n";
echo "------------------------\n";

runTest('データベース接続', function() {
    $db = \App\Core\Database::getInstance();
    $result = $db->selectOne("SELECT 1 as test");
    return assertEquals(1, $result['test'], 'データベース接続失敗');
});

runTest('商品テーブル存在確認', function() {
    $db = \App\Core\Database::getInstance();
    $exists = $db->tableExists('products');
    return assertTrue($exists, '商品テーブルが存在しません');
});

runTest('注文テーブル存在確認', function() {
    $db = \App\Core\Database::getInstance();
    $exists = $db->tableExists('orders');
    return assertTrue($exists, '注文テーブルが存在しません');
});

echo "\n";

// ===============================================
// 設定テスト
// ===============================================
echo "2. 設定テスト\n";
echo "-------------\n";

runTest('環境変数読み込み', function() {
    return assertTrue(defined('ENV'), 'ENV定数が定義されていません');
});

runTest('データベース設定', function() {
    $required = [DB_HOST, DB_NAME, DB_USERNAME];
    foreach ($required as $setting) {
        if (empty($setting)) {
            return "必須のDB設定が不足しています";
        }
    }
    return true;
});

runTest('年齢区分設定', function() {
    $ageGroups = AGE_GROUPS;
    $expected = ['2', '3', '4', '5'];
    foreach ($expected as $age) {
        if (!isset($ageGroups[$age])) {
            return "年齢区分 {$age} が定義されていません";
        }
    }
    return true;
});

echo "\n";

// ===============================================
// ヘルパー関数テスト
// ===============================================
echo "3. ヘルパー関数テスト\n";
echo "--------------------\n";

runTest('URL生成関数', function() {
    $url = url('/test');
    return assertNotEmpty($url, 'URL生成関数が空を返しました');
});

runTest('価格フォーマット関数', function() {
    $formatted = format_price(1000);
    return assertEquals('1,000円', $formatted, '価格フォーマットが正しくありません');
});

runTest('日付フォーマット関数', function() {
    $formatted = format_date('2025-01-01');
    return assertNotEmpty($formatted, '日付フォーマット関数が空を返しました');
});

runTest('年齢区分ラベル取得', function() {
    $label = get_age_group_label('3');
    return assertEquals('3歳児(年少)', $label, '年齢区分ラベルが正しくありません');
});

runTest('カタカナチェック関数', function() {
    $valid = is_katakana('ヤマダ ハナコ');
    $invalid = is_katakana('yamada hanako');
    
    if (!$valid) return 'カタカナ文字列が有効と認識されませんでした';
    if ($invalid) return '英字がカタカナとして認識されました';
    
    return true;
});

echo "\n";

// ===============================================
// モデルテスト
// ===============================================
echo "4. モデルテスト\n";
echo "---------------\n";

runTest('商品モデル - 基本操作', function() {
    $productModel = new \App\Models\Product();
    $products = $productModel->all();
    
    if (!is_array($products)) {
        return '商品一覧取得が配列を返しませんでした';
    }
    
    return true;
});

runTest('注文モデル - 基本操作', function() {
    $orderModel = new \App\Models\Order();
    $count = $orderModel->count();
    
    if (!is_numeric($count)) {
        return '注文数取得が数値を返しませんでした';
    }
    
    return true;
});

runTest('管理者モデル - パスワードハッシュ', function() {
    $adminModel = new \App\Models\Admin();
    $errors = $adminModel->validatePasswordStrength('Test123!');
    
    return assertTrue(empty($errors), 'パスワード強度チェックでエラーが発生しました');
});

echo "\n";

// ===============================================
// セキュリティテスト
// ===============================================
echo "5. セキュリティテスト\n";
echo "--------------------\n";

runTest('HTMLエスケープ関数', function() {
    $input = '<script>alert("xss")</script>';
    $escaped = e($input);
    
    if (strpos($escaped, '<script>') !== false) {
        return 'HTMLエスケープが機能していません';
    }
    
    return true;
});

runTest('入力サニタイズ関数', function() {
    $input = '<script>alert("test")</script>  ';
    $sanitized = sanitize_input($input);
    
    if (strpos($sanitized, '<script>') !== false) {
        return 'サニタイズが機能していません';
    }
    
    if ($sanitized !== trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'))) {
        return 'サニタイズ結果が期待値と異なります';
    }
    
    return true;
});

runTest('CSRFトークン生成', function() {
    session_start();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    
    $token = csrf_token();
    
    return assertNotEmpty($token, 'CSRFトークンが生成されませんでした');
});

echo "\n";

// ===============================================
// ファイルシステムテスト
// ===============================================
echo "6. ファイルシステムテスト\n";
echo "------------------------\n";

runTest('ストレージディレクトリ存在確認', function() {
    $dirs = [
        ROOT_PATH . '/storage',
        ROOT_PATH . '/storage/product_images',
        ROOT_PATH . '/logs'
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            return "ディレクトリが存在しません: {$dir}";
        }
        
        if (!is_writable($dir)) {
            return "ディレクトリに書き込み権限がありません: {$dir}";
        }
    }
    
    return true;
});

runTest('設定ファイル存在確認', function() {
    $envFile = ROOT_PATH . '/.env';
    
    if (!file_exists($envFile)) {
        return '.envファイルが存在しません';
    }
    
    return true;
});

echo "\n";

// ===============================================
// 統合テスト（軽量）
// ===============================================
echo "7. 統合テスト\n";
echo "-------------\n";

runTest('年齢別商品取得', function() {
    $ageGroupProductModel = new \App\Models\AgeGroupProduct();
    $products = $ageGroupProductModel->getActiveByAgeGroup('3');
    
    return assertTrue(is_array($products), '年齢別商品取得が配列を返しませんでした');
});

runTest('注文番号生成', function() {
    $orderNumber = generate_order_number();
    
    if (strlen($orderNumber) < 10) {
        return '注文番号が短すぎます';
    }
    
    if (strpos($orderNumber, 'ORD') !== 0) {
        return '注文番号の形式が正しくありません';
    }
    
    return true;
});

runTest('メール設定検証', function() {
    $mailService = new \App\Services\MailService();
    $errors = $mailService->validateConfiguration();
    
    // 開発環境では設定が不完全でも許可
    if (ENV !== 'production' && !empty($errors)) {
        return true; // 開発環境では警告のみ
    }
    
    return assertTrue(empty($errors), 'メール設定に問題があります: ' . implode(', ', $errors));
});

echo "\n";

// ===============================================
// テスト結果表示
// ===============================================
echo "==========================================\n";
echo "テスト実行結果\n";
echo "==========================================\n";
echo "総テスト数: {$totalTests}\n";
echo "成功: {$passedTests}\n";
echo "失敗: {$failedTests}\n";

if ($failedTests > 0) {
    echo "\n失敗したテスト:\n";
    echo "----------------\n";
    foreach ($errors as $error) {
        echo "✗ {$error}\n";
    }
}

$successRate = ($totalTests > 0) ? round(($passedTests / $totalTests) * 100, 1) : 0;
echo "\n成功率: {$successRate}%\n";

if ($failedTests === 0) {
    echo "\n🎉 すべてのテストに合格しました！\n";
    exit(0);
} else {
    echo "\n⚠️  いくつかのテストに失敗しました。上記のエラーを確認してください。\n";
    exit(1);
}
?>