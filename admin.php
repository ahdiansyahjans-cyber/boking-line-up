<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}
include "koneksi.php";

if (isset($_GET['keluar'])) {
  session_destroy();
  header("Location: login.php");
  exit;
}

if (isset($_GET['status'])) {
  $id = $_GET['id'];
  $status = $_GET['status'];
  mysqli_query($koneksi, "UPDATE tbl_pesanan SET status_pesanan = '$status' WHERE id_pesanan = '$id'");
  header("Location: admin.php");
  exit;
}

if (isset($_POST['simpan_stok'])) {
  $id = $_POST['id_produk'];
  $stok = $_POST['stok'];
  mysqli_query($koneksi, "UPDATE tbl_produk SET stok = '$stok' WHERE id_produk = '$id'");
  header("Location: admin.php");
  exit;
}

if (isset($_GET['hapus'])) {
  $id = $_GET['hapus'];
  mysqli_query($koneksi, "DELETE FROM tbl_detail WHERE id_pesanan = '$id'");
  mysqli_query($koneksi, "DELETE FROM tbl_pesanan WHERE id_pesanan = '$id'");
  header("Location: admin.php");
  exit;
}

$pesanan = mysqli_query($koneksi, "SELECT * FROM tbl_pesanan ORDER BY tgl_booking DESC, jam_booking ASC");
$produk = mysqli_query($koneksi, "SELECT * FROM tbl_produk");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - Rental PS Bogor</title>
<style>
/* ———— DASAR ——— */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Roboto, Arial, sans-serif;
}

body {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  min-height: 100vh;
  padding: 16px;
}

a { text-decoration: none; }

