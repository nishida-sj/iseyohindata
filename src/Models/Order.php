<?php
namespace App\Models;

use App\Core\Model;
use Exception;

/**
 * 注文モデル
 * 
 * 注文データの管理を行う
 */
class Order extends Model {
    protected $table = 'orders';
    protected $fillable = [
        'order_number', 'parent_name', 'child_name', 'child_name_kana',
        'age_group', 'total_amount', 'total_quantity', 'status', 
        'order_date', 'notes', 'ip_address', 'user_agent'
    ];
    
    /**
     * 注文明細と一緒に注文を作成
     */
    public function createWithItems($orderData, $orderItems) {
        try {
            $this->db->beginTransaction();
            
            // 注文番号の生成
            $orderData['order_number'] = $this->generateOrderNumber();
            
            // 合計金額・数量の計算
            $totalAmount = 0;
            $totalQuantity = 0;
            
            foreach ($orderItems as $item) {
                $totalAmount += $item['subtotal'];
                $totalQuantity += $item['quantity'];
            }
            
            $orderData['total_amount'] = $totalAmount;
            $orderData['total_quantity'] = $totalQuantity;
            $orderData['order_date'] = date('Y-m-d H:i:s');
            $orderData['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $orderData['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $orderData['status'] = 'completed';
            
            // 注文作成
            $orderId = $this->create($orderData);
            
            // 注文明細作成
            $orderItemModel = new OrderItem();
            foreach ($orderItems as $item) {
                $item['order_id'] = $orderId;
                $orderItemModel->create($item);
            }
            
            $this->db->commit();
            
            return [
                'order_id' => $orderId,
                'order_number' => $orderData['order_number']
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 注文番号を生成
     */
    private function generateOrderNumber() {
        do {
            $orderNumber = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->exists(['order_number' => $orderNumber]);
        } while ($exists);
        
        return $orderNumber;
    }
    
    /**
     * 注文を注文明細付きで取得
     */
    public function findWithItems($id) {
        $order = $this->find($id);
        if (!$order) {
            return null;
        }
        
        $orderItemModel = new OrderItem();
        $order['items'] = $orderItemModel->getByOrderId($id);
        
        return $order;
    }
    
    /**
     * 注文番号で検索
     */
    public function findByOrderNumber($orderNumber) {
        return $this->whereOne(['order_number' => $orderNumber]);
    }
    
    /**
     * 期間指定で注文を取得
     */
    public function getByPeriod($startDate, $endDate) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE order_date >= :start_date 
                AND order_date <= :end_date 
            ORDER BY order_date DESC
        ";
        
        $params = [
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59'
        ];
        
        return $this->query($sql, $params);
    }
    
    /**
     * 年齢区分別注文統計を取得
     */
    public function getAgeGroupStats($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                age_group,
                COUNT(*) as order_count,
                SUM(total_quantity) as total_quantity,
                SUM(total_amount) as total_amount
            FROM {$this->table} 
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " AND order_date >= :start_date AND order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " GROUP BY age_group ORDER BY age_group ASC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 日別注文統計を取得
     */
    public function getDailyStats($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                DATE(order_date) as order_date,
                COUNT(*) as order_count,
                SUM(total_quantity) as total_quantity,
                SUM(total_amount) as total_amount
            FROM {$this->table} 
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " AND order_date >= :start_date AND order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " GROUP BY DATE(order_date) ORDER BY order_date ASC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 注文詳細データを取得（CSV出力用）
     */
    public function getDetailedOrders($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                o.order_number,
                o.parent_name,
                o.child_name,
                o.child_name_kana,
                CASE o.age_group
                    WHEN '2' THEN '2歳児(ひよこ)'
                    WHEN '3' THEN '3歳児(年少)'
                    WHEN '4' THEN '4歳児(年中)'
                    WHEN '5' THEN '5歳児(年長)'
                    ELSE CONCAT(o.age_group, '歳児')
                END as age_group_label,
                o.total_quantity,
                o.total_amount,
                o.order_date,
                oi.product_code,
                oi.product_name,
                oi.specification,
                oi.unit_price,
                oi.quantity,
                oi.subtotal
            FROM {$this->table} o
            LEFT JOIN order_items oi ON o.id = oi.order_id
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
     * 注文検索
     */
    public function search($keyword, $ageGroup = null, $startDate = null, $endDate = null) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (!empty($keyword)) {
            $sql .= " AND (order_number LIKE :keyword OR parent_name LIKE :keyword OR child_name LIKE :keyword OR child_name_kana LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }
        
        if (!empty($ageGroup)) {
            $sql .= " AND age_group = :age_group";
            $params['age_group'] = $ageGroup;
        }
        
        if ($startDate) {
            $sql .= " AND order_date >= :start_date";
            $params['start_date'] = $startDate . ' 00:00:00';
        }
        
        if ($endDate) {
            $sql .= " AND order_date <= :end_date";
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " ORDER BY order_date DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 月別売上データを取得
     */
    public function getMonthlySales($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $sql = "
            SELECT 
                MONTH(order_date) as month,
                COUNT(*) as order_count,
                SUM(total_quantity) as total_quantity,
                SUM(total_amount) as total_amount
            FROM {$this->table} 
            WHERE YEAR(order_date) = :year
            GROUP BY MONTH(order_date)
            ORDER BY month ASC
        ";
        
        return $this->query($sql, ['year' => $year]);
    }
    
    /**
     * 申込者リストを取得（重複除去）
     */
    public function getCustomerList($startDate = null, $endDate = null) {
        $sql = "
            SELECT DISTINCT
                parent_name,
                child_name,
                child_name_kana,
                age_group,
                COUNT(*) as order_count,
                SUM(total_amount) as total_amount,
                MAX(order_date) as last_order_date
            FROM {$this->table} 
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " AND order_date >= :start_date AND order_date <= :end_date";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        $sql .= " GROUP BY parent_name, child_name, child_name_kana, age_group ORDER BY last_order_date DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 最近の注文を取得
     */
    public function getRecentOrders($limit = 10) {
        return $this->all("order_date DESC LIMIT {$limit}");
    }
    
    /**
     * 今日の注文統計
     */
    public function getTodayStats() {
        $today = date('Y-m-d');
        
        $sql = "
            SELECT 
                COUNT(*) as order_count,
                COALESCE(SUM(total_quantity), 0) as total_quantity,
                COALESCE(SUM(total_amount), 0) as total_amount
            FROM {$this->table} 
            WHERE DATE(order_date) = :today
        ";
        
        $result = $this->db->selectOne($sql, ['today' => $today]);
        return $result ?: ['order_count' => 0, 'total_quantity' => 0, 'total_amount' => 0];
    }
}
?>