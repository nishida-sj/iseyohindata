-- 双康幼稚園用品申込サイト データベース設計
-- Author: システム開発者
-- Date: 2025-08-18
-- Version: 1.0

-- 文字コードとエンジン設定
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==================================================
-- 商品マスタテーブル
-- ==================================================
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `product_code` varchar(50) NOT NULL COMMENT '商品コード',
  `product_name` varchar(255) NOT NULL COMMENT '商品名',
  `specification` text COMMENT '規格',
  `price` int(11) NOT NULL DEFAULT 0 COMMENT '価格（税込）',
  `remarks` text COMMENT '備考',
  `image_filename` varchar(255) DEFAULT NULL COMMENT '商品画像ファイル名',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ（1:有効、0:無効）',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '表示順序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_code` (`product_code`),
  KEY `idx_products_active` (`is_active`),
  KEY `idx_products_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品マスタ';

-- ==================================================
-- 年齢区分別商品設定テーブル（中間テーブル）
-- ==================================================
CREATE TABLE IF NOT EXISTS `age_group_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `age_group` varchar(10) NOT NULL COMMENT '年齢区分（2,3,4,5）',
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '表示フラグ（1:表示、0:非表示）',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '表示順序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_age_group_products` (`age_group`, `product_id`),
  KEY `fk_age_group_products_product_id` (`product_id`),
  KEY `idx_age_group_products_age` (`age_group`),
  KEY `idx_age_group_products_sort` (`sort_order`),
  CONSTRAINT `fk_age_group_products_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='年齢区分別商品設定';

-- ==================================================
-- 注文テーブル
-- ==================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '注文ID',
  `order_number` varchar(50) NOT NULL COMMENT '注文番号',
  `parent_name` varchar(100) NOT NULL COMMENT '保護者名',
  `child_name` varchar(100) NOT NULL COMMENT '入園児氏名',
  `child_name_kana` varchar(100) NOT NULL COMMENT '入園児氏名（フリガナ）',
  `age_group` varchar(10) NOT NULL COMMENT '入園年齢区分（2,3,4,5）',
  `total_amount` int(11) NOT NULL DEFAULT 0 COMMENT '合計金額',
  `total_quantity` int(11) NOT NULL DEFAULT 0 COMMENT '合計数量',
  `status` varchar(20) NOT NULL DEFAULT 'completed' COMMENT '注文ステータス',
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注文日時',
  `notes` text COMMENT '備考',
  `ip_address` varchar(45) DEFAULT NULL COMMENT '注文者IPアドレス',
  `user_agent` text COMMENT 'ユーザーエージェント',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orders_number` (`order_number`),
  KEY `idx_orders_date` (`order_date`),
  KEY `idx_orders_age_group` (`age_group`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='注文';

-- ==================================================
-- 注文明細テーブル
-- ==================================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '注文明細ID',
  `order_id` int(11) NOT NULL COMMENT '注文ID',
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `product_code` varchar(50) NOT NULL COMMENT '商品コード（スナップショット）',
  `product_name` varchar(255) NOT NULL COMMENT '商品名（スナップショット）',
  `specification` text COMMENT '規格（スナップショット）',
  `unit_price` int(11) NOT NULL DEFAULT 0 COMMENT '単価（スナップショット）',
  `quantity` int(11) NOT NULL DEFAULT 0 COMMENT '数量',
  `subtotal` int(11) NOT NULL DEFAULT 0 COMMENT '小計',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order_id` (`order_id`),
  KEY `fk_order_items_product_id` (`product_id`),
  KEY `idx_order_items_order_product` (`order_id`, `product_id`),
  CONSTRAINT `fk_order_items_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='注文明細';

-- ==================================================
-- 管理者テーブル
-- ==================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '管理者ID',
  `username` varchar(50) NOT NULL COMMENT 'ユーザー名',
  `password` varchar(255) NOT NULL COMMENT 'パスワード（ハッシュ化）',
  `email` varchar(255) DEFAULT NULL COMMENT 'メールアドレス',
  `display_name` varchar(100) NOT NULL COMMENT '表示名',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `last_login_at` datetime DEFAULT NULL COMMENT '最終ログイン日時',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_username` (`username`),
  KEY `idx_admins_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理者';

-- ==================================================
-- システムログテーブル
-- ==================================================
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ログID',
  `level` varchar(20) NOT NULL DEFAULT 'info' COMMENT 'ログレベル',
  `message` text NOT NULL COMMENT 'メッセージ',
  `context` text COMMENT 'コンテキスト（JSON）',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPアドレス',
  `user_agent` text COMMENT 'ユーザーエージェント',
  `admin_id` int(11) DEFAULT NULL COMMENT '管理者ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
  PRIMARY KEY (`id`),
  KEY `idx_system_logs_level` (`level`),
  KEY `idx_system_logs_created_at` (`created_at`),
  KEY `fk_system_logs_admin_id` (`admin_id`),
  CONSTRAINT `fk_system_logs_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='システムログ';

-- ==================================================
-- 設定テーブル
-- ==================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '設定ID',
  `setting_key` varchar(100) NOT NULL COMMENT '設定キー',
  `setting_value` text COMMENT '設定値',
  `description` varchar(255) DEFAULT NULL COMMENT '説明',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='設定';

-- ==================================================
-- 初期データ投入
-- ==================================================

-- デフォルト管理者作成（パスワード: admin123）
INSERT INTO `admins` (`username`, `password`, `display_name`, `email`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理者', 'admin@example.com')
ON DUPLICATE KEY UPDATE 
  `password` = VALUES(`password`),
  `display_name` = VALUES(`display_name`);

-- 基本設定
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES 
('site_name', '双康幼稚園用品申込サイト', 'サイト名'),
('order_enabled', '1', '注文受付有効フラグ'),
('order_start_date', '2025-01-01', '注文受付開始日'),
('order_end_date', '2025-03-31', '注文受付終了日'),
('admin_email', 'admin@example.com', '管理者メールアドレス'),
('mail_from_name', '双康幼稚園用品申込システム', 'メール送信者名')
ON DUPLICATE KEY UPDATE 
  `setting_value` = VALUES(`setting_value`),
  `description` = VALUES(`description`);

-- サンプル商品データ
INSERT INTO `products` (`product_code`, `product_name`, `specification`, `price`, `remarks`, `is_active`, `sort_order`) VALUES 
('UNIFORM001', '制服上着', 'サイズ：100cm〜130cm', 4500, '季節：秋冬用', 1, 1),
('UNIFORM002', '制服ズボン', 'サイズ：100cm〜130cm', 3200, '季節：秋冬用', 1, 2),
('UNIFORM003', '制服スカート', 'サイズ：100cm〜130cm', 3200, '季節：秋冬用', 1, 3),
('UNIFORM004', '夏用半袖シャツ', 'サイズ：100cm〜130cm', 2100, '季節：春夏用', 1, 4),
('UNIFORM005', '冬用長袖シャツ', 'サイズ：100cm〜130cm', 2800, '季節：秋冬用', 1, 5),
('BAG001', '通園バッグ', '横30cm×縦25cm×マチ10cm', 2800, 'ネームタグ付き', 1, 6),
('HAT001', '通園帽', 'サイズ：S〜L', 1500, 'あご紐付き', 1, 7),
('SHOES001', '上履き', 'サイズ：15cm〜20cm', 1800, '滑り止め付き', 1, 8)
ON DUPLICATE KEY UPDATE 
  `product_name` = VALUES(`product_name`),
  `specification` = VALUES(`specification`),
  `price` = VALUES(`price`),
  `remarks` = VALUES(`remarks`);

-- 年齢別商品設定（全年齢で全商品を表示）
INSERT INTO `age_group_products` (`age_group`, `product_id`, `is_active`, `sort_order`) 
SELECT age.age_group, p.id, 1, p.sort_order
FROM (
  SELECT '2' as age_group UNION ALL
  SELECT '3' as age_group UNION ALL
  SELECT '4' as age_group UNION ALL
  SELECT '5' as age_group
) age
CROSS JOIN `products` p
WHERE p.is_active = 1
ON DUPLICATE KEY UPDATE 
  `is_active` = VALUES(`is_active`),
  `sort_order` = VALUES(`sort_order`);

SET FOREIGN_KEY_CHECKS = 1;

-- ==================================================
-- インデックス最適化のためのクエリ（必要に応じて実行）
-- ==================================================

-- 注文データの集計用ビュー
CREATE OR REPLACE VIEW `v_order_summary` AS
SELECT 
  o.id,
  o.order_number,
  o.parent_name,
  o.child_name,
  o.child_name_kana,
  o.age_group,
  CASE o.age_group
    WHEN '2' THEN '2歳児(ひよこ)'
    WHEN '3' THEN '3歳児(年少)'
    WHEN '4' THEN '4歳児(年中)'
    WHEN '5' THEN '5歳児(年長)'
    ELSE CONCAT(o.age_group, '歳児')
  END as age_group_label,
  o.total_amount,
  o.total_quantity,
  o.order_date,
  COUNT(oi.id) as item_count
FROM `orders` o
LEFT JOIN `order_items` oi ON o.id = oi.order_id
GROUP BY o.id;

-- 商品別売上集計用ビュー
CREATE OR REPLACE VIEW `v_product_sales` AS
SELECT 
  p.id as product_id,
  p.product_code,
  p.product_name,
  p.price as current_price,
  COALESCE(SUM(oi.quantity), 0) as total_quantity,
  COALESCE(SUM(oi.subtotal), 0) as total_sales,
  COUNT(DISTINCT oi.order_id) as order_count
FROM `products` p
LEFT JOIN `order_items` oi ON p.id = oi.product_id
GROUP BY p.id;

COMMIT;