/* ———— HEADER ——— */
.atas {
  background: linear-gradient(90deg, #ffd700, #ffed4e);
  color: #222;
  padding: 20px;
  border-radius: 14px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}
.atas h1 { font-size: 20px; }
.atas p { font-size: 13px; color: #555; margin-top: 4px; }
.keluar {
  background: #e63946;
  color: #fff;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: 0.2s;
}
.keluar:hover { background: #d63031; }

/* ———— KOTAK ——— */
.kotak {
  background: #fff;
  border-radius: 14px;
  padding: 22px;
  margin-bottom: 20px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
h2 {
  font-size: 17px;
  margin-bottom: 16px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f0f0f0;
  color: #222;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ———— TABEL ——— */
.tabel {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.tabel th,
.tabel td {
  padding: 12px 8px;
  text-align: left;
  border-bottom: 1px solid #eee;
  vertical-align: middle;
}
.tabel th {
  background: #f8f9fa;
  font-weight: 700;
  color: #333;
}
.tabel tr:hover {
  background: #fafafa;
}

/* ———— TOMBOL ——— */
.btn {
  padding: 7px 10px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: inline-block;
  font-size: 12px;
  font-weight: 700;
  margin: 2px;
  transition: 0.2s;
}
.btn-detail { background: #6f42c1; color: #fff; }
.btn-detail:hover { background: #5a32a3; }
.btn-ok { background: #28a745; color: #fff; }
.btn-ok:hover { background: #218838; }
.btn-tutup { background: #17a2b8; color: #fff; }
.btn-tutup:hover { background: #138496; }
.btn-hapus { background: #dc3545; color: #fff; }
.btn-hapus:hover { background: #c82333; }
.btn-stok { background: #4158d0; color: #fff; }
.btn-stok:hover { background: #3548b5; }

/* ———— STATUS ——— */
.belum { color: #ffc107; font-weight: 700; }
.sudah { color: #28a745; font-weight: 700; }
.selesai { color: #6c757d; font-weight: 700; }
.waktu { font-weight: 700; color: #4158d0; }

/* ———— FORM ——— */
input {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 6px;
  width: 80px;
  text-align: center;
  transition: 0.2s;
}
input:focus {
  outline: none;
  border-color: #4158d0;
}

.kosong {
  text-align: center;
  color: #999;
  padding: 30px;
}

/* ———— QR ——— */
.qr-kotak {
  text-align: center;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 12px;
}
.qr-kotak img {
  max-width: 150px;
  margin: 10px auto;
  display: block;
}
.qr-kotak p {
  font-size: 13px;
  color: #666;
  margin-top: 5px;
}

/* ———— POPUP ——— */
.popup-latar {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  justify-content: center;
  align-items: flex-start;
  z-index: 9999;
  padding: 20px;
  overflow-y: auto;
}
.popup-isi {
  background: #fff;
  border-radius: 16px;
  padding: 25px;
  max-width: 400px;
  width: 100%;
  margin-top: 40px;
}
.popup-isi h3 {
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 2px solid #eee;
}
.tutup-popup {
  margin-top: 15px;
  width: 100%;
  padding: 10px;
  background: #e63946;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}
.tutup-popup:hover { background: #d63031; }
</style>
</head>
<body>

<div class="atas">
  <div>
    <h1>🔧 DASHBOARD ADMIN</h1>
    <p>Rental PS Bogor — Kelola Jadwal & Pesanan</p>
  </div>
  <a href="admin.php?keluar=1" class="keluar" onclick="return confirm('Yakin ingin keluar?')">🚪 Keluar</a>
</div>

<div class="kotak">
  <h2>📱 QR Code Pesanan</h2>
  <div class="qr-kotak">
    <p>Scan untuk Pesanan Lewat HP</p>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=http://localhost/rentalps" alt="QR Code">
    <p>Buka: <strong>localhost/rentalps</strong></p>
  </div>
</div>

<div class="kotak">
  <h2>📅 Jadwal & Pesanan Masuk</h2>
  <table class="tabel">
    <tr>
      <th>No</th>
      <th>Nama / HP</th>
      <th>Tanggal Booking</th>
      <th>Jam</th>
      <th>Total</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
    <?php $no=1; while($p = mysqli_fetch_assoc($pesanan)): 
      $id = $p['id_pesanan'];
      $status = $p['status_pesanan'];
      if ($status == 'baru') $kelas = 'belum';
      elseif ($status == 'dikonfirmasi') $kelas = 'sudah';
      elseif ($status == 'selesai') $kelas = 'selesai';
      
      $detail = mysqli_query($koneksi, "SELECT * FROM tbl_detail WHERE id_pesanan = '$id'");
      $daftar = [];
      while ($d = mysqli_fetch_assoc($detail)) $daftar[] = $d;
      
      $tgl = $p['tgl_booking'] ? date('d/m/Y', strtotime($p['tgl_booking'])) : '-';
      $jam = $p['jam_booking'] ? date('H:i', strtotime($p['jam_booking'])) : '-';
    ?>
    <tr>
      <td><?= $no++ ?></td>
      <td>
        <strong><?= $p['nama_pelanggan'] ?: 'Anonim' ?></strong><br>
        <small><?= $p['no_hp'] ?: '-' ?></small>
      </td>
      <td class="waktu"><?= $tgl ?></td>
      <td class="waktu"><?= $jam ?></td>
      <td style="font-weight:700; color:#e63946;">Rp <?= number_format($p['total_harga'],0,',','.') ?></td>
      <td><span class="<?= $kelas ?>"><?= ucfirst($status) ?></span></td>
      <td>
        <button class="btn btn-detail" onclick="lihatDetail('<?= addslashes($p['nama_pelanggan'] ?: 'Anonim') ?>', '<?= $p['no_hp'] ?>', '<?= $tgl ?>', '<?= $jam ?>', <?= $p['total_harga'] ?>, <?= json_encode($daftar) ?>)">📋 Detail</button>
        <?php if ($status == 'baru'): ?>
        <a href="admin.php?status=dikonfirmasi&id=<?= $id ?>" class="btn btn-ok" onclick="return confirm('Konfirmasi pesanan ini?')">✅ Konfirmasi</a>
        <?php elseif ($status == 'dikonfirmasi'): ?>
        <a href="admin.php?status=selesai&id=<?= $id ?>" class="btn btn-tutup" onclick="return confirm('Tandai sudah selesai?')">✓ Selesai</a>
        <?php endif; ?>
        <a href="admin.php?hapus=<?= $id ?>" class="btn btn-hapus" onclick="return confirm('Yakin hapus?')">🗑️</a>
      </td>
    </tr>
    <?php endwhile; if(mysqli_num_rows($pesanan) == 0): ?>
    <tr><td colspan="7" class="kosong">Belum ada pesanan masuk 📭</td></tr>
    <?php endif; ?>
  </table>
</div>

<div class="kotak">
  <h2>📦 Kelola Stok Makanan & Minuman</h2>
  <table class="tabel">
    <tr>
      <th>Nama Produk</th>
      <th>Harga</th>
      <th>Stok</th>
      <th>Ubah</th>
    </tr>
    <?php while($pr = mysqli_fetch_assoc($produk)): ?>
    <tr>
      <td><strong><?= $pr['nama_produk'] ?></strong></td>
      <td>Rp <?= number_format($pr['harga'],0,',','.') ?></td>
      <td style="font-weight:700; color:<?= $pr['stok'] == 0 ? '#dc3545' : '#28a745' ?>;">
        <?= $pr['stok'] == 0 ? 'HABIS' : $pr['stok'] ?>
      </td>
      <td>
        <form method="POST" style="display:flex; gap:5px; align-items:center;">
          <input type="hidden" name="id_produk" value="<?= $pr['id_produk'] ?>">
          <input type="number" name="stok" value="<?= $pr['stok'] ?>" min="0" required>
          <button type="submit" name="simpan_stok" class="btn btn-stok">Simpan</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

<div class="popup-latar" id="popupDetail">
  <div class="popup-isi">
    <h3>📋 Detail Pesanan</h3>
    <div id="isiDetail"></div>
    <button class="tutup-popup" onclick="tutupDetail()">Tutup</button>
  </div>
</div>

<script>
function lihatDetail(nama, hp, tgl, jam, total, daftar) {
  let html = `<p><strong>Nama:</strong> ${nama}</p>`;
  html += `<p><strong>No HP:</strong> ${hp || '-'}</p>`;
  html += `<p><strong>Tanggal Booking:</strong> ${tgl}</p>`;
  html += `<p><strong>Jam Mulai:</strong> ${jam}</p>`;
  html += `<p style="margin-top:12px;"><strong>Daftar Pesanan:</strong></p>`;
  daftar.forEach(item => {
    html += `<div style="padding:6px 0; border-bottom:1px dashed #eee;">
      • ${item.nama_item}<br>
      <small>Rp ${parseInt(item.harga).toLocaleString('id-ID')}</small>
    </div>`;
  });
  html += `<p style="margin-top:12px; font-weight:700; font-size:16px; color:#e63946;">Total: Rp ${parseInt(total).toLocaleString('id-ID')}</p>`;
  
  document.getElementById('isiDetail').innerHTML = html;
  document.getElementById('popupDetail').style.display = 'flex';
}

function tutupDetail() {
  document.getElementById('popupDetail').style.display = 'none';
}
</script>

</body>
</html>