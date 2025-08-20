<?php
namespace App\Core;

use App\Core\Database;
use Exception;

/**
 * ベースModelクラス
 * 
 * 各モデルクラスの基底クラス
 * 基本的なCRUD操作を提供
 */
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 全レコードを取得
     */
    public function all($orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->select($sql);
    }
    
    /**
     * IDでレコードを取得
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->selectOne($sql, ['id' => $id]);
    }
    
    /**
     * 条件でレコードを取得（複数）
     */
    public function where($conditions, $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * 条件でレコードを取得（単一）
     */
    public function whereOne($conditions) {
        $result = $this->where($conditions, null, 1);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * レコード作成
     */
    public function create($data) {
        // fillableプロパティでフィルタリング
        $filteredData = [];
        if (!empty($this->fillable)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->fillable)) {
                    $filteredData[$key] = $value;
                }
            }
        } else {
            $filteredData = $data;
        }
        
        // タイムスタンプ追加
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $filteredData['created_at'] = $now;
            $filteredData['updated_at'] = $now;
        }
        
        $columns = array_keys($filteredData);
        $placeholders = ':' . implode(', :', $columns);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
        
        return $this->db->insert($sql, $filteredData);
    }
    
    /**
     * レコード更新
     */
    public function update($id, $data) {
        // fillableプロパティでフィルタリング
        $filteredData = [];
        if (!empty($this->fillable)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->fillable)) {
                    $filteredData[$key] = $value;
                }
            }
        } else {
            $filteredData = $data;
        }
        
        // タイムスタンプ更新
        if ($this->timestamps) {
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $setPairs = [];
        foreach ($filteredData as $column => $value) {
            $setPairs[] = "{$column} = :{$column}";
        }
        
        $filteredData['id'] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setPairs) . " WHERE {$this->primaryKey} = :id";
        
        return $this->db->update($sql, $filteredData);
    }
    
    /**
     * レコード削除
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->delete($sql, ['id' => $id]);
    }
    
    /**
     * レコード数をカウント
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $result = $this->db->selectOne($sql, $params);
        return (int)$result['count'];
    }
    
    /**
     * ページング付きデータ取得
     */
    public function paginate($page = 1, $perPage = 10, $conditions = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        
        // データ取得
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->db->select($sql, $params);
        $total = $this->count($conditions);
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ];
    }
    
    /**
     * カスタムクエリ実行
     */
    protected function query($sql, $params = []) {
        return $this->db->select($sql, $params);
    }
    
    /**
     * データの存在確認
     */
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
}
?>