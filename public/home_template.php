<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="双康幼稚園の用品申込サイトです。制服、バッグ、帽子などの園用品をオンラインで注文できます。">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #f8f9fa;
            --accent-color: #ffc107;
        }
        
        body {
            font-family: 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a4480 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a4480 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 25px;
            padding: 0.75rem 2rem;
        }
    </style>
</head>
<body>
    <!-- ナビゲーション -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= url() ?>">
                <i class="fas fa-graduation-cap me-2"></i>
                双康幼稚園用品申込
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?= url() ?>">
                    <i class="fas fa-home me-1"></i>ホーム
                </a>
                <?php if ($order_enabled): ?>
                <a class="nav-link" href="<?= url('order') ?>">
                    <i class="fas fa-shopping-cart me-1"></i>ご用品申込
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- フラッシュメッセージ -->
    <?php
    $flash = flash();
    foreach (['success', 'error', 'warning', 'info'] as $type):
        if (isset($flash[$type])):
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
            <?= e($flash[$type]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php
        endif;
    endforeach;
    ?>

    <!-- ヒーローセクション -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">
                <i class="fas fa-graduation-cap me-3"></i>
                双康幼稚園用品申込サイト
            </h1>
            <p class="lead mb-4">
                園児の用品を簡単・便利にオンラインでお申し込みいただけます
            </p>
            <?php if ($order_enabled): ?>
            <a href="<?= url('order') ?>" class="btn btn-warning btn-lg">
                <i class="fas fa-shopping-cart me-2"></i>申込を始める
            </a>
            <?php endif; ?>
        </div>
    </section>

    <div class="container">
        <!-- 申込状況 -->
        <div class="row mb-4">
            <?php if ($order_enabled): ?>
            <div class="col-lg-8">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-3x text-success mb-3"></i>
                        <h4 class="card-title text-success">申込受付中</h4>
                        <p class="card-text">
                            現在、用品の申込を受け付けております。<br>
                            申込期間: <?= format_date(ORDER_START_DATE) ?> 〜 <?= format_date(ORDER_END_DATE) ?>
                        </p>
                        <a href="<?= url('order') ?>" class="btn btn-success btn-lg">
                            <i class="fas fa-shopping-cart me-2"></i>申込を始める
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>申込状況</h5>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h3 text-primary"><?= number_format($stats['total_orders']) ?></div>
                                <small class="text-muted">総申込数</small>
                            </div>
                            <div class="col-6">
                                <div class="h3 text-success"><?= number_format($stats['today_orders']) ?></div>
                                <small class="text-muted">本日の申込</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                        <h4 class="card-title text-warning">申込期間外です</h4>
                        <p class="card-text">
                            申込期間: <?= format_date(ORDER_START_DATE) ?> 〜 <?= format_date(ORDER_END_DATE) ?>
                        </p>
                        <p class="text-muted">申込開始まで今しばらくお待ちください。</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 年齢別ご案内 -->
        <section class="mb-5">
            <h2 class="text-center mb-4">
                <i class="fas fa-users me-2"></i>年齢別ご案内
            </h2>
            <div class="row">
                <?php foreach ($age_groups as $age => $label): ?>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="display-6 text-primary mb-3">
                                <?php
                                $icons = ['2' => 'baby', '3' => 'child', '4' => 'smile', '5' => 'graduation-cap'];
                                ?>
                                <i class="fas fa-<?= $icons[$age] ?? 'child' ?>"></i>
                            </div>
                            <h5 class="card-title"><?= e($label) ?></h5>
                            <p class="card-text text-muted">
                                対象の用品一覧をご確認いただけます
                            </p>
                            <?php if ($order_enabled): ?>
                            <a href="<?= url('order?age=' . $age) ?>" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>用品を見る
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- システム情報 -->
        <section class="mb-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle me-2"></i>システム情報
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>環境:</strong> <?= ENV ?><br>
                            <strong>PHPバージョン:</strong> <?= phpversion() ?>
                        </div>
                        <div class="col-md-4">
                            <strong>データベース:</strong> <?= DB_NAME ?><br>
                            <strong>申込機能:</strong> <?= ORDER_ENABLED ? '有効' : '無効' ?>
                        </div>
                        <div class="col-md-4">
                            <strong>商品数:</strong> <?= $stats['active_products'] ?><br>
                            <strong>バージョン:</strong> 1.0.0
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- CSRF Token -->
    <script>
        window.csrfToken = '<?= $csrf_token ?>';
    </script>
</body>
</html>