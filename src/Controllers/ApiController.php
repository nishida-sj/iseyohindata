<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\AgeGroupProduct;
use Exception;

/**
 * API コントローラー
 * 
 * Ajax リクエスト用のAPI を提供
 */
class ApiController extends Controller {
    
    /**
     * 年齢別商品リストを取得
     */
    public function getProductsByAge($age) {
        try {
            // 年齢の妥当性チェック
            if (!in_array($age, ['2', '3', '4', '5'])) {
                $this->json(['error' => '無効な年齢区分です'], 400);
                return;
            }
            
            // 年齢に対応する商品を取得
            $ageGroupProductModel = new AgeGroupProduct();
            $products = $ageGroupProductModel->getActiveByAgeGroup($age);
            
            // レスポンス用にデータを整形
            $result = [];
            foreach ($products as $product) {
                $result[] = [
                    'id' => (int)$product['product_id'],
                    'code' => $product['product_code'],
                    'name' => $product['product_name'],
                    'specification' => $product['specification'],
                    'price' => (int)$product['price'],
                    'remarks' => $product['remarks'],
                    'image_url' => image_url($product['image_filename']),
                    'formatted_price' => format_price($product['price'])
                ];
            }
            
            $this->json([
                'success' => true,
                'age_group' => $age,
                'age_group_label' => get_age_group_label($age),
                'products' => $result,
                'count' => count($result)
            ]);
            
        } catch (Exception $e) {
            error_log("API Get Products By Age Error: " . $e->getMessage());
            $this->json(['error' => 'データの取得に失敗しました'], 500);
        }
    }
    
    /**
     * CSRFトークンを取得
     */
    public function getCsrfToken() {
        try {
            $token = $this->generateCsrfToken();
            
            $this->json([
                'success' => true,
                'csrf_token' => $token
            ]);
            
        } catch (Exception $e) {
            error_log("API Get CSRF Token Error: " . $e->getMessage());
            $this->json(['error' => 'トークンの生成に失敗しました'], 500);
        }
    }
}
?>