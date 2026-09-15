<?php
// Mengambil environment variable dari Railway
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: "3306";

// Jika dijalankan di lokal (XAMPP), gunakan fallback
if (!$host) {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "dbl_rental";
}

$koneksi = mysqli_connect($host, $user, $pass, $db, (int)$port);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
