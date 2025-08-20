<?php
namespace App\Models;

use App\Core\Model;

/**
 * システムログモデル
 * 
 * システムログの管理を行う
 */
class SystemLog extends Model {
    protected $table = 'system_logs';
    protected $fillable = [
        'level', 'message', 'context', 'ip_address', 'user_agent', 'admin_id'
    ];
    
    /**
     * ログレベル定数
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * ログを記録
     */
    public function log($level, $message, $context = null, $adminId = null) {
        return $this->create([
            'level' => $level,
            'message' => $message,
            'context' => $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'admin_id' => $adminId
        ]);
    }
    
    /**
     * 情報ログを記録
     */
    public function info($message, $context = null, $adminId = null) {
        return $this->log(self::LEVEL_INFO, $message, $context, $adminId);
    }
    
    /**
     * 警告ログを記録
     */
    public function warning($message, $context = null, $adminId = null) {
        return $this->log(self::LEVEL_WARNING, $message, $context, $adminId);
    }
    
    /**
     * エラーログを記録
     */
    public function error($message, $context = null, $adminId = null) {
        return $this->log(self::LEVEL_ERROR, $message, $context, $adminId);
    }
    
    /**
     * 重要ログを記録
     */
    public function critical($message, $context = null, $adminId = null) {
        return $this->log(self::LEVEL_CRITICAL, $message, $context, $adminId);
    }
    
    /**
     * デバッグログを記録
     */
    public function debug($message, $context = null, $adminId = null) {
        // デバッグログは開発環境のみ記録
        if (ENV !== 'production') {
            return $this->log(self::LEVEL_DEBUG, $message, $context, $adminId);
        }
        return null;
    }
    
    /**
     * ログレベル別にログを取得
     */
    public function getByLevel($level, $limit = 100) {
        return $this->where(['level' => $level], 'created_at DESC', $limit);
    }
    
    /**
     * 管理者別にログを取得
     */
    public function getByAdmin($adminId, $limit = 100) {
        return $this->where(['admin_id' => $adminId], 'created_at DESC', $limit);
    }
    
