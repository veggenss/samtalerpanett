<?php
namespace Spn\Database;

use mysqli;

class Connection{
    private static ?mysqli $conn = null;

    private static function connect(): void
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        self::$conn = new \mysqli(
            $_ENV['DB_HOST'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            $_ENV['DB_NAME']
        );

        self::$conn->set_charset("utf8mb4");
    }

    public static function get(): mysqli{
        if (!self::$conn){
            self::connect();
        }

        try{
            if(!self::$conn->ping()){
                self::connect();
            }
        }
        catch(\mysqli_sql_exception $e){
            self::connect();
        }

        return self::$conn;
    }
}