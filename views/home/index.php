<?php
$show_hero = true;
ob_start();
?>

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
                    申込期間: <?= format_date($order_start_date) ?> 〜 <?= format_date($order_end_date) ?>
                </p>
                <a href="<?= url('/order') ?>" class="btn btn-success btn-lg">
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
                    申込期間: <?= format_date($order_start_date) ?> 〜 <?= format_date($order_end_date) ?>
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
                    <a href="<?= url('/order') ?>?age=<?= $age ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>用品を見る
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- お知らせ・ご案内 -->
<section class="mb-5">
    <h2 class="text-center mb-4">
        <i class="fas fa-info-circle me-2"></i>お知らせ・ご案内
    </h2>
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-book me-2 text-primary"></i>ご利用案内
                    </h5>
                    <p class="card-text">
                        申込方法や注意事項について詳しくご案内いたします。
                        初めてご利用の方は必ずお読みください。
                    </p>
                    <a href="<?= url('/guide') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right me-1"></i>詳しく見る
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-envelope me-2 text-success"></i>お問い合わせ
                    </h5>
                    <p class="card-text">
                        ご不明な点やお困りのことがございましたら、
                        お気軽にお問い合わせください。
                    </p>
                    <a href="<?= url('/contact') ?>" class="btn btn-success">
                        <i class="fas fa-arrow-right me-1"></i>お問い合わせ
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 特徴・メリット -->
<section class="mb-5">
    <h2 class="text-center mb-4">
        <i class="fas fa-star me-2"></i>このサイトの特徴
    </h2>
    <div class="row">
        <div class="col-md-4 mb-3 text-center">
            <div class="mb-3">
                <i class="fas fa-clock fa-3x text-primary"></i>
            </div>
            <h5>24時間受付</h5>
            <p class="text-muted">
                申込期間中はいつでもお申し込み可能です。
                お忙しい保護者の方も安心してご利用いただけます。
            </p>
        </div>
        <div class="col-md-4 mb-3 text-center">
            <div class="mb-3">
                <i class="fas fa-shield-alt fa-3x text-success"></i>
            </div>
            <h5>安全・安心</h5>
            <p class="text-muted">
                SSL暗号化通信により、個人情報を安全に保護します。
                セキュリティ対策も万全です。
            </p>
        </div>
        <div class="col-md-4 mb-3 text-center">
            <div class="mb-3">
                <i class="fas fa-mobile-alt fa-3x text-warning"></i>
            </div>
            <h5>スマホ対応</h5>
            <p class="text-muted">
                スマートフォンからも快適にご利用いただけます。
                外出先からでも簡単にお申し込み可能です。
            </p>
        </div>
    </div>
</section>

<!-- 重要なお知らせ -->
<section class="mb-5">
    <div class="alert alert-info">
        <h5 class="alert-heading">
            <i class="fas fa-exclamation-circle me-2"></i>重要なお知らせ
        </h5>
        <ul class="mb-0">
            <li>申込は期間内に完了してください。期間外の申込は受け付けできません。</li>
            <li>お申し込み後の変更・キャンセルはできませんのでご注意ください。</li>
            <li>ご不明な点がございましたら、お早めにお問い合わせください。</li>
            <li>システムメンテナンス等により一時的にご利用いただけない場合があります。</li>
        </ul>
    </div>
</section>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>