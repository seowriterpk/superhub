
<?php
namespace VidSocial\Core;

use PDO;
use PDOException;

/**
 * Database Connection Class
 * Singleton pattern for MySQL connection
 */
class Database
{
    private static $instance = null;
    private $pdo;
    
    private function __construct()
    {
        $app = Application::getInstance();
        
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $app->config('db_host'),
            $app->config('db_name')
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            $this->pdo = new PDO(
                $dsn,
                $app->config('db_user'),
                $app->config('db_pass'),
                $options
            );
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
    
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function insert(string $table, array $data): int
    {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return (int)$this->pdo->lastInsertId();
    }
    
    public function update(string $table, array $data, array $where): int
    {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "{$key} = :{$key}";
        }
        
        $whereClause = [];
        foreach (array_keys($where) as $key) {
            $whereClause[] = "{$key} = :where_{$key}";
            $data["where_{$key}"] = $where[$key];
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $whereClause);
        $stmt = $this->query($sql, $data);
        
        return $stmt->rowCount();
    }
    
    public function delete(string $table, array $where): int
    {
        $whereClause = [];
        foreach (array_keys($where) as $key) {
            $whereClause[] = "{$key} = :{$key}";
        }
        
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);
        $stmt = $this->query($sql, $where);
        
        return $stmt->rowCount();
    }
}
