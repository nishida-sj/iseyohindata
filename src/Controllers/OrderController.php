<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\AgeGroupProduct;
use App\Models\SystemLog;
use Exception;

/**
 * 注文コントローラー
 * 
 * WEB注文機能を管理
 */
class OrderController extends Controller {
    
    /**
     * 注文入力画面
     */
    public function index() {
        try {
            // 申込期間チェック
            if (!is_order_period()) {
                $this->view('order.closed', [
                    'page_title' => '申込受付終了 - 双康幼稚園用品申込サイト',
                    'order_start_date' => ORDER_START_DATE,
                    'order_end_date' => ORDER_END_DATE
                ]);
                return;
            }
            
            // CSRFトークン生成
            $csrfToken = $this->generateCsrfToken();
            
            // 年齢区分リスト
            $ageGroups = AGE_GROUPS;
            
            // バリデーションエラーがある場合の処理
            $errors = $this->getFlash('errors') ?? [];
            $oldInput = $this->getFlash('old_input') ?? [];
            
            $data = [
                'page_title' => 'ご用品申込 - 双康幼稚園用品申込サイト',
                'csrf_token' => $csrfToken,
                'age_groups' => $ageGroups,
                'errors' => $errors,
                'old_input' => $oldInput
            ];
            
            $this->view('order.index', $data);
            
        } catch (Exception $e) {
            error_log("Order Index Error: " . $e->getMessage());
            $this->view('error.500', ['message' => 'ページの読み込みに失敗しました。']);
        }
    }
    
    /**
     * 注文処理（確認画面へ）
     */
    public function store() {
        try {
            // 申込期間チェック
            if (!is_order_period()) {
                $this->setFlash('error', '申込期間外です。');
                $this->redirect('/');
                return;
            }
            
            // CSRF検証
            if (!$this->validateCsrfToken()) {
                throw new Exception('不正なリクエストです。');
            }
            
            // 入力値取得
            $input = $this->allInput();
            
            // バリデーション
            $errors = $this->validateOrderInput($input);
            
            if (!empty($errors)) {
                $this->setFlash('errors', $errors);
                $this->setFlash('old_input', $input);
                $this->redirect('/order');
                return;
            }
            
            // 商品データの取得と検証
            $orderItems = $this->validateAndPrepareOrderItems($input);
            
            if (empty($orderItems)) {
                $this->setFlash('error', '商品が選択されていません。');
                $this->setFlash('old_input', $input);
                $this->redirect('/order');
                return;
            }
            
            // セッションに注文データを保存
            $_SESSION['order_data'] = [
                'parent_name' => $input['parent_name'],
                'child_name' => $input['child_name'],
                'child_name_kana' => $input['child_name_kana'],
                'age_group' => $input['age_group'],
                'items' => $orderItems
            ];
            
            $this->redirect('/order/confirm');
            
        } catch (Exception $e) {
            error_log("Order Store Error: " . $e->getMessage());
            $this->setFlash('error', $e->getMessage());
            $this->setFlash('old_input', $input ?? []);
            $this->redirect('/order');
        }
    }
    
    /**
     * 注文確認画面
     */
    public function confirm() {
        try {
            // セッションから注文データを取得
            if (!isset($_SESSION['order_data'])) {
                $this->setFlash('error', '注文データが見つかりません。最初からやり直してください。');
                $this->redirect('/order');
                return;
            }
            
            $orderData = $_SESSION['order_data'];
            
            // 合計金額・数量の計算
            $totalAmount = 0;
            $totalQuantity = 0;
            
            foreach ($orderData['items'] as $item) {
                $totalAmount += $item['subtotal'];
                $totalQuantity += $item['quantity'];
            }
            
            // CSRFトークン生成
            $csrfToken = $this->generateCsrfToken();
            
            $data = [
                'page_title' => '注文確認 - 双康幼稚園用品申込サイト',
                'csrf_token' => $csrfToken,
                'order_data' => $orderData,
                'total_amount' => $totalAmount,
                'total_quantity' => $totalQuantity,
                'age_group_label' => get_age_group_label($orderData['age_group'])
            ];
            
            $this->view('order.confirm', $data);
            
        } catch (Exception $e) {
            error_log("Order Confirm Error: " . $e->getMessage());
            $this->setFlash('error', 'エラーが発生しました。最初からやり直してください。');
            $this->redirect('/order');
        }
    }
    
