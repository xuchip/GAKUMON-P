<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = '127.0.0.1';
    $user = 'root';
    $password = 'admin';
    $database = 'db_gakumon';

    try {
        $connection = new mysqli($host, $user, $password, $database);
    }
    catch (mysqli_sql_exception $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit();
    }

?>
