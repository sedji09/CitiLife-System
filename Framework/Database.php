<?php

namespace Framework;

use PDO;
use PDOException;
use Exception;

class Database
{
    public $conn;

    public function __construct($config)
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];

        try {
            $this->conn = new PDO($dsn, $config['username'], $config['password'], $options);
            // Sync with local time zone for logs
            $this->conn->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: {$e->getMessage()}");
        }
    }

    /**
     * Helper to prepare and execute queries directly
     */
    public function query($query, $params = [])
    {
        try {
            $sth = $this->conn->prepare($query);
            foreach ($params as $param => $value) {
                $sth->bindValue(':' . $param, $value);
            }
            $sth->execute();
            return $sth;
        } catch (PDOException $e) {
            throw new Exception("Query failed to execute: {$e->getMessage()}");
        }
    }
}
