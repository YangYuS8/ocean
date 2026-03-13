<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'test_db');
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: (getenv('MYSQL_ROOT_PASSWORD') ?: 'root');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        self::$connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }

    public static function execSqlFile(string $filePath): void
    {
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new PDOException('Failed to read SQL file: ' . $filePath);
        }

        $statements = preg_split('/;\s*\n/', trim($sql));
        if ($statements === false) {
            throw new PDOException('Failed to parse SQL file: ' . $filePath);
        }

        $pdo = self::connection();

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
        }
    }
}