    /**
     * 注文完了処理
     */
    public function complete() {
        try {
            // 申込期間チェック
            if (!is_order_period()) {
                $this->setFlash('error', '申込期間外です。');
                $this->redirect('/');
                return;
            }
            
            // CSRF検証
            if (!$this->validateCsrfToken()) {
                throw new Exception('不正なリクエストです。');
            }
            
            // セッションから注文データを取得
            if (!isset($_SESSION['order_data'])) {
                throw new Exception('注文データが見つかりません。');
            }
            
            $orderData = $_SESSION['order_data'];
            
            // 注文を作成
            $orderModel = new Order();
            $result = $orderModel->createWithItems($orderData, $orderData['items']);
            
            // 成功時のログ記録
            $systemLog = new SystemLog();
            $systemLog->info('注文完了', [
                'order_id' => $result['order_id'],
                'order_number' => $result['order_number'],
                'parent_name' => $orderData['parent_name'],
                'child_name' => $orderData['child_name'],
                'age_group' => $orderData['age_group'],
                'total_amount' => array_sum(array_column($orderData['items'], 'subtotal')),
                'total_quantity' => array_sum(array_column($orderData['items'], 'quantity'))
            ]);
            
            // メール送信（実装は後で）
            // TODO: 管理者宛メール送信
            
            // セッションクリア
            unset($_SESSION['order_data']);
            
            // 完了画面にリダイレクト
            $this->redirect('/order/thanks?order_number=' . urlencode($result['order_number']));
            
        } catch (Exception $e) {
            error_log("Order Complete Error: " . $e->getMessage());
            
            // エラーログ記録
            $systemLog = new SystemLog();
            $systemLog->error('注文処理エラー', [
                'error' => $e->getMessage(),
                'order_data' => $_SESSION['order_data'] ?? null
            ]);
            
            $this->setFlash('error', '注文処理中にエラーが発生しました。恐れ入りますが、再度お試しください。');
            $this->redirect('/order/confirm');
        }
    }
    
    /**
     * 注文完了画面
     */
    public function thanks() {
        $orderNumber = $this->input('order_number');
        
        if (empty($orderNumber)) {
            $this->redirect('/');
            return;
        }
        
        // 注文番号の妥当性チェック
        $orderModel = new Order();
        $order = $orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            $this->setFlash('error', '注文が見つかりません。');
            $this->redirect('/');
            return;
        }
        
        $data = [
            'page_title' => '注文完了 - 双康幼稚園用品申込サイト',
            'order_number' => $orderNumber,
            'order' => $order,
            'age_group_label' => get_age_group_label($order['age_group'])
        ];
        
        $this->view('order.thanks', $data);
    }
    
    /**
     * 注文入力データのバリデーション
     */
    private function validateOrderInput($input) {
        $rules = [
            'parent_name' => 'required|max:100',
            'child_name' => 'required|max:100',
            'child_name_kana' => 'required|katakana|max:100',
            'age_group' => 'required|in:2,3,4,5'
        ];
        
        return $this->validate($input, $rules);
    }
    
    /**
     * 注文商品データの検証と準備
     */
    private function validateAndPrepareOrderItems($input) {
        $orderItems = [];
        $ageGroup = $input['age_group'];
        
        // 年齢に対応する商品を取得
        $ageGroupProductModel = new AgeGroupProduct();
        $availableProducts = $ageGroupProductModel->getActiveByAgeGroup($ageGroup);
        
        // 利用可能商品のIDリストを作成
        $availableProductIds = array_column($availableProducts, 'product_id');
        
        foreach ($input as $key => $value) {
            // quantity_で始まるキーをチェック
            if (strpos($key, 'quantity_') === 0) {
                $productId = (int)substr($key, 9); // 'quantity_'の部分を除去
                $quantity = (int)$value;
                
                if ($quantity > 0) {
                    // 商品IDが年齢に対応しているかチェック
                    if (!in_array($productId, $availableProductIds)) {
                        throw new Exception('選択された商品は対象年齢に対応していません。');
                    }
                    
                    // 商品情報を取得
                    $product = null;
                    foreach ($availableProducts as $p) {
                        if ($p['product_id'] == $productId) {
                            $product = $p;
                            break;
                        }
                    }
                    
                    if (!$product) {
                        throw new Exception('商品情報の取得に失敗しました。');
                    }
                    
                    // 数量チェック
                    if ($quantity < 1 || $quantity > 99) {
                        throw new Exception('数量は1〜99の範囲で入力してください。');
                    }
                    
                    $subtotal = $product['price'] * $quantity;
                    
                    $orderItems[] = [
                        'product_id' => $productId,
                        'product_code' => $product['product_code'],
                        'product_name' => $product['product_name'],
                        'specification' => $product['specification'],
                        'unit_price' => $product['price'],
                        'quantity' => $quantity,
                        'subtotal' => $subtotal
                    ];
                }
            }
        }
        
        return $orderItems;
    }
    
    /**
     * 戻る処理（確認画面から入力画面へ）
     */
    public function back() {
        // セッションに注文データがある場合は入力フォームに復元
        if (isset($_SESSION['order_data'])) {
            $orderData = $_SESSION['order_data'];
            
            // 古い入力値として設定
            $oldInput = [
                'parent_name' => $orderData['parent_name'],
                'child_name' => $orderData['child_name'],
                'child_name_kana' => $orderData['child_name_kana'],
                'age_group' => $orderData['age_group']
            ];
            
            // 商品数量も復元
            foreach ($orderData['items'] as $item) {
                $oldInput['quantity_' . $item['product_id']] = $item['quantity'];
            }
            
            $this->setFlash('old_input', $oldInput);
        }
        
        $this->redirect('/order');
    }
    
    /**
     * 注文のキャンセル
     */
    public function cancel() {
        // セッションから注文データを削除
        unset($_SESSION['order_data']);
        
        $this->setFlash('info', '注文をキャンセルしました。');
        $this->redirect('/');
    }
}
?>