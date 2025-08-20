<?php
namespace App\Models;

use App\Core\Model;
use Exception;

/**
 * 年齢区分別商品設定モデル
 * 
 * 年齢区分ごとの商品表示設定を管理する
 */
class AgeGroupProduct extends Model {
    protected $table = 'age_group_products';
    protected $fillable = [
        'age_group', 'product_id', 'is_active', 'sort_order'
    ];
    
    /**
     * 特定年齢区分の商品設定を取得
     */
    public function getByAgeGroup($ageGroup) {
        $sql = "
            SELECT 
                agp.*,
                p.product_code,
                p.product_name,
                p.specification,
                p.price,
                p.remarks,
                p.image_filename,
                p.is_active as product_is_active
            FROM {$this->table} agp
            INNER JOIN products p ON agp.product_id = p.id
            WHERE agp.age_group = :age_group
            ORDER BY agp.sort_order ASC, p.sort_order ASC
        ";
        
        return $this->query($sql, ['age_group' => $ageGroup]);
    }
    
    /**
     * 特定年齢区分の有効な商品のみを取得
     */
    public function getActiveByAgeGroup($ageGroup) {
        $sql = "
            SELECT 
                agp.*,
                p.product_code,
                p.product_name,
                p.specification,
                p.price,
                p.remarks,
                p.image_filename
            FROM {$this->table} agp
            INNER JOIN products p ON agp.product_id = p.id
            WHERE agp.age_group = :age_group
                AND agp.is_active = 1
                AND p.is_active = 1
            ORDER BY agp.sort_order ASC, p.sort_order ASC
        ";
        
        return $this->query($sql, ['age_group' => $ageGroup]);
    }
    
