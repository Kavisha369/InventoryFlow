<?php
declare(strict_types=1);
class DB {
    private static ?PDO $instance = null;
    private const HOST    = 'localhost';
    private const PORT    = '3306';
    private const DBNAME  = 'inventory_system_db';
    private const USER    = 'root';
    private const PASS    = '';
    private const CHARSET = 'utf8mb4';
    private function __construct() {}
    private function __clone()     {}
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', self::HOST, self::PORT, self::DBNAME, self::CHARSET);
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::MYSQL_ATTR_FOUND_ROWS => true];
            try { self::$instance = new PDO($dsn, self::USER, self::PASS, $options); }
            catch (PDOException $e) { error_log('[DB] Connection failed: ' . $e->getMessage()); throw new RuntimeException('Database connection unavailable.'); }
        }
        return self::$instance;
    }
    public static function query(string $sql, array $params = []): PDOStatement { $stmt = self::getInstance()->prepare($sql); $stmt->execute($params); return $stmt; }
    public static function beginTransaction(): void { self::getInstance()->beginTransaction(); }
    public static function commit(): void { self::getInstance()->commit(); }
    public static function rollBack(): void { self::getInstance()->rollBack(); }
    public static function lastInsertId(): string { return self::getInstance()->lastInsertId(); }
}
