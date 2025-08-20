<?php
/**
 * サブドメイン環境テストページ
 * iseyohin.geo.jp での動作確認用
 */

echo "<h1>🎯 サブドメイン環境テスト</h1>";

echo "<h2>サーバー情報</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>項目</th><th>値</th></tr>";
echo "<tr><td>HTTP_HOST</td><td>" . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>REQUEST_URI</td><td>" . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>SCRIPT_NAME</td><td>" . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>DOCUMENT_ROOT</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>__DIR__</td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td>dirname(__DIR__)</td><td>" . dirname(__DIR__) . "</td></tr>";
echo "</table>";

echo "<h2>ファイル存在確認</h2>";
$files = [
    '../index.php' => 'ルートindex.php',
    '../config/config.php' => '設定ファイル',
    '../.env' => '環境変数ファイル',
    '../storage/' => 'ストレージディレクトリ',
    'index.php' => 'public/index.php'
];

echo "<ul>";
foreach ($files as $file => $desc) {
    $exists = file_exists($file);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✓存在' : '✗なし';
    echo "<li style='color: {$color}'><strong>{$desc}</strong>: {$status} ({$file})</li>";
}
echo "</ul>";

echo "<h2>PHP設定</h2>";
echo "<p><strong>PHPバージョン:</strong> " . phpversion() . "</p>";
echo "<p><strong>現在のディレクトリ:</strong> " . getcwd() . "</p>";

echo "<h2>リンクテスト</h2>";
echo "<ul>";
echo "<li><a href='../setup.php'>セットアップページ</a></li>";
echo "<li><a href='index.php'>メインアプリケーション</a></li>";
echo "<li><a href='../'>ルートディレクトリ</a></li>";
echo "</ul>";

echo "<p><small>テスト完了後、このファイル（test.php）は削除してください。</small></p>";
?>