    /**
     * 年齢区分の商品設定を一括更新
     */
    public function updateAgeGroupProducts($ageGroup, $productSettings) {
        try {
            $this->db->beginTransaction();
            
            // 既存の設定を削除
            $this->db->delete(
                "DELETE FROM {$this->table} WHERE age_group = :age_group",
                ['age_group' => $ageGroup]
            );
            
            // 新しい設定を追加
            foreach ($productSettings as $setting) {
                $data = [
                    'age_group' => $ageGroup,
                    'product_id' => $setting['product_id'],
                    'is_active' => $setting['is_active'] ?? 1,
                    'sort_order' => $setting['sort_order'] ?? 0
                ];
                
                $this->create($data);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 商品を年齢区分に追加
     */
    public function addProductToAgeGroup($ageGroup, $productId, $isActive = true, $sortOrder = 0) {
        // 重複チェック
        if ($this->exists(['age_group' => $ageGroup, 'product_id' => $productId])) {
            throw new Exception('この商品は既に設定されています。');
        }
        
        // ソート順序の自動設定
        if ($sortOrder === 0) {
            $maxSort = $this->db->selectOne(
                "SELECT MAX(sort_order) as max_sort FROM {$this->table} WHERE age_group = :age_group",
                ['age_group' => $ageGroup]
            );
            $sortOrder = ($maxSort['max_sort'] ?? 0) + 1;
        }
        
        return $this->create([
            'age_group' => $ageGroup,
            'product_id' => $productId,
            'is_active' => $isActive ? 1 : 0,
            'sort_order' => $sortOrder
        ]);
    }
    
    /**
     * 年齢区分から商品を削除
     */
    public function removeProductFromAgeGroup($ageGroup, $productId) {
        return $this->db->delete(
            "DELETE FROM {$this->table} WHERE age_group = :age_group AND product_id = :product_id",
            ['age_group' => $ageGroup, 'product_id' => $productId]
        );
    }
    
    /**
     * 商品の表示状態を切り替え
     */
    public function toggleProductActive($ageGroup, $productId) {
        $current = $this->whereOne(['age_group' => $ageGroup, 'product_id' => $productId]);
        if (!$current) {
            throw new Exception('設定が見つかりません。');
        }
        
        $newStatus = $current['is_active'] ? 0 : 1;
        
        return $this->db->update(
            "UPDATE {$this->table} SET is_active = :is_active WHERE age_group = :age_group AND product_id = :product_id",
            [
                'is_active' => $newStatus,
                'age_group' => $ageGroup,
                'product_id' => $productId
            ]
        );
    }
    
    /**
     * 表示順序を更新
     */
    public function updateSortOrder($ageGroup, $productId, $sortOrder) {
        return $this->db->update(
            "UPDATE {$this->table} SET sort_order = :sort_order WHERE age_group = :age_group AND product_id = :product_id",
            [
                'sort_order' => $sortOrder,
                'age_group' => $ageGroup,
                'product_id' => $productId
            ]
        );
    }
    
    /**
     * 年齢区分の表示順序を一括更新
     */
    public function updateSortOrders($ageGroup, $sortData) {
        try {
            $this->db->beginTransaction();
            
            foreach ($sortData as $item) {
                $this->updateSortOrder($ageGroup, $item['product_id'], $item['sort_order']);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 商品が設定されていない年齢区分を取得
     */
    public function getAgeGroupsWithoutProduct($productId) {
        $sql = "
            SELECT age_groups.age_group
            FROM (
                SELECT '2' as age_group UNION ALL
                SELECT '3' as age_group UNION ALL
                SELECT '4' as age_group UNION ALL
                SELECT '5' as age_group
            ) age_groups
            LEFT JOIN {$this->table} agp ON age_groups.age_group = agp.age_group AND agp.product_id = :product_id
            WHERE agp.id IS NULL
        ";
        
        return $this->query($sql, ['product_id' => $productId]);
    }
    
    /**
     * 商品が設定されている年齢区分を取得
     */
    public function getAgeGroupsWithProduct($productId) {
        return $this->where(['product_id' => $productId], 'age_group ASC');
    }
    
    /**
     * 年齢区分別の商品数統計を取得
     */
    public function getAgeGroupStats() {
        $sql = "
            SELECT 
                agp.age_group,
                CASE agp.age_group
                    WHEN '2' THEN '2歳児(ひよこ)'
                    WHEN '3' THEN '3歳児(年少)'
                    WHEN '4' THEN '4歳児(年中)'
                    WHEN '5' THEN '5歳児(年長)'
                    ELSE CONCAT(agp.age_group, '歳児')
                END as age_group_label,
                COUNT(*) as total_products,
                SUM(CASE WHEN agp.is_active = 1 AND p.is_active = 1 THEN 1 ELSE 0 END) as active_products,
                SUM(CASE WHEN agp.is_active = 0 OR p.is_active = 0 THEN 1 ELSE 0 END) as inactive_products
            FROM {$this->table} agp
            INNER JOIN products p ON agp.product_id = p.id
            GROUP BY agp.age_group
            ORDER BY agp.age_group ASC
        ";
        
        return $this->query($sql);
    }
    
    /**
     * 商品を全年齢区分に一括追加
     */
    public function addProductToAllAgeGroups($productId, $isActive = true) {
        try {
            $this->db->beginTransaction();
            
            $ageGroups = ['2', '3', '4', '5'];
            
            foreach ($ageGroups as $ageGroup) {
                // 既に存在する場合はスキップ
                if (!$this->exists(['age_group' => $ageGroup, 'product_id' => $productId])) {
                    $this->addProductToAgeGroup($ageGroup, $productId, $isActive);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 商品を全年齢区分から削除
     */
    public function removeProductFromAllAgeGroups($productId) {
        return $this->db->delete(
            "DELETE FROM {$this->table} WHERE product_id = :product_id",
            ['product_id' => $productId]
        );
    }
    
    /**
     * 年齢区分設定のコピー
     */
    public function copyAgeGroupSettings($fromAgeGroup, $toAgeGroup) {
        try {
            $this->db->beginTransaction();
            
            // コピー先の既存設定を削除
            $this->db->delete(
                "DELETE FROM {$this->table} WHERE age_group = :age_group",
                ['age_group' => $toAgeGroup]
            );
            
            // コピー元の設定を取得して複製
            $sourceSettings = $this->where(['age_group' => $fromAgeGroup]);
            
            foreach ($sourceSettings as $setting) {
                $this->create([
                    'age_group' => $toAgeGroup,
                    'product_id' => $setting['product_id'],
                    'is_active' => $setting['is_active'],
                    'sort_order' => $setting['sort_order']
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * 商品が注文で使用されているかチェック
     */
    public function isProductUsedInOrders($productId) {
        $result = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM order_items WHERE product_id = :product_id",
            ['product_id' => $productId]
        );
        
        return $result['count'] > 0;
    }
}
?>