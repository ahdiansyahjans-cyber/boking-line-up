<?php
session_start();
include "koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_pelanggan']));
    $hp = mysqli_real_escape_string($koneksi, trim($_POST['no_hp']));
    $tgl_booking = $_POST['tgl_booking'];
    $jam_booking = $_POST['jam_booking'];
    $items = json_decode($_POST['items'], true);

    header('Content-Type: application/json');

    if (empty($nama) || empty($hp) || empty($tgl_booking) || empty($jam_booking) || empty($items)) {
        echo json_encode(['sukses' => false, 'pesan' => 'Lengkapi semua data!']);
        exit;
    }

    $cek = mysqli_fetch_assoc(mysqli_query($koneksi, 
        "SELECT COUNT(*) AS ada FROM tbl_pesanan 
         WHERE tgl_booking = '$tgl_booking' 
           AND jam_booking = '$jam_booking' 
           AND status_pesanan != 'selesai'"
    ));
    
    if ($cek['ada'] > 0) {
        echo json_encode(['sukses' => false, 'pesan' => 'Maaf, tanggal & jam ini sudah dipesan! Pilih waktu lain.']);
        exit;
    }

    foreach ($items as $item) {
        if (($item['jenis'] ?? '') === 'produk') {
            $nm = $item['nama'];
            $cekStok = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok FROM tbl_produk WHERE nama_produk = '$nm'"));
            if (!$cekStok || $cekStok['stok'] <= 0) {
                echo json_encode(['sukses' => false, 'pesan' => "$nm sudah habis!"]);
                exit;
            }
            mysqli_query($koneksi, "UPDATE tbl_produk SET stok = stok - 1 WHERE nama_produk = '$nm'");
        }
    }

    $total = 0;
    foreach ($items as $i) $total += $i['harga'];

    mysqli_query($koneksi, "INSERT INTO tbl_pesanan 
        (nama_pelanggan, no_hp, total_harga, tgl_booking, jam_booking, status_pesanan) 
        VALUES ('$nama', '$hp', '$total', '$tgl_booking', '$jam_booking', 'baru')");
    
    $id = mysqli_insert_id($koneksi);
    
    foreach ($items as $i) {
        $jenis = $i['jenis'] ?? 'lainnya';
        $nm = mysqli_real_escape_string($koneksi, $i['nama']);
        mysqli_query($koneksi, "INSERT INTO tbl_detail 
            (id_pesanan, jenis, nama_item, harga) VALUES ('$id', '$jenis', '$nm', '".$i['harga']."')");
    }

    echo json_encode(['sukses' => true, 'pesan' => 'Pesanan Berhasil! Terima kasih 🎉']);
    exit;
}

$ps = mysqli_query($koneksi, "SELECT * FROM tbl_ps");
$produk = mysqli_query($koneksi, "SELECT * FROM tbl_produk");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rental PS - Bogor</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
}

body {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    min-height: 100vh;
    padding-top: 80px;
    padding-left: 16px;
    padding-right: 16px;
    padding-bottom: 30px;
}

a { text-decoration: none; }

