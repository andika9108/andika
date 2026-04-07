<?php
session_start();

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// Koneksi Database
$conn = mysqli_connect("localhost", "root", "", "umamusume_db");
if (!$conn) { die("Koneksi gagal!"); }

$pesan = "";

// ==========================================
// LOGIKA CRUD INVENTORY (EDIT & HAPUS SAJA)
// ==========================================

// 1. UPDATE (Edit Barang)
if (isset($_POST['edit_barang'])) {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $stok = (int)$_POST['stok'];
    $harga = (int)$_POST['harga'];

    $query = "UPDATE products SET nama_produk='$nama', stok='$stok', harga='$harga' WHERE id='$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['pesan'] = "<div class='toast success'><i class='fas fa-edit'></i> <div><b>Diperbarui!</b><br>Data item berhasil diubah.</div></div>";
    }
    header("Location: inventory.php"); exit();
}

// 2. DELETE (Hapus Barang)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM products WHERE id='$id'");
    $_SESSION['pesan'] = "<div class='toast success'><i class='fas fa-trash'></i> <div><b>Terhapus!</b><br>Item telah dihapus dari sistem.</div></div>";
    header("Location: inventory.php"); exit();
}

// Tangkap pesan dari session
if (isset($_SESSION['pesan'])) {
    $pesan = $_SESSION['pesan'];
    unset($_SESSION['pesan']); 
}

