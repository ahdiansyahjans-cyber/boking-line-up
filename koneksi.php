<?php
$host = getenv('MYSQLHOST') ?: "localhost";
$user = getenv('MYSQLUSER') ?: "root";
$pass = getenv('MYSQLPASSWORD') ?: "";
$db   = getenv('MYSQLDATABASE') ?: "dbl_rental";
$port = getenv('MYSQLPORT') ?: "3306";

$koneksi = mysqli_connect($host, $user, $pass, $db, $port);

if (!$koneksi) {
     die("Koneksi gagal: " . mysqli_connect_error());
}
?>
