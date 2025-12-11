<?php
session_start();

// ログイン確認
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login_direct.php');
    exit;
}

require_once __DIR__ . '/config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USERNAME,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die('データベース接続エラー: ' . $e->getMessage());
}

// パラメータ取得
$reportType = $_GET['type'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// レポートタイプの検証
if (!in_array($reportType, ['quantity', 'amount'])) {
    die('無効なレポートタイプです');
}

/**
 * お子様名別アイテム別データ取得（最新単価を参照）
 */
function getChildItemData($pdo, $startDate, $endDate, $isAmount = false) {
    // 数量集計の場合は quantity、金額集計の場合は quantity × 最新単価
    if ($isAmount) {
        $valueField = 'oi.quantity * COALESCE(p.price, oi.unit_price)';
    } else {
        $valueField = 'oi.quantity';
    }

    $sql = "
        SELECT
            DATE_FORMAT(o.order_date, '%Y/%m/%d %H:%i') as order_datetime,
            o.age_group,
            CASE o.age_group
                WHEN '2' THEN '2歳児(ひよこ)'
                WHEN '3' THEN '3歳児(年少)'
                WHEN '4' THEN '4歳児(年中)'
                WHEN '5' THEN '5歳児(年長)'
                ELSE CONCAT(o.age_group, '歳児')
            END as class_name,
            o.child_name,
            o.child_name_kana,
            COALESCE(o.handedness, '') as handedness,
            oi.product_name,
            SUM($valueField) as total_value
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        GROUP BY o.order_date, o.age_group, o.child_name, o.child_name_kana, o.handedness, oi.product_name
        ORDER BY o.age_group, o.child_name, oi.product_name, o.order_date
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ピボットテーブル形式に変換
 */
function createPivotData($rawData) {
    $pivot = [];
    $products = [];
    $childInfo = [];

    foreach ($rawData as $row) {
        $childKey = $row['child_name'];
        $productName = $row['product_name'];
        $value = $row['total_value'];

        // 園児情報を保存
        $childInfo[$childKey] = [
            'order_datetime' => $row['order_datetime'],
            'class_name' => $row['class_name'],
            'child_name' => $row['child_name'],
            'child_name_kana' => $row['child_name_kana'],
            'handedness' => $row['handedness']
        ];

        $pivot[$childKey][$productName] = $value;
        $products[$productName] = true;
    }

    // 商品名をソート
    $products = array_keys($products);
    sort($products);

    return [$pivot, $products, $childInfo];
}

/**
 * CSV出力
 */
function outputCSV($pivotData, $products, $childInfo, $filename, $isAmount = false) {
    // ヘッダー設定
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM追加（Excel用）
    echo "\xEF\xBB\xBF";

    // CSVファイルポインタ
    $output = fopen('php://output', 'w');

    // ヘッダー行
    $headers = ['注文日時', 'クラス', '園児名', 'フリガナ', '利き手'];
    $headers = array_merge($headers, $products);
    $headers[] = $isAmount ? '合計金額' : '合計数量';
    fputcsv($output, $headers);

    // データ行
    foreach ($pivotData as $childKey => $productData) {
        $info = $childInfo[$childKey];
        $row = [
            $info['order_datetime'],
            $info['class_name'],
            $info['child_name'],
            $info['child_name_kana'],
            $info['handedness']
        ];
        $total = 0;

        foreach ($products as $product) {
            $value = $productData[$product] ?? 0;
            if ($isAmount) {
                $row[] = number_format($value);
                $total += $value;
            } else {
                $row[] = (int)$value;
                $total += $value;
            }
        }

        // 合計列
        if ($isAmount) {
            $row[] = number_format($total);
        } else {
            $row[] = (int)$total;
        }

        fputcsv($output, $row);
    }

    // 合計行
    $totalRow = ['', '', '', '', '合計'];
    $grandTotal = 0;

    foreach ($products as $product) {
        $productTotal = 0;
        foreach ($pivotData as $productData) {
            $productTotal += $productData[$product] ?? 0;
        }

        if ($isAmount) {
            $totalRow[] = number_format($productTotal);
        } else {
            $totalRow[] = (int)$productTotal;
        }

        $grandTotal += $productTotal;
    }

    // 総合計
    if ($isAmount) {
        $totalRow[] = number_format($grandTotal);
    } else {
        $totalRow[] = (int)$grandTotal;
    }

    fputcsv($output, $totalRow);

    fclose($output);
    exit;
}

// データ取得
$isAmount = ($reportType === 'amount');
$rawData = getChildItemData($pdo, $startDate, $endDate, $isAmount);

if (empty($rawData)) {
    die('指定期間にデータがありません');
}

// ピボットテーブル作成
list($pivotData, $products, $childInfo) = createPivotData($rawData);

// ファイル名生成
$dateRange = str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate);
if ($isAmount) {
    $filename = 'syukeikin_' . $dateRange . '.csv';
} else {
    $filename = 'syukei_' . $dateRange . '.csv';
}

// CSV出力
outputCSV($pivotData, $products, $childInfo, $filename, $isAmount);
?>