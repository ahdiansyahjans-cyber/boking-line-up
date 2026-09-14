<?php
session_start();
include "koneksi.php";

if (isset($_POST['masuk'])) {
  $user = $_POST['username'];
  $pass = $_POST['password'];

  $cek = mysqli_query($koneksi, "SELECT * FROM tbl_admin WHERE username='$user' AND password='$pass'");
  if (mysqli_num_rows($cek) > 0) {
    $_SESSION['admin'] = true;
    header("Location: admin.php");
    exit;
  } else {
    $pesan = "Username atau password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin - Rental PS</title>
<style>
* {margin:0; padding:0; box-sizing:border-box; font-family:sans-serif;}
body {
  background: linear-gradient(135deg, #1a1a2e, #16213e);
  min-height:100vh;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:20px;
}
.kotak {
  background:white;
  border-radius:16px;
  padding:30px;
  width:100%;
  max-width:400px;
  box-shadow:0 8px 24px rgba(0,0,0,0.3);
}
h2 {text-align:center; color:#222; margin-bottom:25px;}
.pesan {background:#ffe6e6; color:red; padding:10px; border-radius:8px; margin-bottom:15px; text-align:center;}
.form-grup {margin-bottom:15px;}
label {display:block; margin-bottom:6px; font-weight:bold; color:#444;}
input {
  width:100%;
  padding:12px;
  border:2px solid #ddd;
  border-radius:8px;
  font-size:15px;
}
input:focus {outline:none; border-color:#4158d0;}
button {
  width:100%;
  padding:12px;
  background:linear-gradient(90deg, #4158d0, #5b7cfa);
  color:white;
  border:none;
  border-radius:8px;
  font-size:16px;
  font-weight:bold;
  cursor:pointer;
  margin-top:10px;
}
button:hover {opacity:0.9;}
.kembali {display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none; font-size:14px;}
</style>
</head>
<body>
<div class="kotak">
  <h2>🔐 LOGIN ADMIN</h2>
  <?php if (isset($pesan)): ?>
  <div class="pesan"><?= $pesan ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-grup">
      <label>Username</label>
      <input type="text" name="username" required placeholder="Masukkan username">
    </div>
    <div class="form-grup">
      <label>Password</label>
      <input type="password" name="password" required placeholder="Masukkan password">
    </div>
    <button type="submit" name="masuk">MASUK</button>
  </form>
  <a href="index.php" class="kembali">← Kembali ke Halaman Utama</a>
</div>
</body>
</html>