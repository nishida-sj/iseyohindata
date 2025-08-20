<?php
namespace App\Models;

use App\Core\Model;
use Exception;

/**
 * 商品モデル
 * 
 * 商品マスタの管理を行う
 */
class Product extends Model {
    protected $table = 'products';
    protected $fillable = [
        'product_code', 'product_name', 'specification', 
        'price', 'remarks', 'image_filename', 'is_active', 'sort_order'
    ];
    
    /**
     * 有効な商品のみを取得
     */
    public function getActive($orderBy = 'sort_order ASC') {
        return $this->where(['is_active' => 1], $orderBy);
    }
    
    /**
     * 商品コードで商品を取得
     */
    public function findByCode($productCode) {
        return $this->whereOne(['product_code' => $productCode]);
    }
    
    /**
     * 年齢グループに表示される商品を取得
     */
    public function getByAgeGroup($ageGroup) {
        $sql = "
            SELECT p.*, agp.sort_order as age_group_sort_order
            FROM {$this->table} p
            INNER JOIN age_group_products agp ON p.id = agp.product_id
            WHERE p.is_active = 1 
                AND agp.age_group = :age_group 
                AND agp.is_active = 1
            ORDER BY agp.sort_order ASC, p.sort_order ASC
        ";
        
        return $this->query($sql, ['age_group' => $ageGroup]);
    }
    
    /**
     * 商品を作成（画像アップロード込み）
     */
    public function createWithImage($data, $imageFile = null) {
        try {
            $this->db->beginTransaction();
            
            // 画像ファイルの処理
            if ($imageFile && isset($imageFile['tmp_name']) && $imageFile['error'] === UPLOAD_ERR_OK) {
                $data['image_filename'] = $this->uploadImage($imageFile);
            }
            
            // 商品コードの重複チェック
            if ($this->exists(['product_code' => $data['product_code']])) {
                throw new Exception('商品コードが既に存在します。');
            }
            
            // ソート順序の自動設定
            if (!isset($data['sort_order']) || $data['sort_order'] === '') {
                $maxSort = $this->db->selectOne("SELECT MAX(sort_order) as max_sort FROM {$this->table}");
                $data['sort_order'] = ($maxSort['max_sort'] ?? 0) + 1;
            }
            
            $productId = $this->create($data);
            
            $this->db->commit();
            return $productId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            // アップロードされた画像があれば削除
            if (isset($data['image_filename'])) {
                $this->deleteImageFile($data['image_filename']);
            }
            
            throw $e;
        }
    }
    
