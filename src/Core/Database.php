<?php
namespace App\Core;

use PDO;
use PDOException;
use Exception;

/**
 * データベース接続・操作クラス
 * 
 * PDOを使用したMySQL接続とCRUD操作を提供
 * SQLインジェクション対策としてプリペアドステートメントを使用
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * シングルトンパターンでインスタンスを取得
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * データベース接続
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // SQLインジェクション対策
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("データベース接続に失敗しました。");
        }
    }
    
    /**
     * PDO接続オブジェクトを取得
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * SELECT文を実行
     * 
     * @param string $sql SQL文
     * @param array $params パラメータ配列
     * @return array 結果配列
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Select Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("データの取得に失敗しました。");
        }
    }
    
    /**
     * SELECT文を実行（単一レコード）
     */
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database SelectOne Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("データの取得に失敗しました。");
        }
    }
    
    /**
     * INSERT文を実行
     * 
     * @param string $sql SQL文
     * @param array $params パラメータ配列
     * @return int 挿入されたレコードのID
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database Insert Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("データの挿入に失敗しました。");
        }
    }
    
    /**
     * UPDATE文を実行
     */
    public function update($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database Update Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("データの更新に失敗しました。");
        }
    }
    
    /**
     * DELETE文を実行
     */
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database Delete Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("データの削除に失敗しました。");
        }
    }
    
    /**
     * トランザクション開始
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * コミット
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * ロールバック
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * テーブル存在確認
     */
    public function tableExists($tableName) {
        try {
            $sql = "SHOW TABLES LIKE :table_name";
            $result = $this->selectOne($sql, ['table_name' => $tableName]);
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * クエリ実行（汎用）
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database Execute Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("クエリの実行に失敗しました。");
        }
    }
}
?>