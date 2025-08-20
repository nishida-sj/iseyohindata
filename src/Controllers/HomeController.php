<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemLog;

/**
 * ホームコントローラー
 * 
 * サイトのトップページを管理
 */
class HomeController extends Controller {
    
    /**
     * トップページ表示
     */
    public function index() {
        try {
            // CSRFトークン生成
            $csrfToken = $this->generateCsrfToken();
            
            // 申込期間チェック
            $orderEnabled = is_order_period();
            
            // 統計情報取得（参考表示用）
            $orderModel = new Order();
            $productModel = new Product();
            
            $stats = [
                'total_orders' => $orderModel->count(),
                'active_products' => $productModel->count(['is_active' => 1]),
                'today_orders' => $orderModel->getTodayStats()['order_count']
            ];
            
            // ビューに渡すデータ
            $data = [
                'page_title' => '双康幼稚園用品申込サイト',
                'csrf_token' => $csrfToken,
                'order_enabled' => $orderEnabled,
                'order_start_date' => ORDER_START_DATE,
                'order_end_date' => ORDER_END_DATE,
                'stats' => $stats,
                'age_groups' => AGE_GROUPS
            ];
            
            $this->view('home.index', $data);
            
        } catch (Exception $e) {
            error_log("Home Index Error: " . $e->getMessage());
            $this->view('error.500', ['message' => 'ページの読み込みに失敗しました。']);
        }
    }
    
    /**
     * サイト情報ページ
     */
    public function about() {
        $data = [
            'page_title' => 'サイトについて - 双康幼稚園用品申込サイト'
        ];
        
        $this->view('home.about', $data);
    }
    
    /**
     * 利用案内ページ
     */
    public function guide() {
        $data = [
            'page_title' => 'ご利用案内 - 双康幼稚園用品申込サイト',
            'age_groups' => AGE_GROUPS,
            'order_start_date' => ORDER_START_DATE,
            'order_end_date' => ORDER_END_DATE
        ];
        
        $this->view('home.guide', $data);
    }
    
    /**
     * お問い合わせページ
     */
    public function contact() {
        $data = [
            'page_title' => 'お問い合わせ - 双康幼稚園用品申込サイト',
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        if ($this->isPost()) {
            $this->handleContactForm();
            return;
        }
        
        $this->view('home.contact', $data);
    }
    
    /**
     * お問い合わせフォーム処理
     */
    private function handleContactForm() {
        try {
            // CSRF検証
            if (!$this->validateCsrfToken()) {
                throw new Exception('不正なリクエストです。');
            }
            
            // 入力値取得
            $input = $this->allInput();
            
            // バリデーション
            $rules = [
                'name' => 'required|max:100',
                'email' => 'required|email|max:255',
                'subject' => 'required|max:200',
                'message' => 'required|max:2000'
            ];
            
            $errors = $this->validate($input, $rules);
            
            if (!empty($errors)) {
                set_errors($errors);
                set_old_input($input);
                $this->redirect('/contact');
                return;
            }
            
            // メール送信（実装は後で）
            // TODO: メール送信機能を実装
            
            // ログ記録
            $systemLog = new SystemLog();
            $systemLog->info('お問い合わせ受信', [
                'name' => $input['name'],
                'email' => $input['email'],
                'subject' => $input['subject']
            ]);
            
            $this->setFlash('success', 'お問い合わせを受け付けました。回答までしばらくお待ちください。');
            $this->redirect('/contact');
            
        } catch (Exception $e) {
            error_log("Contact Form Error: " . $e->getMessage());
            $this->setFlash('error', $e->getMessage());
            $this->redirect('/contact');
        }
    }
    
    /**
     * プライバシーポリシー
     */
    public function privacy() {
        $data = [
            'page_title' => 'プライバシーポリシー - 双康幼稚園用品申込サイト'
        ];
        
        $this->view('home.privacy', $data);
    }
    
    /**
     * 利用規約
     */
    public function terms() {
        $data = [
            'page_title' => '利用規約 - 双康幼稚園用品申込サイト'
        ];
        
        $this->view('home.terms', $data);
    }
    
    /**
     * サイトマップ
     */
    public function sitemap() {
        $data = [
            'page_title' => 'サイトマップ - 双康幼稚園用品申込サイト'
        ];
        
        $this->view('home.sitemap', $data);
    }
    
    /**
     * メンテナンス画面
     */
    public function maintenance() {
        http_response_code(503);
        
        $data = [
            'page_title' => 'メンテナンス中 - 双康幼稚園用品申込サイト'
        ];
        
        $this->view('home.maintenance', $data);
    }
    
    /**
     * ヘルスチェック（システム監視用）
     */
    public function health() {
        try {
            // データベース接続確認
            $orderModel = new Order();
            $orderModel->count(); // 軽量なクエリ実行
            
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'database' => 'connected'
            ];
            
            // システムログからエラーチェック
            $systemLog = new SystemLog();
            $healthCheck = $systemLog->getHealthCheck();
            $health['log_status'] = $healthCheck['status'];
            
            if ($healthCheck['status'] !== 'healthy') {
                $health['warnings'] = $healthCheck['warnings'];
            }
            
            $this->json($health);
            
        } catch (Exception $e) {
            error_log("Health Check Error: " . $e->getMessage());
            
            $this->json([
                'status' => 'unhealthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => 'System check failed'
            ], 503);
        }
    }
    
    /**
     * ロボット.txt
     */
    public function robots() {
        header('Content-Type: text/plain');
        
        $robots = "User-agent: *\n";
        
        if (ENV === 'production') {
            $robots .= "Allow: /\n";
            $robots .= "Disallow: /admin/\n";
            $robots .= "Disallow: /storage/\n";
            $robots .= "Disallow: /logs/\n";
        } else {
            $robots .= "Disallow: /\n";
        }
        
        $robots .= "\nSitemap: " . url('sitemap') . "\n";
        
        echo $robots;
        exit;
    }
}
?>