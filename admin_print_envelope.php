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

// POSTデータ確認
if (!isset($_POST['selected_orders']) || empty($_POST['selected_orders'])) {
    header('Location: admin_print_orders.php');
    exit;
}

$selectedOrders = $_POST['selected_orders'];

// 納品予定日が設定された場合は印刷処理へ
if (isset($_POST['delivery_date'])) {
    $deliveryDate = $_POST['delivery_date'];

    // 選択された注文の詳細データを取得
    $placeholders = str_repeat('?,', count($selectedOrders) - 1) . '?';

    $sql = "
        SELECT
            o.id,
            o.order_number,
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
            o.parent_name,
            o.total_amount,
            o.total_quantity
        FROM orders o
        WHERE o.id IN ($placeholders)
        ORDER BY o.age_group, o.child_name, o.order_date
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($selectedOrders);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 各注文の商品詳細を取得（最新単価を参照）
    for ($i = 0; $i < count($orders); $i++) {
        $sql = "
            SELECT
                oi.product_name,
                oi.quantity,
                COALESCE(p.price, oi.unit_price) as unit_price,
                oi.quantity * COALESCE(p.price, oi.unit_price) as subtotal
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.product_name
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orders[$i]['id']]);
        $orders[$i]['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 合計金額を再計算（最新単価ベース）
        $recalculatedTotal = 0;
        $recalculatedQuantity = 0;
        foreach ($orders[$i]['items'] as $item) {
            $recalculatedTotal += $item['subtotal'];
            $recalculatedQuantity += $item['quantity'];
        }
        $orders[$i]['total_amount'] = $recalculatedTotal;
        $orders[$i]['total_quantity'] = $recalculatedQuantity;

        // デバッグ情報を追加
        error_log("Order " . ($i + 1) . " (ID: {$orders[$i]['id']}): " . $orders[$i]['child_name'] . " - " . count($orders[$i]['items']) . " items, Total: ¥" . number_format($recalculatedTotal));
    }

    // 印刷履歴の記録
    try {
        foreach ($orders as $order) {
            // 既存の印刷履歴をチェック
            $checkSql = "SELECT id, print_count FROM print_history WHERE order_id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$order['id']]);
            $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                // 既存レコードがある場合は印刷回数を増加
                $updateSql = "
                    UPDATE print_history
                    SET print_count = print_count + 1,
                        print_date = NOW(),
                        delivery_date = ?,
                        printed_by = ?
                    WHERE id = ?
                ";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    $deliveryDate,
                    $_SESSION['admin_username'] ?? 'admin',
                    $existingRecord['id']
                ]);
            } else {
                // 新規レコードを作成
                $insertSql = "
                    INSERT INTO print_history (
                        order_id, order_number, print_count, printed_by,
                        delivery_date, print_date
                    ) VALUES (?, ?, 1, ?, ?, NOW())
                ";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    $order['id'],
                    $order['order_number'],
                    $_SESSION['admin_username'] ?? 'admin',
                    $deliveryDate
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Print history recording error: " . $e->getMessage());
    }

    // 印刷処理
    include 'print_envelope_template.php';
    exit;
}

// 注文情報取得（確認用）
$placeholders = str_repeat('?,', count($selectedOrders) - 1) . '?';

$sql = "
    SELECT
        o.id,
        o.order_number,
        CASE o.age_group
            WHEN '2' THEN '2歳児(ひよこ)'
            WHEN '3' THEN '3歳児(年少)'
            WHEN '4' THEN '4歳児(年中)'
            WHEN '5' THEN '5歳児(年長)'
            ELSE CONCAT(o.age_group, '歳児')
        END as class_name,
        o.child_name,
        o.child_name_kana,
        o.total_amount
    FROM orders o
    WHERE o.id IN ($placeholders)
    ORDER BY o.age_group, o.child_name
";

$stmt = $pdo->prepare($sql);
$stmt->execute($selectedOrders);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品予定日入力 - 双康幼稚園用品申込システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">双康幼稚園用品申込システム - 納品予定日入力</span>
            <div class="navbar-nav">
                <a class="nav-link text-white" href="admin_print_orders.php">注文選択に戻る</a>
                <a class="nav-link text-white" href="admin_logout.php">ログアウト</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4><i class="fas fa-calendar-plus"></i> 納品予定日入力</h4>
                    </div>
                    <div class="card-body">

                        <!-- 選択された注文の確認 -->
                        <div class="mb-4">
                            <h6>印刷対象注文 (<?= count($orders) ?>件)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>注文番号</th>
                                            <th>クラス</th>
                                            <th>園児名</th>
                                            <th class="text-end">金額</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($order['order_number']) ?></span></td>
                                            <td><span class="badge bg-info"><?= htmlspecialchars($order['class_name']) ?></span></td>
                                            <td><?= htmlspecialchars($order['child_name']) ?></td>
                                            <td class="text-end">¥<?= number_format($order['total_amount']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 納品予定日入力フォーム -->
                        <form method="POST" action="admin_print_envelope.php">
                            <!-- 選択された注文IDを保持 -->
                            <?php foreach ($selectedOrders as $orderId): ?>
                            <input type="hidden" name="selected_orders[]" value="<?= htmlspecialchars($orderId) ?>">
                            <?php endforeach; ?>

                            <div class="mb-4">
                                <label for="delivery_date" class="form-label">
                                    <i class="fas fa-truck"></i> 納品予定日 <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-lg" id="delivery_date"
                                       name="delivery_date" required
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                                <div class="form-text">
                                    保育用品集金袋に印刷される納品予定日を設定してください
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>印刷設定について:</strong>
                                <ul class="mb-0">
                                    <li>長形３封筒サイズ（120mm × 235mm）で印刷されます</li>
                                    <li>「双康幼稚園 保育用品集金袋」フォーマットで出力されます</li>
                                    <li>つり銭のいらないようにお願い致します の注意書きが入ります</li>
                                </ul>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-secondary" onclick="goBack()">
                                    <i class="fas fa-arrow-left"></i> 戻る
                                </button>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-print"></i> 集金袋を印刷 (<?= count($orders) ?>件)
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function goBack() {
            // フォームデータを保持して戻る
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'admin_print_orders.php';

            // 選択された注文IDをフォームに追加
            const selectedOrders = document.querySelectorAll('input[name="selected_orders[]"]');
            selectedOrders.forEach(input => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'return_selected[]';
                hiddenInput.value = input.value;
                form.appendChild(hiddenInput);
            });

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>