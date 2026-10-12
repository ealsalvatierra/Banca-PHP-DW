<?php
$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'banca_db';

$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    die('Error de conexión (' . $mysqli->connect_errno . '): ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
