<?php
/**
 * WebConnect - Database Connection
 * PDO wrapper with error handling.
 */

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                @error_log('[DB Error] ' . $e->getMessage() . ' - ' . date('Y-m-d H:i:s'), 3, ERROR_LOG);
                die('Database connection failed. Please contact the administrator.');
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        $db = self::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function fetchColumn(string $sql, array $params = []): mixed {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, $data);
        return (int) self::getInstance()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $set = [];
        $params = [];
        $paramIndex = 1;
        foreach ($data as $col => $val) {
            $paramName = "set_{$col}_{$paramIndex}";
            $set[] = "{$col} = :{$paramName}";
            $params[$paramName] = $val;
            $paramIndex++;
        }
        // Bind positional parameters for WHERE clause
        $i = 1;
        $whereSql = preg_replace_callback('/\?/', function($match) use (&$i, &$params) {
            $paramName = "where_{$i}";
            $params[$paramName] = $whereParams[$i - 1] ?? null;
            $i++;
            return ":{$paramName}";
        }, $where);
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$whereSql}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int {
        $stmt = self::query("DELETE FROM {$table} WHERE {$where}", $params);
        return $stmt->rowCount();
    }
}
