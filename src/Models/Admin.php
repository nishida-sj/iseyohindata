<?php
namespace App\Models;

use App\Core\Model;
use Exception;

/**
 * 管理者モデル
 * 
 * 管理者アカウントの管理を行う
 */
class Admin extends Model {
    protected $table = 'admins';
    protected $fillable = [
        'username', 'password', 'email', 'display_name', 'is_active', 'last_login_at'
    ];
    
    /**
     * ユーザー名で管理者を検索
     */
    public function findByUsername($username) {
        return $this->whereOne(['username' => $username]);
    }
    
    /**
     * メールアドレスで管理者を検索
     */
    public function findByEmail($email) {
        return $this->whereOne(['email' => $email]);
    }
    
    /**
     * ログイン認証
     */
    public function authenticate($username, $password) {
        $admin = $this->findByUsername($username);
        
        if (!$admin) {
            return false;
        }
        
        if (!$admin['is_active']) {
            throw new Exception('このアカウントは無効です。');
        }
        
        if (!password_verify($password, $admin['password'])) {
            return false;
        }
        
        // 最終ログイン日時を更新
        $this->updateLastLogin($admin['id']);
        
        return $admin;
    }
    
    /**
     * 最終ログイン日時を更新
     */
    public function updateLastLogin($adminId) {
        return $this->update($adminId, ['last_login_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * パスワードをハッシュ化して管理者を作成
     */
    public function createAdmin($data) {
        // ユーザー名の重複チェック
        if ($this->exists(['username' => $data['username']])) {
            throw new Exception('ユーザー名が既に存在します。');
        }
        
        // メールアドレスの重複チェック
        if (!empty($data['email']) && $this->exists(['email' => $data['email']])) {
            throw new Exception('メールアドレスが既に存在します。');
        }
        
        // パスワードをハッシュ化
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        return $this->create($data);
    }
    
    /**
     * 管理者情報を更新（パスワード変更含む）
     */
    public function updateAdmin($id, $data) {
        $admin = $this->find($id);
        if (!$admin) {
            throw new Exception('管理者が見つかりません。');
        }
        
        // ユーザー名の重複チェック（自分以外）
        if (isset($data['username'])) {
            $existingAdmin = $this->findByUsername($data['username']);
            if ($existingAdmin && $existingAdmin['id'] != $id) {
                throw new Exception('ユーザー名が既に存在します。');
            }
        }
        
        // メールアドレスの重複チェック（自分以外）
        if (isset($data['email']) && !empty($data['email'])) {
            $existingAdmin = $this->findByEmail($data['email']);
            if ($existingAdmin && $existingAdmin['id'] != $id) {
                throw new Exception('メールアドレスが既に存在します。');
            }
        }
        
        // パスワードが設定されている場合はハッシュ化
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // パスワードが空の場合は更新しない
            unset($data['password']);
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * パスワードを変更
     */
    public function changePassword($adminId, $currentPassword, $newPassword) {
        $admin = $this->find($adminId);
        if (!$admin) {
            throw new Exception('管理者が見つかりません。');
        }
        
        // 現在のパスワードを確認
        if (!password_verify($currentPassword, $admin['password'])) {
            throw new Exception('現在のパスワードが正しくありません。');
        }
        
        // 新しいパスワードをハッシュ化して更新
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->update($adminId, ['password' => $hashedPassword]);
    }
    
    /**
     * 有効な管理者のみを取得
     */
    public function getActive() {
        return $this->where(['is_active' => 1], 'username ASC');
    }
    
    /**
     * 管理者のアクティビティログを記録
     */
    public function logActivity($adminId, $action, $details = null) {
        $systemLogModel = new SystemLog();
        
        return $systemLogModel->create([
            'level' => 'info',
            'message' => $action,
            'context' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'admin_id' => $adminId
        ]);
    }
    
    /**
     * 管理者の統計情報を取得
     */
    public function getStats() {
        $sql = "
            SELECT 
                COUNT(*) as total_admins,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_admins,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_admins,
                SUM(CASE WHEN last_login_at IS NOT NULL THEN 1 ELSE 0 END) as logged_in_admins,
                SUM(CASE WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_active_admins
            FROM {$this->table}
        ";
        
        $result = $this->db->selectOne($sql);
        return $result ?: [
            'total_admins' => 0,
            'active_admins' => 0,
            'inactive_admins' => 0,
            'logged_in_admins' => 0,
            'recent_active_admins' => 0
        ];
    }
    
    /**
     * 最近のログイン履歴を取得
     */
    public function getRecentLogins($limit = 10) {
        return $this->where(['is_active' => 1], 'last_login_at DESC', $limit);
    }
    
    /**
     * セッション情報をクリーンアップ
     */
    public function cleanupSessions() {
        // 実装はセッション管理方式に依存
        // ここでは基本的なセッション変数のクリア
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_display_name']);
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_last_activity']);
    }
    
    /**
     * セッション情報を設定
     */
    public function setSession($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_display_name'] = $admin['display_name'];
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_last_activity'] = time();
        
        // CSRFトークンを再生成
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    /**
     * セッションの有効性をチェック
     */
    public function isValidSession() {
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            return false;
        }
        
        if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_last_activity'])) {
            return false;
        }
        
        // セッションタイムアウトチェック（30分）
        $sessionTimeout = 30 * 60; // 30分
        if (time() - $_SESSION['admin_last_activity'] > $sessionTimeout) {
            return false;
        }
        
        // 管理者がまだ有効かチェック
        $admin = $this->find($_SESSION['admin_id']);
        if (!$admin || !$admin['is_active']) {
            return false;
        }
        
        // 最終活動時間を更新
        $_SESSION['admin_last_activity'] = time();
        
        return true;
    }
    
    /**
     * パスワード強度をチェック
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'パスワードは8文字以上である必要があります。';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'パスワードには小文字を含める必要があります。';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'パスワードには大文字を含める必要があります。';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'パスワードには数字を含める必要があります。';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'パスワードには記号を含める必要があります。';
        }
        
        return $errors;
    }
}
?>