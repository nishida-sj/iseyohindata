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

// デフォルト期間（当月）
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// 基本統計取得
try {
    $sql = "
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(SUM(total_quantity), 0) as total_quantity,
            COALESCE(AVG(total_amount), 0) as avg_order_amount
        FROM orders 
        WHERE DATE(order_date) BETWEEN ? AND ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $basicStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $basicStats = ['total_orders' => 0, 'total_sales' => 0, 'total_quantity' => 0, 'avg_order_amount' => 0];
}

// 年齢別統計取得
try {
    $sql = "
        SELECT 
            age_group,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(SUM(total_quantity), 0) as total_quantity
        FROM orders 
        WHERE DATE(order_date) BETWEEN ? AND ?
        GROUP BY age_group
        ORDER BY age_group
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $ageStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 年齢ラベルを追加
    foreach ($ageStats as &$row) {
        $row['age_label'] = AGE_GROUPS[$row['age_group']] ?? $row['age_group'] . '歳児';
    }
    
} catch (Exception $e) {
    $ageStats = [];
}

// 利き手統計取得
try {
    $sql = "
        SELECT 
            handedness,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(SUM(total_quantity), 0) as total_quantity
        FROM orders 
        WHERE DATE(order_date) BETWEEN ? AND ?
        AND handedness IS NOT NULL
        GROUP BY handedness
        ORDER BY handedness
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $handednessStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $handednessStats = [];
}

