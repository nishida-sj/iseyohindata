<?php
/**
 * 双康幼稚園用品申込サイト - Webルートエントリーポイント
 * public/ディレクトリがWebサーバーのドキュメントルートになる想定
 */

// ルートディレクトリの指定
define('ROOT_PATH', dirname(__DIR__));

// メインアプリケーションを読み込み
require_once ROOT_PATH . '/index.php';
?>