// 3. READ (Ambil Data untuk Ditampilkan)
$produk_query = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Inventory Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === CSS TEMA DASAR === */
        :root { --bg-base: #06090F; --bg-surface: rgba(18, 24, 38, 0.6); --border-color: rgba(255, 255, 255, 0.08); --primary: #22C55E; --primary-glow: rgba(34, 197, 94, 0.25); --accent: #ff7a00; --text-main: #FFFFFF; --text-muted: #808B9F; --danger: #EF4444; --radius-lg: 24px; --radius-md: 14px; --blur: blur(20px); --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; background-image: radial-gradient(circle at 10% 50%, rgba(34, 197, 94, 0.05), transparent 20%), radial-gradient(circle at 90% 30%, rgba(255, 122, 0, 0.03), transparent 20%); }
        ::-webkit-scrollbar { width: 6px; height: 6px;} ::-webkit-scrollbar-track { background: transparent; } ::-webkit-scrollbar-thumb { background: #1C2433; border-radius: 10px; }

        /* Sidebar & Topbar */
        .sidebar { position: fixed; left: -320px; top: 0; bottom: 0; width: 300px; background: rgba(10, 15, 26, 0.9); backdrop-filter: var(--blur); border-right: 1px solid var(--border-color); padding: 30px 20px; display: flex; flex-direction: column; z-index: 1000; transition: var(--transition); }
        .sidebar.show { left: 0; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 999; opacity: 0; visibility: hidden; transition: 0.4s ease; }
        .sidebar-overlay.show { opacity: 1; visibility: visible; cursor: pointer; }
        .logo { display: flex; align-items: center; gap: 15px; margin-bottom: 35px; padding: 0 10px; }
        .logo-icon { width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: var(--radius-md); display: flex; justify-content: center; align-items: center; font-size: 20px; color: white; }
        .logo-text h2 { font-size: 22px; font-weight: 700; color: var(--text-main); line-height: 1.1; }
        .logo-text span { font-size: 11px; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; }

        .menu { flex: 1; display: flex; flex-direction: column; gap: 7px; }
        .menu a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-md); font-weight: 500; font-size: 14px; transition: 0.3s; }
        .menu a i { font-size: 18px; width: 24px; text-align: center; }
        .menu a:hover, .menu a.active { background: rgba(34, 197, 94, 0.08); color: var(--primary); box-shadow: inset 3px 0 0 var(--primary); }
        .menu a.logout { margin-top: auto; color: var(--danger); font-weight: 600;}

        /* Gambar Sidebar */
        .sidebar-img { margin-top: 15px; height: 170px; border-radius: var(--radius-md); background: url('https://i.pinimg.com/736x/cb/ac/b4/cbacb415a97d75a61e721d6076b6680a.jpg') center top/cover; border: 1px solid var(--border-color); position: relative; }
        .sidebar-img::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 50%; background: linear-gradient(to top, rgba(10, 15, 26, 0.85), transparent); }

        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; width: 100%; }
        .topbar { position: sticky; top: 0; z-index: 5; background: rgba(6, 9, 15, 0.85); backdrop-filter: var(--blur); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .topbar-left { display: flex; align-items: center; gap: 20px; }
        .btn-menu { background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); color: var(--text-main); font-size: 20px; width: 45px; height: 45px; border-radius: 12px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
        .btn-menu:hover { background: var(--primary); color: #000; }
        .topbar-title h3 { font-size: 18px; font-weight: 600; }
        .topbar-title p { font-size: 13px; color: var(--text-muted); }
        .user-profile { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 7px 18px 7px 7px; border-radius: 50px; border: 1px solid var(--border-color); }
        .user-profile img { width: 34px; height: 34px; border-radius: 50%; }
        .user-profile span { font-size: 13px; font-weight: 600; }

        .content { padding: 40px; max-width: 1400px; margin: 0 auto; width: 100%; }

        /* Box & Table INVENTORY */
        .box { background: var(--bg-surface); backdrop-filter: var(--blur); padding: 35px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .box-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; }
        .header-title { display: flex; align-items: center; gap: 15px; }
        .header-title i { color: #3B82F6; font-size: 18px; padding: 12px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.1); }
        .header-title h3 { font-size: 18px; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 18px 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 13px; }
        th { color: var(--text-muted); font-weight: 500; font-size: 11px; text-transform: uppercase; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .badge { padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 600; background: rgba(34, 197, 94, 0.1); color: var(--primary); border: 1px solid rgba(34, 197, 94, 0.2); }
        
        .action-btns { display: flex; gap: 10px; }
        .btn-edit, .btn-del { width: 35px; height: 35px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; color: white; text-decoration: none;}
        .btn-edit { background: rgba(59, 130, 246, 0.2); color: #3B82F6; }
        .btn-edit:hover { background: #3B82F6; color: white; }
        .btn-del { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
        .btn-del:hover { background: var(--danger); color: white; }

        /* MODAL POPUP */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1001; display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
        .modal-overlay.show { display: flex; opacity: 1; }
        .modal-box { background: var(--bg-base); border: 1px solid var(--border-color); border-radius: var(--radius-lg); width: 100%; max-width: 450px; padding: 30px; transform: translateY(-20px); transition: 0.3s; box-shadow: 0 20px 60px rgba(0,0,0,0.8); }
        .modal-overlay.show .modal-box { transform: translateY(0); }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .modal-header h3 { font-size: 18px; font-weight: 600; }
        .close-modal { background: none; border: none; color: var(--text-muted); font-size: 20px; cursor: pointer; transition: 0.3s; }
        .close-modal:hover { color: var(--danger); }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .form-group input { width: 100%; padding: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); outline: none; font-size: 14px; }
        .form-group input:focus { border-color: var(--primary); }
        
        .btn-submit { width: 100%; background: var(--primary); color: #000; padding: 15px; border: none; border-radius: var(--radius-md); font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 10px;}
        .btn-submit:hover { box-shadow: 0 0 20px var(--primary-glow); }

        /* Toast */
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 9999; }
        .toast { display: flex; align-items: center; gap: 18px; padding: 18px 26px; border-radius: var(--radius-md); background: var(--bg-surface); backdrop-filter: var(--blur); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 15px 50px rgba(0,0,0,0.6); color: white; font-size: 13px; animation: slideInRight 0.5s forwards, fadeOut 0.5s ease 4.5s forwards; }
        .toast.success i { color: var(--primary); font-size: 26px; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if($pesan != "") echo "<div class='toast-container'>$pesan</div>"; ?>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="logo"><div class="logo-icon"><i class="fas fa-bolt"></i></div><div class="logo-text"><h2>Suzuka</h2><span>Premium Store</span></div></div>
        <nav class="menu">
            <a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="inventory.php" class="active"><i class="fas fa-box-open"></i> Inventory</a>
            <a href="#"><i class="fas fa-receipt"></i> Transactions</a>
            <a href="#"><i class="fas fa-cog"></i> Settings</a>
            <div class="sidebar-img"></div>
            <a href="index.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout Session</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left"><button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button><div class="topbar-title"><h3>Inventory Stock</h3><p>Manage digital products and pricing.</p></div></div>
            <div class="user-profile"><img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=22C55E&color=fff&bold=true" alt="User"><span>Trainer <?php echo htmlspecialchars($username); ?></span></div>
        </header>

        <div class="content">
            <div class="box">
                <div class="box-header">
                    <div class="header-title">
                        <i class="fas fa-database"></i>
                        <h3>Data Master Produk</h3>
                    </div>
                </div>
                
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Produk Item</th>
                                <th>Harga Jual</th>
                                <th>Stok Sisa</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($produk_query)): ?>
                            <tr>
                                <td style="color: var(--text-muted);">#<?php echo $row['id']; ?></td>
                                <td style="font-weight: 500; color: #fff;"><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                                <td style="font-weight: 600;">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td><span class="badge"><?php echo $row['stok']; ?> Pcs</span></td>
                                <td>
                                    <div class="action-btns">
                                        <!-- Tombol Edit mengirim data row ke Javascript Modal -->
                                        <button class="btn-edit" title="Edit Item" onclick="openModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars(addslashes($row['nama_produk'])); ?>', '<?php echo $row['stok']; ?>', '<?php echo $row['harga']; ?>')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <!-- Tombol Delete -->
                                        <a href="?hapus=<?php echo $row['id']; ?>" class="btn-del" title="Hapus Item" onclick="return confirm('Yakin mau hapus item <?php echo htmlspecialchars(addslashes($row['nama_produk'])); ?>?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL EDIT SAJA -->
    <div class="modal-overlay" id="crudModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modalTitle">Edit Item</h3>
                <button class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <form action="" method="POST" id="modalForm">
                <input type="hidden" name="id" id="formId">
                <div class="form-group">
                    <label>Nama Game & Produk</label>
                    <input type="text" name="nama_produk" id="formNama" required>
                </div>
                <div class="form-group">
                    <label>Harga Jual (Rp)</label>
                    <input type="number" name="harga" id="formHarga" required>
                </div>
                <div class="form-group">
                    <label>Jumlah Stok</label>
                    <input type="number" name="stok" id="formStok" required>
                </div>
                <button type="submit" name="edit_barang" class="btn-submit"><i class='fas fa-sync'></i> Update Data</button>
            </form>
        </div>
    </div>

    <script>
        // Logic Sidebar (Mobile)
        const btnMenu = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        btnMenu.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });

        // Logic Modal CRUD
        const modal = document.getElementById('crudModal');
        const fId = document.getElementById('formId');
        const fNama = document.getElementById('formNama');
        const fStok = document.getElementById('formStok');
        const fHarga = document.getElementById('formHarga');

        // Fungsi openModal sekarang fokus untuk Edit saja
        function openModal(id, nama, stok, harga) {
            modal.classList.add('show');
            fId.value = id; 
            fNama.value = nama; 
            fStok.value = stok; 
            fHarga.value = harga;
        }

        function closeModal() {
            modal.classList.remove('show');
        }
    </script>
</body>
</html>