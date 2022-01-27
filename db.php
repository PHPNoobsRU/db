<?php

class DB
{
    private $host;
    private $port;
    private $base;
    private $user;
    private $pass;
    private $conn;
    private $link;

    function __construct(string $host, int $port, string $base, string $user, string $pass)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->host = $host;
        $this->port = $port;
        $this->base = $base;
        $this->user = $user;
        $this->pass = $pass;
        $this->conn = false;
    }

    private function connect()
    {
        if ($this->conn === false) {
            try {
                $this->link = new mysqli(
                    $this->host,
                    $this->user,
                    $this->pass,
                    $this->base,
                    $this->port
                );
            } catch (\Throwable) {
                return false;
            }
            if ($this->link->connect_errno === 0) {
                $this->conn = true;
            }
        }
    }

    public function query(string $query): bool|object
    {
        $this->connect();
        if ($this->conn === true) {
            return $this->link->query($query);
        } else {
            return false;
        }
    }

    public function fetch_query($query)
    {
        if (empty($query)) {
            return false;
        }
        if (is_array($query)) {
            $res = [];
            foreach ($query as $k => $q) {
                $result = $this->query($q);
                if ($result === false) {
                    $res[$k] =  false;
                }
                $rows = $result->fetch_assoc();
                $res[$k] = $rows;
            }
            return $res;
        } else {
            $result = $this->query($query);
            if ($result === false) {
                return false;
            }
            $rows = $result->fetch_assoc();
            return $rows;
        }
    }
}


$db = new DB('winhost', 3306, 'phpnoobs', 'phpnoobs', 'phpnoobs');


$sql = <<<SQL
            SELECT unix_timestamp() as time
            SQL;

$result = $db->fetch_query([$sql, $sql]);
if ($result !== false) {
    var_dump($result);
} else {
    die('DB error');
}

// var_dump($db->fetch_query($sql));

// var_dump($db->fetch_query($sql));

// var_dump($db);
