<?php
namespace App\Models;

use App\Core\Model;

/**
 * 注文明細モデル
 * 
 * 注文明細データの管理を行う
 */
class OrderItem extends Model {
    protected $table = 'order_items';
    protected $fillable = [
        'order_id', 'product_id', 'product_code', 'product_name', 
        'specification', 'unit_price', 'quantity', 'subtotal'
    ];
    
    /**
     * 注文IDで注文明細を取得
     */
    public function getByOrderId($orderId) {
        return $this->where(['order_id' => $orderId], 'id ASC');
    }
    
    /**
     * 商品IDで注文明細を取得
     */
    public function getByProductId($productId) {
        return $this->where(['product_id' => $productId], 'created_at DESC');
    }
    
    /**
     * 商品別売上集計を取得
     */
    public function getProductSales($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                oi.product_id,
                oi.product_code,
                oi.product_name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as order_count,
                AVG(oi.unit_price) as avg_unit_price
            FROM {$this->table} oi
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " INNER JOIN orders o ON oi.order_id = o.id";
            $sql .= " WHERE o.order_date >= :start_date AND o.order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " GROUP BY oi.product_id, oi.product_code, oi.product_name";
        $sql .= " ORDER BY total_quantity DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 年齢区分別商品売上を取得
     */
    public function getProductSalesByAge($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                o.age_group,
                CASE o.age_group
                    WHEN '2' THEN '2歳児(ひよこ)'
                    WHEN '3' THEN '3歳児(年少)'
                    WHEN '4' THEN '4歳児(年中)'
                    WHEN '5' THEN '5歳児(年長)'
                    ELSE CONCAT(o.age_group, '歳児')
                END as age_group_label,
                oi.product_code,
                oi.product_name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as order_count
            FROM {$this->table} oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " AND o.order_date >= :start_date AND o.order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " GROUP BY o.age_group, oi.product_id, oi.product_code, oi.product_name";
        $sql .= " ORDER BY o.age_group ASC, total_quantity DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 売上ランキングを取得
     */
    public function getSalesRanking($type = 'quantity', $limit = 10, $startDate = null, $endDate = null) {
        $orderColumn = ($type === 'sales') ? 'total_sales' : 'total_quantity';
        
        $sql = "
            SELECT 
                oi.product_id,
                oi.product_code,
                oi.product_name,
                oi.specification,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_sales,
                COUNT(DISTINCT oi.order_id) as order_count,
                AVG(oi.unit_price) as avg_unit_price
            FROM {$this->table} oi
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " INNER JOIN orders o ON oi.order_id = o.id";
            $sql .= " WHERE o.order_date >= :start_date AND o.order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " GROUP BY oi.product_id, oi.product_code, oi.product_name, oi.specification";
        $sql .= " ORDER BY {$orderColumn} DESC";
        $sql .= " LIMIT {$limit}";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 特定商品の売上推移を取得
     */
    public function getProductSalesTrend($productId, $startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                DATE(o.order_date) as order_date,
                SUM(oi.quantity) as daily_quantity,
                SUM(oi.subtotal) as daily_sales,
                COUNT(DISTINCT oi.order_id) as daily_orders
            FROM {$this->table} oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = :product_id
        ";
        
        $params = ['product_id' => $productId];
        
        if ($startDate && $endDate) {
            $sql .= " AND o.order_date >= :start_date AND o.order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " GROUP BY DATE(o.order_date) ORDER BY order_date ASC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 注文明細の詳細情報を取得
     */
    public function getItemDetails($itemId) {
        $sql = "
            SELECT 
                oi.*,
                o.order_number,
                o.parent_name,
                o.child_name,
                o.age_group,
                o.order_date,
                p.is_active as product_is_active,
                p.image_filename
            FROM {$this->table} oi
            INNER JOIN orders o ON oi.order_id = o.id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.id = :item_id
        ";
        
        return $this->db->selectOne($sql, ['item_id' => $itemId]);
    }
    
    /**
     * CSV出力用の注文明細データを取得
     */
    public function getForCsvExport($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                o.order_number as '注文番号',
                o.order_date as '注文日時',
                o.parent_name as '保護者名',
                o.child_name as '入園児氏名',
                o.child_name_kana as 'フリガナ',
                CASE o.age_group
                    WHEN '2' THEN '2歳児(ひよこ)'
                    WHEN '3' THEN '3歳児(年少)'
                    WHEN '4' THEN '4歳児(年中)'
                    WHEN '5' THEN '5歳児(年長)'
                    ELSE CONCAT(o.age_group, '歳児')
                END as '年齢区分',
                oi.product_code as '商品コード',
                oi.product_name as '商品名',
                oi.specification as '規格',
                oi.unit_price as '単価',
                oi.quantity as '数量',
                oi.subtotal as '小計'
            FROM {$this->table} oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " AND o.order_date >= :start_date AND o.order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " ORDER BY o.order_date DESC, o.id ASC, oi.id ASC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 商品別売上サマリーをCSV用に取得
     */
    public function getProductSalesSummaryForCsv($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                oi.product_code as '商品コード',
                oi.product_name as '商品名',
                oi.specification as '規格',
                COALESCE(AVG(oi.unit_price), 0) as '平均単価',
                SUM(oi.quantity) as '総販売数量',
                SUM(oi.subtotal) as '総売上金額',
                COUNT(DISTINCT oi.order_id) as '注文件数'
            FROM {$this->table} oi
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " INNER JOIN orders o ON oi.order_id = o.id";
            $sql .= " WHERE o.order_date >= :start_date AND o.order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " GROUP BY oi.product_code, oi.product_name, oi.specification";
        $sql .= " ORDER BY SUM(oi.quantity) DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 在庫切れ予測データを取得
     */
    public function getInventoryPrediction($days = 30) {
        $sql = "
            SELECT 
                oi.product_id,
                oi.product_code,
                oi.product_name,
                SUM(oi.quantity) as total_quantity,
                COUNT(DISTINCT DATE(o.order_date)) as active_days,
                ROUND(SUM(oi.quantity) / COUNT(DISTINCT DATE(o.order_date)), 2) as avg_daily_sales
            FROM {$this->table} oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY oi.product_id, oi.product_code, oi.product_name
            HAVING active_days > 0
            ORDER BY avg_daily_sales DESC
        ";
        
        return $this->query($sql, ['days' => $days]);
    }
}
?>