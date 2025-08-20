<?php
namespace App\Core;

use Exception;

/**
 * ベースControllerクラス
 * 
 * 各コントローラーの基底クラス
 * ビューの読み込み、CSRFトークン管理、入力検証等を提供
 */
abstract class Controller {
    protected $data = [];
    
    /**
     * ビューファイルを読み込んで表示
     */
    protected function view($viewName, $data = []) {
        // データをマージ
        $this->data = array_merge($this->data, $data);
        
        // 変数を展開
        extract($this->data);
        
        $viewPath = ROOT_PATH . '/views/' . str_replace('.', '/', $viewName) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new Exception("ビューファイルが見つかりません: {$viewPath}");
        }
        
        include $viewPath;
    }
    
    /**
     * JSONレスポンスを返す
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * リダイレクト
     */
    protected function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * POSTリクエストかどうか確認
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * GETリクエストかどうか確認
     */
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * 入力値を取得（サニタイズ付き）
     */
    protected function input($key, $default = null) {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        
        if (is_string($value)) {
            return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }
        
        return $value;
    }
    
    /**
     * 全入力値を取得
     */
    protected function allInput() {
        $input = array_merge($_GET, $_POST);
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * CSRFトークンを生成
     */
    protected function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) || 
            time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE) {
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * CSRFトークンを検証
     */
    protected function validateCsrfToken($token = null) {
        if ($token === null) {
            $token = $this->input('csrf_token');
        }
        
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) || 
            time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * フラッシュメッセージを設定
     */
    protected function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }
    
    /**
     * フラッシュメッセージを取得
     */
    protected function getFlash($type = null) {
        if ($type === null) {
            $flash = $_SESSION['flash'] ?? [];
            unset($_SESSION['flash']);
            return $flash;
        }
        
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    
    /**
     * バリデーション実行
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $fieldRules = explode('|', $rule);
            
            foreach ($fieldRules as $singleRule) {
                $params = explode(':', $singleRule);
                $ruleName = $params[0];
                $ruleParam = $params[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "{$field}は必須です。";
                        }
                        break;
                        
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "{$field}の形式が正しくありません。";
                        }
                        break;
                        
                    case 'min':
                        if (!empty($value) && mb_strlen($value) < (int)$ruleParam) {
                            $errors[$field][] = "{$field}は{$ruleParam}文字以上で入力してください。";
                        }
                        break;
                        
                    case 'max':
                        if (!empty($value) && mb_strlen($value) > (int)$ruleParam) {
                            $errors[$field][] = "{$field}は{$ruleParam}文字以下で入力してください。";
                        }
                        break;
                        
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = "{$field}は数値で入力してください。";
                        }
                        break;
                        
                    case 'katakana':
                        if (!empty($value) && !preg_match('/^[ァ-ヶー　\s]+$/u', $value)) {
                            $errors[$field][] = "{$field}は全角カタカナで入力してください。";
                        }
                        break;
                        
                    case 'in':
                        $allowedValues = explode(',', $ruleParam);
                        if (!empty($value) && !in_array($value, $allowedValues)) {
                            $errors[$field][] = "{$field}の値が正しくありません。";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * アップロードファイルの処理
     */
    protected function handleFileUpload($fieldName, $uploadPath, $allowedExtensions = null) {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $file = $_FILES[$fieldName];
        
        // ファイルサイズチェック
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception('ファイルサイズが大きすぎます。');
        }
        
        // 拡張子チェック
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($allowedExtensions && !in_array($extension, $allowedExtensions)) {
            throw new Exception('許可されていないファイル形式です。');
        }
        
        // ファイル名の生成（衝突回避）
        $fileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $filePath = rtrim($uploadPath, '/') . '/' . $fileName;
        
        // アップロードディレクトリの作成
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // ファイル移動
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('ファイルのアップロードに失敗しました。');
        }
        
        return $fileName;
    }
    
    /**
     * 404エラーを返す
     */
    protected function notFound() {
        http_response_code(404);
        $this->view('error.404');
        exit;
    }
    
    /**
     * 403エラーを返す
     */
    protected function forbidden() {
        http_response_code(403);
        $this->view('error.403');
        exit;
    }
    
    /**
     * デバッグ用：変数をダンプ
     */
    protected function dd($data) {
        if (ENV !== 'production') {
            echo '<pre>';
            var_dump($data);
            echo '</pre>';
            exit;
        }
    }
}
?>