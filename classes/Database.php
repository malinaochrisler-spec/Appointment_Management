<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

final class Database {
    private static ?PDO $connection = null;
    public static function connection(): PDO {
        if (!self::$connection) {
            try {
                self::$connection = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                exit('Database connection is unavailable. Check config/config.php and ensure MySQL is running.');
            }
        }
        return self::$connection;
    }
}