    /**
     * 商品を更新（画像アップロード込み）
     */
    public function updateWithImage($id, $data, $imageFile = null) {
        try {
            $this->db->beginTransaction();
            
            $currentProduct = $this->find($id);
            if (!$currentProduct) {
                throw new Exception('商品が見つかりません。');
            }
            
            // 商品コードの重複チェック（自分以外）
            $existingProduct = $this->whereOne(['product_code' => $data['product_code']]);
            if ($existingProduct && $existingProduct['id'] != $id) {
                throw new Exception('商品コードが既に存在します。');
            }
            
            $oldImageFilename = $currentProduct['image_filename'];
            
            // 新しい画像ファイルの処理
            if ($imageFile && isset($imageFile['tmp_name']) && $imageFile['error'] === UPLOAD_ERR_OK) {
                $data['image_filename'] = $this->uploadImage($imageFile);
            }
            
            $this->update($id, $data);
            
            // 新しい画像がアップロードされた場合、古い画像を削除
            if (isset($data['image_filename']) && !empty($oldImageFilename)) {
                $this->deleteImageFile($oldImageFilename);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            // アップロードされた新しい画像があれば削除
            if (isset($data['image_filename'])) {
                $this->deleteImageFile($data['image_filename']);
            }
            
            throw $e;
        }
    }
    
    /**
     * 商品を削除（画像ファイルも削除）
     */
    public function deleteWithImage($id) {
        try {
            $this->db->beginTransaction();
            
            $product = $this->find($id);
            if (!$product) {
                throw new Exception('商品が見つかりません。');
            }
            
            // 注文に含まれているかチェック
            $orderItemCount = $this->db->selectOne(
                "SELECT COUNT(*) as count FROM order_items WHERE product_id = :product_id",
                ['product_id' => $id]
            );
            
            if ($orderItemCount['count'] > 0) {
                // 注文に含まれている場合は論理削除
                $this->update($id, ['is_active' => 0]);
            } else {
                // 注文に含まれていない場合は物理削除
                $this->delete($id);
                
                // 画像ファイルを削除
                if (!empty($product['image_filename'])) {
                    $this->deleteImageFile($product['image_filename']);
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
     * 画像ファイルをアップロード
     */
    private function uploadImage($imageFile) {
        // ファイルサイズチェック
        if ($imageFile['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception('ファイルサイズが大きすぎます（最大' . format_file_size(MAX_UPLOAD_SIZE) . '）。');
        }
        
        // 拡張子チェック
        $extension = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_IMAGE_EXTENSIONS)) {
            throw new Exception('許可されていないファイル形式です（' . implode(', ', ALLOWED_IMAGE_EXTENSIONS) . 'のみ）。');
        }
        
        // MIMEタイプチェック
        $allowedMimeTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif'
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imageFile['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception('不正なファイル形式です。');
        }
        
        // ファイル名生成（衝突回避）
        $fileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $uploadPath = UPLOAD_PATH . $fileName;
        
        // アップロードディレクトリの作成
        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }
        
        // ファイル移動
        if (!move_uploaded_file($imageFile['tmp_name'], $uploadPath)) {
            throw new Exception('ファイルのアップロードに失敗しました。');
        }
        
        // 画像リサイズ（必要に応じて）
        $this->resizeImage($uploadPath, $extension);
        
        return $fileName;
    }
    
    /**
     * 画像ファイルを削除
     */
    private function deleteImageFile($filename) {
        if (empty($filename)) return;
        
        $filePath = UPLOAD_PATH . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    /**
     * 画像リサイズ
     */
    private function resizeImage($filePath, $extension) {
        $maxWidth = 800;
        $maxHeight = 600;
        
        // 画像情報を取得
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) return;
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        
        // リサイズが不要な場合は終了
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            return;
        }
        
        // アスペクト比を保持してリサイズサイズを計算
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = round($originalWidth * $ratio);
        $newHeight = round($originalHeight * $ratio);
        
        // 元画像を読み込み
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $sourceImage = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $sourceImage = imagecreatefrompng($filePath);
                break;
            case 'gif':
                $sourceImage = imagecreatefromgif($filePath);
                break;
            default:
                return;
        }
        
        if (!$sourceImage) return;
        
        // 新しい画像を作成
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // PNG・GIFの透明度を保持
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefill($resizedImage, 0, 0, $transparent);
        }
        
        // リサイズ実行
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // ファイルに保存
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($resizedImage, $filePath, 90);
                break;
            case 'png':
                imagepng($resizedImage, $filePath, 9);
                break;
            case 'gif':
                imagegif($resizedImage, $filePath);
                break;
        }
        
        // メモリ解放
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
    }
    
    /**
     * 商品売上データを取得
     */
    public function getSalesData($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                p.*,
                COALESCE(SUM(oi.quantity), 0) as total_quantity,
                COALESCE(SUM(oi.subtotal), 0) as total_sales,
                COUNT(DISTINCT oi.order_id) as order_count
            FROM {$this->table} p
            LEFT JOIN order_items oi ON p.id = oi.product_id
        ";
        
        $params = [];
        $conditions = [];
        
        if ($startDate && $endDate) {
            $sql .= " LEFT JOIN orders o ON oi.order_id = o.id";
            $conditions[] = "(o.order_date >= :start_date AND o.order_date <= :end_date)";
            $params['start_date'] = $startDate . ' 00:00:00';
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " GROUP BY p.id ORDER BY total_quantity DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * 表示順序を更新
     */
    public function updateSortOrder($id, $sortOrder) {
        return $this->update($id, ['sort_order' => $sortOrder]);
    }
    
    /**
     * 商品検索
     */
    public function search($keyword, $isActive = null) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (!empty($keyword)) {
            $sql .= " AND (product_code LIKE :keyword OR product_name LIKE :keyword OR specification LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }
        
        if ($isActive !== null) {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = $isActive ? 1 : 0;
        }
        
        $sql .= " ORDER BY sort_order ASC";
        
        return $this->query($sql, $params);
    }
}
?>