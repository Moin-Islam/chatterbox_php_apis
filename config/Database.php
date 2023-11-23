<?php

class Database
{
    private $host = 'localhost';
    private $db_name = 'chatterbox';
    private $user = 'root';
    private $password = '';
    private $pdo;

    public function getConnection(){
        $this->pdo = null;

        try {
            $this->pdo = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->user, $this->password);
            $this->pdo->exec("set names utf8");
        } catch (PDOException $e) {
            echo "connection error: " . $e->getMessage();
            print_r("connection failed");
        }
        return $this->pdo;
    }
}