/* === BAR ATAS === */
.bar-atas {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    z-index: 9999;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.link-admin a {
    color: #ffd700;
    font-size: 15px;
    font-weight: 600;
}

/* === TOMBOL KERANJANG === */
.tombol-keranjang {
    background: #ffd700;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    font-size: 22px;
    cursor: pointer;
    position: fixed;
    top: 12px;
    right: 20px;
    z-index: 99999;
    box-shadow: 0 3px 10px rgba(255,215,0,0.4);
}
.tombol-keranjang:active { transform: scale(0.92); }

.jumlah-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e53e3e;
    color: #fff;
    font-size: 12px;
    font-weight: bold;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* === NOTIFIKASI === */
.notif {
    position: fixed;
    top: 85px;
    left: 50%;
    transform: translateX(-50%);
    background: #28a745;
    color: #fff;
    padding: 13px 25px;
    border-radius: 10px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 99999;
    pointer-events: none;
}
.notif.tampil { opacity: 1; }
.notif.salah { background: #dc3545; }

/* === PANEL KERANJANG === */
.latar-keranjang {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 8888;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}
.latar-keranjang.buka { opacity: 1; visibility: visible; }

.panel-keranjang {
    position: fixed;
    top: 0;
    right: 0;
    width: 360px;
    max-width: 92vw;
    height: 100vh;
    background: #fff;
    border-radius: 15px 0 0 15px;
    box-shadow: -3px 0 20px rgba(0,0,0,0.15);
    transform: translateX(100%);
    transition: transform 0.3s;
    z-index: 9990;
    display: flex;
    flex-direction: column;
}
.panel-keranjang.buka { transform: translateX(0); }

.keranjang-header {
    padding: 20px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.keranjang-header h3 { font-size: 19px; color: #222; }

.tutup-keranjang {
    background: none;
    border: none;
    font-size: 26px;
    color: #999;
    cursor: pointer;
}
.tutup-keranjang:hover { color: #e53e3e; }

.keranjang-isi {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}
.keranjang-kosong {
    text-align: center;
    color: #999;
    padding: 50px 20px;
}

.item-keranjang {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px dashed #ddd;
}
.nama-item { font-weight: 600; color: #222; }
.harga-item { color: #e53e3e; font-weight: bold; }
.hapus-item {
    background: none;
    border: none;
    color: #e53e3e;
    font-weight: bold;
    cursor: pointer;
    padding: 5px 10px;
}

.keranjang-bawah {
    padding: 20px;
    border-top: 2px solid #f0f0f0;
}
.total-keranjang {
    font-size: 21px;
    font-weight: bold;
    color: #e53e3e;
    margin-bottom: 18px;
}

.form-bawah label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #333;
    font-size: 14px;
}
.form-bawah input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
    margin-bottom: 14px;
}

/* === PILIH WAKTU === */
.latar-waktu {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 99990;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.latar-waktu.tampil { display: flex; }

.box-waktu {
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    width: 100%;
    max-width: 330px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}
.box-waktu h3 {
    font-size: 18px;
    color: #222;
    margin-bottom: 18px;
    text-align: center;
}

.form-kecil { margin-bottom: 15px; }
.form-kecil label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #444;
    font-size: 14px;
}
.form-kecil input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
}

.tombol-waktu {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}
.btn-batal {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    background: #eee;
    color: #666;
    cursor: pointer;
    font-size: 15px;
}
.btn-lanjut {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    background: linear-gradient(90deg, #4158d0, #5b7cfa);
    color: #fff;
    cursor: pointer;
    font-size: 15px;
}

/* === JUDUL === */
.judul {
    text-align: center;
    padding: 10px 0 25px;
}
.judul h1 {
    font-size: 24px;
    color: #ffd700;
    margin-bottom: 5px;
}
.judul p { color: #b0b0c0; font-size: 14px; }

/* === KOTAK & KARTU === */
.kotak {
    background: #fff;
    border-radius: 15px;
    padding: 22px;
    margin-bottom: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}
.kotak h2 {
    font-size: 19px;
    color: #222;
    padding-bottom: 12px;
    margin-bottom: 18px;
    border-bottom: 2px solid #f0f0f0;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}

.kartu {
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid transparent;
}
.kartu:hover {
    transform: translateY(-3px);
    border-color: #ffd700;
}
.kartu img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    background: #e9ecef;
}
.kartu-isi { padding: 14px; }
.kartu-isi h3 {
    font-size: 15px;
    color: #222;
    margin-bottom: 8px;
}

.harga {
    font-size: 17px;
    font-weight: bold;
    color: #e53e3e;
    margin-bottom: 12px;
}

.stok {
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-block;
    margin-bottom: 12px;
    font-weight: 500;
}
.stok-ada { background: #d4edda; color: #155724; }
.stok-habis { background: #f8d7da; color: #721c24; }

/* === TOMBOL === */
.btn {
    width: 100%;
    padding: 11px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-pilih {
    background: linear-gradient(90deg, #4158d0, #5b7cfa);
    color: #fff;
}
.btn-pilih:active { transform: scale(0.96); }

.btn-makan {
    background: linear-gradient(90deg, #28a745, #34ce57);
    color: #fff;
}
.btn-makan:active { transform: scale(0.96); }

.btn-habis { background: #dee2e6; color: #868e96; }

.btn-pesan {
    background: linear-gradient(90deg, #e53e3e, #ff6b6b);
    color: #fff;
    font-size: 16px;
    padding: 13px;
}
.btn-pesan:active { transform: scale(0.96); }
</style>
</head>
<body>

<div id="notif" class="notif"></div>

<div class="bar-atas">
    <div class="link-admin"><a href="login.php">🔐 Masuk Admin</a></div>
</div>

<button class="tombol-keranjang" id="btnKeranjang">
    🛒 <span id="jumlahBadge" class="jumlah-badge">0</span>
</button>

<div class="judul">
    <h1>🎮 RENTAL PS LINE UP</h1>
    <p>Pesan & Main — Pilih Waktu Kamu</p>
</div>

<div id="latarWaktu" class="latar-waktu">
    <div class="box-waktu">
        <h3>📅 Pilih Waktu Booking</h3>
        <div class="form-kecil">
            <label>Tanggal Main</label>
            <input type="date" id="tglBooking">
        </div>
        <div class="form-kecil">
            <label>Jam Mulai</label>
            <input type="time" id="jamBooking"> 
        </div>
        <div class="tombol-waktu">
            <button class="btn-batal" id="btnBatalWaktu">Batal</button>
            <button class="btn-lanjut" id="btnLanjutWaktu">Lanjut</button>
        </div>
    </div>
</div>

<div id="latarKeranjang" class="latar-keranjang"></div>
<div id="panelKeranjang" class="panel-keranjang">
    <div class="keranjang-header">
        <h3>🛒 Keranjang</h3>
        <button class="tutup-keranjang" id="btnTutupKeranjang">✕</button>
    </div>
    <div class="keranjang-isi" id="keranjangIsi">
        <div class="keranjang-kosong">Keranjang masih kosong</div>
    </div>
    <div class="keranjang-bawah" id="bagianCheckout" style="display:none;">
        <div class="total-keranjang">Total: Rp <span id="totalKeranjang">0</span></div>
        <div class="form-bawah">
            <label>Nama Kamu</label>
            <input type="text" id="namaPelanggan" placeholder="Masukkan nama...">
            <label>Nomor HP</label>
            <input type="tel" id="noHP" placeholder="Contoh: 0812xxxxxxx">
            <button class="btn btn-pesan" id="btnKonfirmasi">✅ Konfirmasi Pesanan</button>
        </div>
    </div>
</div>

<div class="kotak">
    <h2>🎮 Pilih Konsol</h2>
    <div class="grid" id="daftarPS">
        <?php while($d = mysqli_fetch_assoc($ps)): 
            $gambar = match($d['nama_ps']) {
                'PS 3' => 'Sony-PlayStation-3-2001A-wController-L.jpg',
                'PS 4' => '008226400_1473281854-01sony-ps4.jpg',
                'PS 5' => 'playstation-5-pro_169.jpeg',
                'Tempat VIP' => 'playstation-5-pro_169.jpeg',
                default => 'https://via.placeholder.com/150x115'
            };
        ?>
        <div class="kartu">
            <img src="<?= $gambar ?>" alt="<?= $d['nama_ps'] ?>">
            <div class="kartu-isi">
                <h3><?= $d['nama_ps'] ?></h3>
                <div class="harga">Rp <?= number_format($d['harga_perjam'],0,',','.') ?>/jam</div>
                <button class="btn btn-pilih" data-nama="<?= $d['nama_ps'] ?>" data-harga="<?= $d['harga_perjam'] ?>">Pilih</button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="kotak">
    <h2>🍔 Makanan & Minuman</h2>
    <div class="grid" id="daftarProduk">
        <?php while($p = mysqli_fetch_assoc($produk)): 
            $gambarP = match($p['nama_produk']) {
                'Kentang Goreng' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=300&h=200&fit=crop',
                'Es Teh' => 'Es-teh-tawar-manis.jpg',
                'Kopi' => 'ini-manfaat-konsumsi-kopi-hitam-dan-efek-sampingnya-untuk-kesehatan.avif',
                default => 'https://via.placeholder.com/150x115'
            };
            $stokKelas = $p['stok'] > 0 ? 'stok-ada' : 'stok-habis';
            $btnKelas = $p['stok'] > 0 ? 'btn-makan' : 'btn-habis';
            $disabled = $p['stok'] <= 0 ? 'disabled' : '';
        ?>
        <div class="kartu">
            <img src="<?= $gambarP ?>" alt="<?= $p['nama_produk'] ?>">
            <div class="kartu-isi">
                <h3><?= $p['nama_produk'] ?></h3>
                <div class="harga">Rp <?= number_format($p['harga'],0,',','.') ?></div>
                <span class="stok <?= $stokKelas ?>">Stok: <?= $p['stok'] > 0 ? $p['stok'] : 'HABIS' ?></span>
                <button class="btn <?= $btnKelas ?>" data-nama="<?= $p['nama_produk'] ?>" data-harga="<?= $p['harga'] ?>" <?= $disabled ?>>
                    <?= $p['stok'] > 0 ? '+ Keranjang' : 'Stok Habis' ?>
                </button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
let keranjang = [];
let tglTerpilih = '';
let jamTerpilih = '';
let psDipilih = null;

document.getElementById('tglBooking').min = new Date().toISOString().split('T')[0];

// === PILIH PS ===
document.querySelectorAll('#daftarPS .btn-pilih').forEach(btn => {
    btn.onclick = function() {
        psDipilih = {
            nama: this.dataset.nama,
            harga: parseInt(this.dataset.harga)
        };
        document.getElementById('latarWaktu').classList.add('tampil');
    };
});

// === PILIH MAKANAN ===
document.querySelectorAll('#daftarProduk .btn:not(:disabled)').forEach(btn => {
    btn.onclick = function() {
        tambahKeKeranjang('produk', this.dataset.nama, parseInt(this.dataset.harga));
    };
});

// === KERANJANG BUKA/TUTUP ===
document.getElementById('btnKeranjang').onclick = function() {
    document.getElementById('latarKeranjang').classList.add('buka');
    document.getElementById('panelKeranjang').classList.add('buka');
};
document.getElementById('btnTutupKeranjang').onclick = tutupKeranjang;
document.getElementById('latarKeranjang').onclick = tutupKeranjang;

function tutupKeranjang() {
    document.getElementById('latarKeranjang').classList.remove('buka');
    document.getElementById('panelKeranjang').classList.remove('buka');
}

// === PILIH WAKTU ===
document.getElementById('btnBatalWaktu').onclick = function() {
    document.getElementById('latarWaktu').classList.remove('tampil');
    psDipilih = null;
};
document.getElementById('btnLanjutWaktu').onclick = function() {
    const tgl = document.getElementById('tglBooking').value;
    const jam = document.getElementById('jamBooking').value;
    if (!tgl || !jam) {
        tampilNotif('Pilih tanggal & jam dulu!', true);
        return;
    }
    tglTerpilih = tgl;
    jamTerpilih = jam;
    tambahKeKeranjang('ps', psDipilih.nama, psDipilih.harga);
    document.getElementById('latarWaktu').classList.remove('tampil');
};

// === TAMBAH KERANJANG ===
function tambahKeKeranjang(jenis, nama, harga) {
    keranjang.push({jenis, nama, harga});
    tampilNotif(nama + ' sudah masuk ke keranjang ✅');
    tampilKeranjang();
}

// === TAMPIL KERANJANG ===
function tampilKeranjang() {
    const wadah = document.getElementById('keranjangIsi');
    const badge = document.getElementById('jumlahBadge');
    const totalEl = document.getElementById('totalKeranjang');
    const bagianBawah = document.getElementById('bagianCheckout');
    
    badge.innerText = keranjang.length;
    
    if (keranjang.length === 0) {
        wadah.innerHTML = '<div class="keranjang-kosong">Keranjang masih kosong</div>';
        bagianBawah.style.display = 'none';
        return;
    }
    
    bagianBawah.style.display = 'block';
    let total = 0;
    let html = '';
    
    keranjang.forEach((item, i) => {
        html += `<div class="item-keranjang">
            <span class="nama-item">${item.nama}</span>
            <span class="harga-item">Rp ${item.harga.toLocaleString('id-ID')}
                <button class="hapus-item" data-i="${i}">✕</button>
            </span>
        </div>`;
        total += item.harga;
    });
    
    wadah.innerHTML = html;
    totalEl.innerText = total.toLocaleString('id-ID');
    
    // Hapus item
    document.querySelectorAll('.hapus-item').forEach(btn => {
        btn.onclick = function() {
            keranjang.splice(parseInt(this.dataset.i), 1);
            tampilKeranjang();
        };
    });
}

// === NOTIFIKASI ===
function tampilNotif(pesan, salah = false) {
    const el = document.getElementById('notif');
    el.innerText = pesan;
    el.classList.toggle('salah', salah);
    el.classList.add('tampil');
    setTimeout(() => el.classList.remove('tampil'), 2500);
}

// === KONFIRMASI ===
document.getElementById('btnKonfirmasi').onclick = async function() {
    const nama = document.getElementById('namaPelanggan').value.trim();
    const hp = document.getElementById('noHP').value.trim();
    
    if (!nama || !hp || !tglTerpilih || !jamTerpilih) {
        tampilNotif('Lengkapi Nama, No HP, Tanggal & Jam!', true);
        return;
    }
    if (keranjang.length === 0) {
        tampilNotif('Keranjang masih kosong!', true);
        return;
    }
    
    const res = await fetch('', {
        method: 'POST',
        body: new URLSearchParams({
            checkout: '1',
            nama_pelanggan: nama,
            no_hp: hp,
            tgl_booking: tglTerpilih,
            jam_booking: jamTerpilih,
            items: JSON.stringify(keranjang)
        })
    });
    
    const hasil = await res.json();
    if (!hasil.sukses) {
        tampilNotif(hasil.pesan, true);
        return;
    }
    alert(hasil.pesan);
    location.reload();
};
</script>

</body>
</html>