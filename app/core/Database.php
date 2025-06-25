<?php

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $password = DB_PASS; 
    private $dbname = DB_NAME; 
    private $dbport = DB_PORT;

    private $handler;
    private $statement;
    private $error;

    public function __construct(){
        $dsn = 'mysql:host='.$this->host.';dbname='.$this->dbname.';port='.$this->dbport;

        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        try {
            $this->handler = new PDO($dsn, $this->user, $this->password, $options);
        } catch (PDOException $e){
            $this->error = $e->getMessage();
            echo $this->error;
        }
    }

    public function query($sql){
        $this->statement = $this->handler->prepare($sql);
    }

    public function execute(){
        return $this->statement->execute();
    }

    public function results(){
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function result(){
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    public function bind($param, $value){
        $this->statement->bindValue($param, $value);
    }
}

?>