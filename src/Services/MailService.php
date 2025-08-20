<?php
namespace App\Services;

use Exception;

/**
 * メール送信サービス
 * 
 * PHPMailer を使用したメール送信機能を提供
 * COREサーバーのSMTP環境に対応
 */
class MailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_encryption;
    private $from_address;
    private $from_name;
    
    public function __construct() {
        $this->smtp_host = MAIL_HOST;
        $this->smtp_port = MAIL_PORT;
        $this->smtp_username = MAIL_USERNAME;
        $this->smtp_password = MAIL_PASSWORD;
        $this->smtp_encryption = MAIL_ENCRYPTION;
        $this->from_address = MAIL_FROM_ADDRESS;
        $this->from_name = MAIL_FROM_NAME;
    }
    
    /**
     * 注文完了メールを管理者に送信
     */
    public function sendOrderNotification($orderData, $orderItems) {
        try {
            $subject = "【双康幼稚園】新しい用品申込がありました（申込ID: {$orderData['order_number']}）";
            
            // メール本文作成
            $body = $this->createOrderNotificationBody($orderData, $orderItems);
            
            // 管理者に送信
            $this->sendMail(ADMIN_EMAIL, $subject, $body);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Mail Service Error (Order Notification): " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * お問い合わせ通知メールを送信
     */
    public function sendContactNotification($contactData) {
        try {
            $subject = "【双康幼稚園】お問い合わせがありました";
            
            $body = "新しいお問い合わせがありました。\n\n";
            $body .= "差出人: {$contactData['name']}\n";
            $body .= "メールアドレス: {$contactData['email']}\n";
            $body .= "件名: {$contactData['subject']}\n";
            $body .= "受信日時: " . date('Y年m月d日 H:i') . "\n\n";
            $body .= "お問い合わせ内容:\n";
            $body .= "----------------------------------------\n";
            $body .= $contactData['message'] . "\n";
            $body .= "----------------------------------------\n\n";
            $body .= "このメールは双康幼稚園用品申込サイトから自動送信されています。\n";
            
            $this->sendMail(ADMIN_EMAIL, $subject, $body);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Mail Service Error (Contact Notification): " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 注文確認メールを申込者に送信（オプション機能）
     */
    public function sendOrderConfirmation($orderData, $orderItems, $customerEmail) {
        try {
            $subject = "【双康幼稚園】用品申込を受け付けました（申込番号: {$orderData['order_number']}）";
            
            $body = "{$orderData['parent_name']} 様\n\n";
            $body .= "この度は双康幼稚園用品申込サイトをご利用いただき、ありがとうございます。\n";
            $body .= "以下の内容で申込を受け付けいたしました。\n\n";
            $body .= "申込番号: {$orderData['order_number']}\n";
            $body .= "申込日時: " . date('Y年m月d日 H:i', strtotime($orderData['order_date'])) . "\n";
            $body .= "保護者名: {$orderData['parent_name']}\n";
            $body .= "入園児氏名: {$orderData['child_name']}（{$orderData['child_name_kana']}）\n";
            $body .= "年齢区分: " . get_age_group_label($orderData['age_group']) . "\n\n";
            
            $body .= "【申込内容】\n";
            foreach ($orderItems as $item) {
                $body .= "・{$item['product_name']} × {$item['quantity']} = " . format_price($item['subtotal']) . "\n";
                if (!empty($item['specification'])) {
                    $body .= "  規格: {$item['specification']}\n";
                }
            }
            
            $body .= "\n合計金額: " . format_price($orderData['total_amount']) . "\n\n";
            $body .= "※このメールは送信専用です。返信はできません。\n";
            $body .= "ご不明な点がございましたら、幼稚園までお問い合わせください。\n\n";
            $body .= "双康幼稚園\n";
            
            $this->sendMail($customerEmail, $subject, $body);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Mail Service Error (Order Confirmation): " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * メール送信処理
     */
    private function sendMail($to, $subject, $body) {
        // COREサーバーなどの共用サーバー環境では、PHPのmail()関数または
        // mb_send_mail()関数を使用することが多い
        // ここでは mb_send_mail() を使用した実装を提供
        
        // 文字エンコーディング設定
        mb_language('Japanese');
        mb_internal_encoding('UTF-8');
        
        // ヘッダー作成
        $headers = [];
        $headers[] = "From: " . mb_encode_mimeheader($this->from_name) . " <{$this->from_address}>";
        $headers[] = "Reply-To: {$this->from_address}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        
        $header = implode("\r\n", $headers);
        
        // 件名をエンコード
        $encoded_subject = mb_encode_mimeheader($subject);
        
        // メール送信
        if (!mb_send_mail($to, $encoded_subject, $body, $header)) {
            throw new Exception("メールの送信に失敗しました。");
        }
        
        // ログ記録
        error_log("Mail sent successfully to: {$to}, Subject: {$subject}");
    }
    
    /**
     * 注文通知メール本文を作成
     */
    private function createOrderNotificationBody($orderData, $orderItems) {
        $body = "「{$orderData['parent_name']}」様から、お子様「{$orderData['child_name']}（{$orderData['child_name_kana']}）」の注文が入りました。\n\n";
        
        $body .= "申込ID: {$orderData['order_number']}\n";
        $body .= "申込日時: " . date('Y年m月d日 H:i', strtotime($orderData['order_date'])) . "\n";
        $body .= "年齢区分: " . get_age_group_label($orderData['age_group']) . "\n\n";
        
        $body .= "注文内容:\n";
        $body .= "----------------------------------------\n";
        
        foreach ($orderItems as $item) {
            $body .= "・{$item['product_name']} × {$item['quantity']} = " . format_price($item['subtotal']) . "\n";
            if (!empty($item['specification'])) {
                $body .= "  規格: {$item['specification']}\n";
            }
        }
        
        $body .= "----------------------------------------\n";
        $body .= "合計金額: " . format_price($orderData['total_amount']) . "\n";
        $body .= "合計数量: {$orderData['total_quantity']} 点\n\n";
        
        // 申込者情報
        $body .= "【申込者情報】\n";
        $body .= "保護者名: {$orderData['parent_name']}\n";
        $body .= "入園児氏名: {$orderData['child_name']}\n";
        $body .= "フリガナ: {$orderData['child_name_kana']}\n";
        $body .= "年齢区分: " . get_age_group_label($orderData['age_group']) . "\n\n";
        
        // システム情報
        $body .= "【システム情報】\n";
        $body .= "IPアドレス: {$orderData['ip_address']}\n";
        $body .= "送信日時: " . date('Y年m月d日 H:i:s') . "\n\n";
        
        $body .= "このメールは双康幼稚園用品申込サイトから自動送信されています。\n";
        $body .= "管理画面: " . url('/admin') . "\n";
        
        return $body;
    }
    
    /**
     * SMTP接続テスト
     */
    public function testConnection() {
        try {
            // 簡単な接続テスト用メール
            $testBody = "メール送信機能のテストです。\n送信時刻: " . date('Y年m月d日 H:i:s');
            $this->sendMail(ADMIN_EMAIL, "【テスト】メール送信テスト", $testBody);
            return true;
        } catch (Exception $e) {
            throw new Exception("メール送信テストに失敗しました: " . $e->getMessage());
        }
    }
    
    /**
     * メール設定の検証
     */
    public function validateConfiguration() {
        $errors = [];
        
        if (empty($this->from_address)) {
            $errors[] = "送信者メールアドレスが設定されていません";
        }
        
        if (empty($this->from_name)) {
            $errors[] = "送信者名が設定されていません";
        }
        
        if (empty(ADMIN_EMAIL)) {
            $errors[] = "管理者メールアドレスが設定されていません";
        }
        
        if (!filter_var($this->from_address, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "送信者メールアドレスの形式が正しくありません";
        }
        
        if (!filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "管理者メールアドレスの形式が正しくありません";
        }
        
        return $errors;
    }
    
    /**
     * エラー通知メールを送信
     */
    public function sendErrorNotification($error, $context = []) {
        try {
            $subject = "【双康幼稚園】システムエラー通知";
            
            $body = "システムでエラーが発生しました。\n\n";
            $body .= "エラー内容: {$error}\n";
            $body .= "発生日時: " . date('Y年m月d日 H:i:s') . "\n";
            
            if (!empty($context)) {
                $body .= "コンテキスト:\n" . print_r($context, true) . "\n";
            }
            
            $body .= "IPアドレス: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $body .= "ユーザーエージェント: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n\n";
            $body .= "このメールは自動送信されています。\n";
            
            $this->sendMail(ADMIN_EMAIL, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Failed to send error notification email: " . $e->getMessage());
        }
    }
}
?>