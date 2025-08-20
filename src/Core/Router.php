<?php
namespace App\Core;

use Exception;

/**
 * ルーティングクラス
 * 
 * URLのルーティングを管理し、適切なコントローラーとアクションに振り分ける
 */
class Router {
    private $routes = [];
    private $params = [];
    
    /**
     * コンストラクタ
     * ルートを定義
     */
    public function __construct() {
        $this->defineRoutes();
    }
    
    /**
     * ルート定義
     */
    private function defineRoutes() {
        // 公開ページ
        $this->addRoute('GET', '/', 'HomeController@index');
        $this->addRoute('GET', '/order', 'OrderController@index');
        $this->addRoute('POST', '/order', 'OrderController@store');
        $this->addRoute('GET', '/order/confirm', 'OrderController@confirm');
        $this->addRoute('POST', '/order/complete', 'OrderController@complete');
        $this->addRoute('GET', '/order/thanks', 'OrderController@thanks');
        
        // 管理機能
        $this->addRoute('GET', '/admin', 'AdminController@index');
        $this->addRoute('GET', '/admin/login', 'AdminController@login');
        $this->addRoute('POST', '/admin/login', 'AdminController@authenticate');
        $this->addRoute('GET', '/admin/logout', 'AdminController@logout');
        
        // 商品管理
        $this->addRoute('GET', '/admin/products', 'ProductController@index');
        $this->addRoute('GET', '/admin/products/create', 'ProductController@create');
        $this->addRoute('POST', '/admin/products', 'ProductController@store');
        $this->addRoute('GET', '/admin/products/{id}', 'ProductController@show');
        $this->addRoute('GET', '/admin/products/{id}/edit', 'ProductController@edit');
        $this->addRoute('POST', '/admin/products/{id}', 'ProductController@update');
        $this->addRoute('POST', '/admin/products/{id}/delete', 'ProductController@delete');
        
        // 年齢別商品設定
        $this->addRoute('GET', '/admin/age-groups', 'AgeGroupController@index');
        $this->addRoute('GET', '/admin/age-groups/{age}/products', 'AgeGroupController@products');
        $this->addRoute('POST', '/admin/age-groups/{age}/products', 'AgeGroupController@updateProducts');
        
        // 注文管理
        $this->addRoute('GET', '/admin/orders', 'OrderManagementController@index');
        $this->addRoute('GET', '/admin/orders/{id}', 'OrderManagementController@show');
        
        // 集計機能
        $this->addRoute('GET', '/admin/reports', 'ReportController@index');
        $this->addRoute('POST', '/admin/reports/csv', 'ReportController@downloadCsv');
        
        // API
        $this->addRoute('GET', '/api/products/age/{age}', 'ApiController@getProductsByAge');
        $this->addRoute('POST', '/api/csrf-token', 'ApiController@getCsrfToken');
        
        // 静的ファイル
        $this->addRoute('GET', '/storage/{path}', 'FileController@serve');
    }
    
    /**
     * ルート追加
     */
    private function addRoute($method, $path, $action) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'action' => $action
        ];
    }
    
    /**
     * リクエストを解析してルーティング実行
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // public/ プレフィックスを除去（必要に応じて）
        if (strpos($uri, '/public') === 0) {
            $uri = substr($uri, 7);
        }
        
        // 末尾のスラッシュを除去
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }
        
        // ルートマッチング
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchRoute($route['path'], $uri)) {
                $this->callAction($route['action']);
                return;
            }
        }
        
        // 404エラー
        http_response_code(404);
        $this->view('error.404');
    }
    
    /**
     * ルートパターンマッチング
     */
    private function matchRoute($pattern, $uri) {
        // パラメータをリセット
        $this->params = [];
        
        // パターンを正規表現に変換
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $uri, $matches)) {
            // パラメータを抽出
            array_shift($matches); // 最初の完全一致を除去
            
            // パラメータ名を取得
            preg_match_all('/\{([a-zA-Z0-9_]+)\}/', func_get_arg(0), $paramNames);
            $paramNames = $paramNames[1];
            
            // パラメータをマッピング
            for ($i = 0; $i < count($paramNames); $i++) {
                if (isset($matches[$i])) {
                    $this->params[$paramNames[$i]] = $matches[$i];
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * アクション実行
     */
    private function callAction($action) {
        list($controllerName, $methodName) = explode('@', $action);
        
        $controllerClass = "App\\Controllers\\{$controllerName}";
        
        if (!class_exists($controllerClass)) {
            throw new Exception("コントローラーが見つかりません: {$controllerClass}");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $methodName)) {
            throw new Exception("メソッドが見つかりません: {$controllerClass}::{$methodName}");
        }
        
        // パラメータをコントローラーに渡す
        if (method_exists($controller, 'setParams')) {
            $controller->setParams($this->params);
        }
        
        // アクション実行
        call_user_func_array([$controller, $methodName], array_values($this->params));
    }
    
    /**
     * ビュー表示（エラー用）
     */
    private function view($viewName) {
        $viewPath = ROOT_PATH . '/views/' . str_replace('.', '/', $viewName) . '.php';
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "404 - Page Not Found";
        }
    }
    
    /**
     * URLヘルパー関数
     */
    public static function url($path = '') {
        $baseUrl = self::getBaseUrl();
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
    
    /**
     * ベースURL取得
     */
    private static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // サブドメイン環境での調整
        if (strpos($host, 'geo.jp') !== false) {
            // COREサーバーのサブドメイン環境
            return $protocol . '://' . $host;
        }
        
        $path = dirname($_SERVER['SCRIPT_NAME']);
        
        // public/ディレクトリが含まれている場合は除去
        if (basename($path) === 'public') {
            $path = dirname($path);
        }
        
        return $protocol . '://' . $host . $path;
    }
    
    /**
     * リダイレクトヘルパー
     */
    public static function redirect($path, $statusCode = 302) {
        $url = self::url($path);
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
}
?>