<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Twin Yasushi Kindergarten Supplies Order') ?></title>
    <meta name="description" content="双康幼稚園の用品申込サイトです。制服、バッグ、帽子などの園用品をオンラインで注文できます。">
    <meta name="keywords" content="双康幼稚園,用品,申込,制服,バッグ,帽子">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #f8f9fa;
            --accent-color: #ffc107;
            --success-color: #198754;
            --danger-color: #dc3545;
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
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.3rem;
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
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: #1a4480;
            border-color: #1a4480;
            transform: translateY(-1px);
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .footer {
            background: var(--primary-color);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }
        
        .product-card {
            transition: transform 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .spinner-border {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 0;
            }
            
            .product-image {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- ナビゲーション -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= url('/') ?>">
                <i class="fas fa-graduation-cap me-2"></i>
                双康幼稚園用品申込
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/') ?>">
                            <i class="fas fa-home me-1"></i>ホーム
                        </a>
                    </li>
                    <?php if (isset($order_enabled) && $order_enabled): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/order') ?>">
                            <i class="fas fa-shopping-cart me-1"></i>ご用品申込
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/guide') ?>">
                            <i class="fas fa-info-circle me-1"></i>ご利用案内
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/contact') ?>">
                            <i class="fas fa-envelope me-1"></i>お問い合わせ
                        </a>
                    </li>
                </ul>
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
            <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?> me-2"></i>
            <?= e($flash[$type]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php
        endif;
    endforeach;
    ?>

    <!-- メインコンテンツ -->
    <main>
        <?php if (isset($show_hero) && $show_hero): ?>
        <section class="hero-section">
            <div class="container text-center">
                <h1 class="display-4 mb-4">
                    <i class="fas fa-graduation-cap me-3"></i>
                    双康幼稚園用品申込サイト
                </h1>
                <p class="lead mb-4">
                    園児の用品を簡単・便利にオンラインでお申し込みいただけます
                </p>
                <?php if (isset($order_enabled) && $order_enabled): ?>
                <a href="<?= url('/order') ?>" class="btn btn-warning btn-lg">
                    <i class="fas fa-shopping-cart me-2"></i>申込を始める
                </a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <div class="container">
            <!-- ここにページコンテンツが入る -->
            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <!-- フッター -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-graduation-cap me-2"></i>双康幼稚園用品申込サイト</h5>
                    <p class="mb-2">安全・安心な用品申込システム</p>
                    <p class="small text-light">本サイトでは、園児の皆様に必要な用品を効率的にお申し込みいただけます。</p>
                </div>
                <div class="col-md-3">
                    <h6>リンク</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?= url('/') ?>" class="text-light">ホーム</a></li>
                        <li><a href="<?= url('/guide') ?>" class="text-light">ご利用案内</a></li>
                        <li><a href="<?= url('/contact') ?>" class="text-light">お問い合わせ</a></li>
                        <li><a href="<?= url('/privacy') ?>" class="text-light">プライバシーポリシー</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>申込期間</h6>
                    <p class="small text-light">
                        <?= format_date(ORDER_START_DATE) ?><br>
                        〜 <?= format_date(ORDER_END_DATE) ?>
                    </p>
                    <?php if (is_admin()): ?>
                    <a href="<?= url('/admin') ?>" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-cog me-1"></i>管理画面
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="small mb-0">
                        &copy; <?= date('Y') ?> 双康幼稚園用品申込システム. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="small mb-0">
                        Version 1.0 | <a href="<?= url('/admin') ?>" class="text-light">Admin</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 共通JavaScript -->
    <script>
        // CSRFトークン
        window.csrfToken = '<?= csrf_token() ?>';
        
        // 基本的な機能
        document.addEventListener('DOMContentLoaded', function() {
            // 自動でアラートを閉じる
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    if (!alert.classList.contains('alert-danger')) {
                        const closeBtn = alert.querySelector('.btn-close');
                        if (closeBtn) closeBtn.click();
                    }
                });
            }, 5000);
            
            // フォーム送信時の二重送信防止
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>処理中...';
                    }
                });
            });
        });
        
        // ユーティリティ関数
        function showLoading() {
            document.querySelector('.loading')?.style.display = 'block';
        }
        
        function hideLoading() {
            document.querySelector('.loading')?.style.display = 'none';
        }
        
        function formatPrice(price) {
            return new Intl.NumberFormat('ja-JP').format(price) + '円';
        }
    </script>
    
    <!-- ページ固有のJavaScript -->
    <?php if (isset($additional_js)): ?>
        <?= $additional_js ?>
    <?php endif; ?>
</body>
</html>