<?php
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE') ?: "railway"; // Fallback ke 'railway' jika env kosong
$port = getenv('MYSQLPORT') ?: "3306";

// Fallback jika dijalankan di lokal (XAMPP)
if (!$host) {
    $host = "127.0.0.1";
    $user = "root";
    $pass = "";
    $db   = "dbl_rental";
}

$koneksi = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