    /**
     * 期間指定でログを取得
     */
    public function getByPeriod($startDate, $endDate, $level = null, $adminId = null) {
        $sql = "SELECT * FROM {$this->table} WHERE created_at >= :start_date AND created_at <= :end_date";
        $params = [
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59'
        ];
        
        if ($level) {
            $sql .= " AND level = :level";
            $params['level'] = $level;
        }
        
        if ($adminId) {
            $sql .= " AND admin_id = :admin_id";
            $params['admin_id'] = $adminId;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 最近のログを取得
     */
    public function getRecent($hours = 24, $limit = 100) {
        $sql = "
            SELECT 
                sl.*,
                a.username,
                a.display_name
            FROM {$this->table} sl
            LEFT JOIN admins a ON sl.admin_id = a.id
            WHERE sl.created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
            ORDER BY sl.created_at DESC
            LIMIT :limit
        ";
        
        return $this->query($sql, ['hours' => $hours, 'limit' => $limit]);
    }
    
    /**
     * エラーログの統計を取得
     */
    public function getErrorStats($days = 7) {
        $sql = "
            SELECT 
                DATE(created_at) as log_date,
                level,
                COUNT(*) as count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                AND level IN ('error', 'critical', 'warning')
            GROUP BY DATE(created_at), level
            ORDER BY log_date DESC, level ASC
        ";
        
        return $this->query($sql, ['days' => $days]);
    }
    
    /**
     * ログレベル別の件数を取得
     */
    public function getLevelCounts($days = 30) {
        $sql = "
            SELECT 
                level,
                COUNT(*) as count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY level
            ORDER BY 
                CASE level
                    WHEN 'critical' THEN 1
                    WHEN 'error' THEN 2
                    WHEN 'warning' THEN 3
                    WHEN 'info' THEN 4
                    WHEN 'debug' THEN 5
                    ELSE 6
                END
        ";
        
        return $this->query($sql, ['days' => $days]);
    }
    
    /**
     * 管理者のアクションログを記録
     */
    public function logAdminAction($action, $targetType, $targetId, $details = null, $adminId = null) {
        $message = "管理者アクション: {$action}";
        $context = [
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details
        ];
        
        return $this->info($message, $context, $adminId);
    }
    
    /**
     * 注文ログを記録
     */
    public function logOrder($action, $orderNumber, $details = null) {
        $message = "注文{$action}: {$orderNumber}";
        $context = [
            'action' => $action,
            'order_number' => $orderNumber,
            'details' => $details
        ];
        
        return $this->info($message, $context);
    }
    
    /**
     * セキュリティログを記録
     */
    public function logSecurity($event, $details = null) {
        $message = "セキュリティイベント: {$event}";
        $context = [
            'event' => $event,
            'details' => $details,
            'session_id' => session_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null
        ];
        
        return $this->warning($message, $context);
    }
    
    /**
     * 古いログを削除
     */
    public function cleanup($days = 90) {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        return $this->db->execute($sql, ['days' => $days]);
    }
    
    /**
     * ログの詳細検索
     */
    public function search($params) {
        $sql = "
            SELECT 
                sl.*,
                a.username,
                a.display_name
            FROM {$this->table} sl
            LEFT JOIN admins a ON sl.admin_id = a.id
            WHERE 1=1
        ";
        
        $queryParams = [];
        
        if (!empty($params['keyword'])) {
            $sql .= " AND (sl.message LIKE :keyword OR sl.context LIKE :keyword)";
            $queryParams['keyword'] = '%' . $params['keyword'] . '%';
        }
        
        if (!empty($params['level'])) {
            $sql .= " AND sl.level = :level";
            $queryParams['level'] = $params['level'];
        }
        
        if (!empty($params['admin_id'])) {
            $sql .= " AND sl.admin_id = :admin_id";
            $queryParams['admin_id'] = $params['admin_id'];
        }
        
        if (!empty($params['start_date'])) {
            $sql .= " AND sl.created_at >= :start_date";
            $queryParams['start_date'] = $params['start_date'] . ' 00:00:00';
        }
        
        if (!empty($params['end_date'])) {
            $sql .= " AND sl.created_at <= :end_date";
            $queryParams['end_date'] = $params['end_date'] . ' 23:59:59';
        }
        
        if (!empty($params['ip_address'])) {
            $sql .= " AND sl.ip_address = :ip_address";
            $queryParams['ip_address'] = $params['ip_address'];
        }
        
        $sql .= " ORDER BY sl.created_at DESC";
        
        if (!empty($params['limit'])) {
            $sql .= " LIMIT " . (int)$params['limit'];
        }
        
        return $this->query($sql, $queryParams);
    }
    
    /**
     * IPアドレス別のアクセス統計
     */
    public function getIpStats($days = 7) {
        $sql = "
            SELECT 
                ip_address,
                COUNT(*) as access_count,
                MIN(created_at) as first_access,
                MAX(created_at) as last_access,
                COUNT(DISTINCT admin_id) as admin_count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                AND ip_address IS NOT NULL
            GROUP BY ip_address
            ORDER BY access_count DESC
            LIMIT 20
        ";
        
        return $this->query($sql, ['days' => $days]);
    }
    
    /**
     * システムヘルスチェック用のエラー監視
     */
    public function getHealthCheck() {
        $sql = "
            SELECT 
                level,
                COUNT(*) as count,
                MAX(created_at) as last_occurrence
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND level IN ('error', 'critical')
            GROUP BY level
        ";
        
        $errors = $this->query($sql);
        
        $health = [
            'status' => 'healthy',
            'errors' => $errors,
            'warnings' => []
        ];
        
        foreach ($errors as $error) {
            if ($error['level'] === 'critical' && $error['count'] > 0) {
                $health['status'] = 'critical';
                $health['warnings'][] = "Critical errors detected: {$error['count']} in the last hour";
            } elseif ($error['level'] === 'error' && $error['count'] > 10) {
                $health['status'] = 'warning';
                $health['warnings'][] = "High error rate: {$error['count']} errors in the last hour";
            }
        }
        
        return $health;
    }
}
?>