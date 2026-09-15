<?php
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: "3306";

if (!$host) {
    $host = "127.0.0.1";
    $user = "root";
    $pass = "";
    $db   = "dbl_rental";
}

// Gunakan 127.0.0.1 jika host bernilai localhost agar tidak error socket
if ($host === 'localhost') {
    $host = '127.0.0.1';
}

$koneksi = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
