<?php
session_start();

// 1. CEK LOGIN
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// 2. KONEKSI DATABASE
require 'config.php'; 

// 3. LOGIKA CRUD (TAMBAH, EDIT, HAPUS)
if (isset($_POST['tambah_barang']) || isset($_POST['edit_barang'])) {
    $game = $_POST['game_select'];
    $nominal = mysqli_real_escape_string($conn, $_POST['nominal_produk']);
    $nama_lengkap = $game . " - " . $nominal;
    $stok = (int)$_POST['stok'];
    $harga = (int)$_POST['harga'];

    if (isset($_POST['tambah_barang'])) {
        mysqli_query($conn, "INSERT INTO products (nama_produk, stok, harga) VALUES ('$nama_lengkap', '$stok', '$harga')");
        $_SESSION['pesan'] = "ditambah";
    } else {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE products SET nama_produk='$nama_lengkap', stok='$stok', harga='$harga' WHERE id='$id'");
        $_SESSION['pesan'] = "diupdate";
    }
    header("Location: inventory.php"); exit();
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM products WHERE id='$id'");
    $_SESSION['pesan'] = "dihapus";
    header("Location: inventory.php"); exit();
}

// 4. AMBIL DATA
$produk_query = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Inventory Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --bg-base: #06090F; --bg-surface: rgba(18, 24, 38, 0.6); --border-color: rgba(255, 255, 255, 0.08); --primary: #22C55E; --text-main: #FFFFFF; --text-muted: #808B9F; --radius-lg: 24px; --radius-md: 14px; --danger: #EF4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; min-height: 100vh; background-image: radial-gradient(circle at 10% 50%, rgba(34, 197, 94, 0.05), transparent 20%); }
        
        /* Sidebar */
        .sidebar { position: fixed; left: -320px; top: 0; bottom: 0; width: 280px; background: rgba(10, 15, 26, 0.95); backdrop-filter: blur(20px); border-right: 1px solid var(--border-color); padding: 30px 20px; display: flex; flex-direction: column; z-index: 1000; transition: 0.3s; }
        .sidebar.show { left: 0; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 999; display: none; }
        .sidebar-overlay.show { display: block; }
        .menu a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-md); font-weight: 500; margin-bottom: 5px; transition: 0.3s; }
        .menu a.active { background: rgba(34, 197, 94, 0.08); color: var(--primary); }

        .main { flex: 1; display: flex; flex-direction: column; width: 100%; overflow-x: hidden; }
        
        /* Topbar Persis Dashboard */
        .topbar { position: sticky; top: 0; z-index: 100; background: rgba(6, 9, 15, 0.85); backdrop-filter: blur(20px); padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); width: 100%; }
        .btn-menu { background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); color: #fff; width: 45px; height: 45px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .clock-box { background: rgba(255,255,255,0.05); padding: 8px 20px; border-radius: 50px; border: 1px solid var(--border-color); color: var(--primary); font-family: monospace; font-size: 16px; font-weight: bold; }
        .sosmed-capsule { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 7px 15px; border-radius: 50px; border: 1px solid var(--border-color); }

        .content { padding: 25px; width: 100%; }
        .box { background: var(--bg-surface); padding: 25px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); }
        
        /* Table Styles */
        .table-container { width: 100%; overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th { padding: 15px; text-align: left; color: var(--text-muted); font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
        td { padding: 18px 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .btn-add { background: var(--primary); color: #000; padding: 12px 20px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; transition: 0.3s; }
        
        /* Modal Style */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #0A0F1A; padding: 30px; border-radius: 24px; width: 100%; max-width: 480px; border: 1px solid var(--border-color); }
        .form-label { display: block; font-size: 11px; color: var(--primary); font-weight: 800; margin-bottom: 8px; text-transform: uppercase; }
        .form-input,/* RESET & FIX DROPDOWN BIAR GAK MENCIUT */
.form-select {
    width: 100% !important; /* Paksa lebar balik normal */
    padding: 14px !important;
    background: rgba(255, 255, 255, 0.03) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 12px !important;
    color: #fff !important;
    appearance: none; /* Hilangin arrow bawaan biar gak aneh */
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2322C55E' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 16px 12px;
}

/* INI KUNCI BIAR PILIHANNYA GAK PUTIH-PUTIH LAGI */
.form-select option {
    background-color: #0A0F1A !important; /* Warna BG Modal */
    color: #FFFFFF !important;           /* Warna Teks */
} }
        
        @media (max-width: 992px) {
            .clock-box { display: none; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="logo" style="display:flex; align-items:center; gap:12px; margin-bottom: 30px;">
            <div style="background:var(--primary); color:#000; padding:10px; border-radius:12px;"><i class="fas fa-bolt"></i></div>
            <h2 style="font-weight:800; font-size:22px;">Suzuka</h2>
        </div>
        <nav class="menu">
            <a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="inventory.php" class="active"><i class="fas fa-box-open"></i> Inventory</a>
            <a href="transactions.php"><i class="fas fa-receipt"></i> Transactions</a>
            <a href="index.php" style="margin-top:auto; color:var(--danger)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main">
        <!-- TOPBAR -->
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div>
                    <h4 style="font-weight:700; margin:0;">Gudang Item</h4>
                    <p style="font-size:11px; color:var(--text-muted); margin:0;">Manajemen Stok & Harga</p>
                </div>
            </div>

            <div class="clock-box" id="realtimeClock">00:00:00</div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="sosmed-capsule">
                    <a href="https://www.facebook.com/share/1AiMvB5THB/" target="_blank" style="color:#1877F2; font-size:18px;"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/zidni_tira?igsh=MWQ4eTdoZGUzdDEzbg==" target="_blank" style="color:#E4405F; font-size:18px;"><i class="fab fa-instagram"></i></a>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=22C55E&color=fff&bold=true" style="width:35px; height:35px; border-radius:50%; border:2px solid var(--primary);">
            </div>
        </header>

        <div class="content">
            <div class="box">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                    <div>
                        <h4 style="font-weight:700;">Master Data Produk</h4>
                        <p style="font-size:12px; color:var(--text-muted);">Total: <?php echo mysqli_num_rows($produk_query); ?> Item</p>
                    </div>
                    <button class="btn-add" onclick="openModal('add')"><i class="fas fa-plus"></i> Tambah Item</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Harga Unit</th>
                                <th>Status Stok</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($produk_query)): ?>
                            <tr>
                                <td><b style="color:#fff;"><?php echo htmlspecialchars($row['nama_produk']); ?></b></td>
                                <td style="color:var(--primary); font-weight:800;">Rp <?php echo number_format($row['harga'],0,',','.'); ?></td>
                                <td>
                                    <?php if($row['stok'] <= 5): ?>
                                        <span style="color:var(--danger); font-size:12px; background:rgba(239, 68, 68, 0.1); padding:4px 10px; border-radius:6px; font-weight:600;">⚡ Limit: <?php echo $row['stok']; ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--primary); font-size:12px; background:rgba(34, 197, 94, 0.1); padding:4px 10px; border-radius:6px;"><?php echo $row['stok']; ?> Tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <button onclick="openModal('edit', '<?php echo $row['id']; ?>', '<?php echo $row['nama_produk']; ?>', '<?php echo $row['stok']; ?>', '<?php echo $row['harga']; ?>')" style="background:none; border:none; color:#3B82F6; cursor:pointer; font-size:16px; margin-right:15px;"><i class="fas fa-edit"></i></button>
                                    <button onclick="confirmHapus(<?php echo $row['id']; ?>)" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:16px;"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL -->
    <div class="modal-overlay" id="crudModal">
        <div class="modal-box">
            <h3 id="modalTitle" style="color:var(--primary); margin-bottom: 20px; font-weight:800;">Tambah Item</h3>
            <form action="" method="POST">
                <input type="hidden" name="id" id="formId">
                
                <label class="form-label">Kategori Game</label>
                <select name="game_select" id="gameSelect" class="form-select">
                    <option value="Genshin Impact">Genshin Impact</option>
                    <option value="Honkai Star Rail">Honkai Star Rail</option>
                    <option value="Uma Musume">Uma Musume</option>
                    <option value="Mobile Legends">Mobile Legends</option>
                    <option value="Free Fire">Free Fire</option>
                    <option value="PUBG Mobile">PUBG Mobile</option>
                    <option value="Valorant">Valorant</option>
                    <option value="Honor of Kings">Honor of Kings</option>
                </select>

                <label class="form-label">Nominal / Nama Paket</label>
                <input type="text" name="nominal_produk" id="formNominal" class="form-input" placeholder="Contoh: 60 Diamonds" required>
                
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" name="harga" id="formHarga" class="form-input" required>
                    </div>
                    <div style="flex:1;">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" id="formStok" class="form-input" required>
                    </div>
                </div>

             <button type="submit" name="tambah_barang" id="btnSubmit" style="width:100%; padding:15px; background:var(--primary); border:none; border-radius:12px; font-weight:800; cursor:pointer; margin-top: 25px;">Simpan Data</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:none; color:var(--text-muted); border:none; margin-top:15px; cursor:pointer; font-size:13px;">Batalkan</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        document.getElementById('menuToggle').onclick = () => { sidebar.classList.add('show'); overlay.classList.add('show'); }
        overlay.onclick = () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }

        // Clock
        function updateClock() {
            document.getElementById('realtimeClock').innerText = new Date().toLocaleTimeString('id-ID', { hour12: false });
        }
        setInterval(updateClock, 1000); updateClock();

        // Modal Logic
        const modal = document.getElementById('crudModal');
        function openModal(mode, id='', nama='', stok='', harga='') {
            modal.classList.add('show');
            if(mode === 'edit') {
                document.getElementById('modalTitle').innerText = "Edit Produk";
                document.getElementById('btnSubmit').name = "edit_barang";
                document.getElementById('formId').value = id;
                document.getElementById('formHarga').value = harga;
                document.getElementById('formStok').value = stok;
                let parts = nama.split(' - ');
                if(parts.length > 1) {
                    document.getElementById('gameSelect').value = parts[0];
                    document.getElementById('formNominal').value = parts[1];
                } else { document.getElementById('formNominal').value = nama; }
            } else {
                document.getElementById('modalTitle').innerText = "Tambah Item Baru";
                document.getElementById('btnSubmit').name = "tambah_barang";
                document.getElementById('formId').value = '';
                document.getElementById('formNominal').value = '';
                document.getElementById('formHarga').value = '';
                document.getElementById('formStok').value = '';
            }
        }
        function closeModal() { modal.classList.remove('show'); }

        // SweetAlert Hapus
        function confirmHapus(id) {
            Swal.fire({
                title: 'Hapus Item?',
                text: "Item ini akan hilang dari daftar jualan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                confirmButtonText: 'Ya, Hapus!',
                background: '#0A0F1A', color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) { window.location.href = '?hapus=' + id; }
            })
        }

        // Notifikasi Sukses
        <?php if(isset($_SESSION['pesan'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data produk telah <?php echo $_SESSION['pesan']; ?>.',
                background: '#0A0F1A', color: '#fff', timer: 2000, showConfirmButton: false
            });
            <?php unset($_SESSION['pesan']); ?>
        <?php endif; ?>
    </script>
</body>
</html>