// 商品別統計取得
try {
    $sql = "
        SELECT
            oi.product_id,
            oi.product_code,
            oi.product_name,
            COUNT(DISTINCT oi.order_id) as order_count,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.quantity * COALESCE(p.price, oi.unit_price)) as total_sales
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        GROUP BY oi.product_id, oi.product_code, oi.product_name
        ORDER BY total_sales DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $productStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $productStats = [];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>集計レポート - 双康幼稚園用品申込システム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">双康幼稚園用品申込システム - 集計レポート</span>
            <div class="navbar-nav">
                <a class="nav-link text-white" href="admin_dashboard.php">ダッシュボードに戻る</a>
                <a class="nav-link text-white" href="admin_logout.php">ログアウト</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-chart-bar"></i> 集計レポート</h1>
        </div>

        <!-- 期間選択 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-calendar"></i> 集計期間選択</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="admin_reports.php">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">開始日</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="<?= htmlspecialchars($startDate) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">終了日</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="<?= htmlspecialchars($endDate) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 集計実行
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="row mt-3">
                    <div class="col">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('today')">今日</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('thisWeek')">今週</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('thisMonth')">今月</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange('lastMonth')">先月</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 詳細レポート出力 -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-file-export"></i> 詳細レポート出力</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h6 class="card-title">
                                    <i class="fas fa-calculator text-info"></i> お子様別数量集計
                                </h6>
                                <p class="card-text small">園児名×商品名の数量クロス集計表</p>
                                <a href="admin_reports_export.php?type=quantity&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>"
                                   class="btn btn-info btn-sm">
                                    <i class="fas fa-download"></i> 数量集計CSV出力
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h6 class="card-title">
                                    <i class="fas fa-yen-sign text-warning"></i> お子様別金額集計
                                </h6>
                                <p class="card-text small">園児名×商品名の金額クロス集計表</p>
                                <a href="admin_reports_export.php?type=amount&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>"
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-download"></i> 金額集計CSV出力
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h6 class="card-title">
                                    <i class="fas fa-print text-primary"></i> 保育用品注文袋印刷
                                </h6>
                                <p class="card-text small">長形３封筒サイズで集金袋を印刷</p>
                                <a href="admin_print_orders.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>"
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-print"></i> 注文袋印刷
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i>
                        <strong>ご注意:</strong>
                        <ul class="mb-0">
                            <li>CSVファイルはExcelで開くことができます</li>
                            <li>印刷機能では長形３封筒サイズ（120mm × 235mm）で出力されます</li>
                            <li>期間を変更した場合は、上記の「集計実行」ボタンを押してから各機能を利用してください</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- 基本統計 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center h-100 border-primary">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart fa-2x text-primary mb-3"></i>
                        <h4 class="card-title"><?= number_format($basicStats['total_orders']) ?></h4>
                        <p class="card-text text-muted">総注文件数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100 border-success">
                    <div class="card-body">
                        <i class="fas fa-yen-sign fa-2x text-success mb-3"></i>
                        <h4 class="card-title">¥<?= number_format($basicStats['total_sales']) ?></h4>
                        <p class="card-text text-muted">総売上金額</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100 border-info">
                    <div class="card-body">
                        <i class="fas fa-boxes fa-2x text-info mb-3"></i>
                        <h4 class="card-title"><?= number_format($basicStats['total_quantity']) ?></h4>
                        <p class="card-text text-muted">総販売数量</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100 border-warning">
                    <div class="card-body">
                        <i class="fas fa-calculator fa-2x text-warning mb-3"></i>
                        <h4 class="card-title">¥<?= number_format($basicStats['avg_order_amount']) ?></h4>
                        <p class="card-text text-muted">平均注文金額</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 年齢別集計 -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-child"></i> 年齢別集計</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ageStats)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <p>指定期間にデータがありません</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>年齢区分</th>
                                        <th class="text-end">注文数</th>
                                        <th class="text-end">売上金額</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ageStats as $stat): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($stat['age_label']) ?></strong></td>
                                        <td class="text-end"><?= number_format($stat['order_count']) ?>件</td>
                                        <td class="text-end">¥<?= number_format($stat['total_sales']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 利き手別集計 -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-white">
                        <h5><i class="fas fa-hand-paper"></i> 利き手別集計</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($handednessStats)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-hand-paper fa-3x mb-3"></i>
                            <p>指定期間にデータがありません</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>利き手</th>
                                        <th class="text-end">注文数</th>
                                        <th class="text-end">売上金額</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($handednessStats as $stat): ?>
                                    <tr>
                                        <td>
                                            <?php if ($stat['handedness'] === '左手'): ?>
                                                <i class="fas fa-hand-paper fa-flip-horizontal me-2 text-warning"></i>
                                            <?php else: ?>
                                                <i class="fas fa-hand-paper me-2 text-primary"></i>
                                            <?php endif; ?>
                                            <strong><?= htmlspecialchars($stat['handedness']) ?></strong>
                                        </td>
                                        <td class="text-end"><?= number_format($stat['order_count']) ?>件</td>
                                        <td class="text-end">¥<?= number_format($stat['total_sales']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 商品別集計 -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-box"></i> 商品別売上TOP10</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($productStats)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-box fa-3x mb-3"></i>
                            <p>指定期間にデータがありません</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>商品名</th>
                                        <th class="text-end">販売数</th>
                                        <th class="text-end">売上金額</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productStats as $index => $stat): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary me-2"><?= $index + 1 ?></span>
                                            <strong><?= htmlspecialchars($stat['product_name']) ?></strong>
                                        </td>
                                        <td class="text-end"><?= number_format($stat['total_quantity']) ?>点</td>
                                        <td class="text-end">¥<?= number_format($stat['total_sales']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> ダッシュボードに戻る
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 期間設定ボタン
        function setDateRange(range) {
            const today = new Date();
            let startDate, endDate;
            
            switch(range) {
                case 'today':
                    startDate = endDate = today.toISOString().split('T')[0];
                    break;
                case 'thisWeek':
                    const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
                    const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));
                    startDate = startOfWeek.toISOString().split('T')[0];
                    endDate = endOfWeek.toISOString().split('T')[0];
                    break;
                case 'thisMonth':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
                    break;
                case 'lastMonth':
                    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    startDate = lastMonth.toISOString().split('T')[0];
                    endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
                    break;
            }
            
            document.getElementById('start_date').value = startDate;
            document.getElementById('end_date').value = endDate;
        }
    </script>
</body>
</html>