<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];
$conn = mysqli_connect("localhost", "root", "", "umamusume_db");

// --- LOGIKA CRUD ---
if (isset($_POST['tambah_barang']) || isset($_POST['edit_barang'])) {
    $game = $_POST['game_select'];
    $nominal = mysqli_real_escape_string($conn, $_POST['nominal_produk']);
    $nama_lengkap = $game . " - " . $nominal;
    $stok = (int)$_POST['stok'];
    $harga = (int)$_POST['harga'];

    if (isset($_POST['tambah_barang'])) {
        mysqli_query($conn, "INSERT INTO products (nama_produk, stok, harga) VALUES ('$nama_lengkap', '$stok', '$harga')");
        $_SESSION['pesan'] = "Produk berhasil ditambah!";
    } else {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE products SET nama_produk='$nama_lengkap', stok='$stok', harga='$harga' WHERE id='$id'");
        $_SESSION['pesan'] = "Produk berhasil diupdate!";
    }
    header("Location: inventory.php"); exit();
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM products WHERE id='$id'");
    header("Location: inventory.php"); exit();
}

$produk_query = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Suzuka Store | Inventory Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --bg-base: #06090F; 
            --bg-surface: rgba(18, 24, 38, 0.7); 
            --border-color: rgba(255, 255, 255, 0.1); 
            --primary: #22C55E; 
            --danger: #EF4444; 
            --text-main: #FFFFFF; 
            --text-muted: #808B9F; 
            --radius-md: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }

        /* SIDEBAR */
        .sidebar { width: 280px; background: rgba(10, 15, 26, 0.95); backdrop-filter: blur(20px); border-right: 1px solid var(--border-color); padding: 30px 20px; display: flex; flex-direction: column; }
        .logo { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; }
        .menu { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .menu a { display: flex; align-items: center; gap: 15px; padding: 14px 18px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-md); transition: 0.3s; font-size: 14px; font-weight: 500; }
        .menu a.active, .menu a:hover { background: rgba(34, 197, 94, 0.1); color: var(--primary); }
        .logout-item { color: var(--danger) !important; }
        .logout-item:hover { background: rgba(239, 68, 68, 0.1) !important; }

        /* TOPBAR & CLOCK */
        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { padding: 15px 40px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(6, 9, 15, 0.5); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        
        .clock-box { 
            background: rgba(255,255,255,0.05); 
            padding: 8px 20px; 
            border-radius: 50px; 
            border: 1px solid var(--border-color); 
            color: var(--primary); 
            font-family: monospace; 
            font-size: 16px; 
            font-weight: bold; 
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.1);
        }

        .user-capsule { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.05); padding: 5px 15px 5px 5px; border-radius: 50px; border: 1px solid var(--border-color); }
        .user-capsule img { width: 35px; height: 35px; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; }

        /* CONTENT */
        .content { padding: 40px; }
        .card { background: var(--bg-surface); padding: 30px; border-radius: 20px; border: 1px solid var(--border-color); }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 11px; border-bottom: 1px solid var(--border-color); text-transform: uppercase; }
        td { padding: 18px 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .btn-add { background: var(--primary); color: #000; padding: 12px 20px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; transition: 0.3s; }
        
        /* MODAL */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 1000; display: none; align-items: center; justify-content: center; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #0A0F1A; padding: 35px; border-radius: 24px; width: 480px; border: 1px solid var(--border-color); }
        .modal-box h3 { color: var(--primary); margin-bottom: 25px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .form-label { display: block; font-size: 12px; color: var(--primary); font-weight: 700; margin-bottom: 8px; text-transform: uppercase; }
        .form-input, .form-select { width: 100%; padding: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 12px; color: #fff; margin-bottom: 20px; outline: none; }
        .form-select option { background-color: #0A0F1A; color: #fff; }
        .btn-save { width: 100%; padding: 16px; background: var(--primary); border: none; border-radius: 12px; font-weight: 800; cursor: pointer; color: #000; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <div style="background:var(--primary); color:#000; width:35px; height:35px; border-radius:8px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-bolt"></i></div> 
            <h2 style="font-size:22px; font-weight:800;">Suzuka</h2>
        </div>
        <nav class="menu">
            <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="inventory.php" class="active"><i class="fas fa-box"></i> Inventory</a>
            <a href="transactions.php"><i class="fas fa-history"></i> Transactions</a>
            <a href="index.php" class="logout-item" onclick="return confirm('Logout sekarang?')"><i class="fas fa-sign-out-alt"></i> Logout Session</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <i class="fas fa-warehouse" style="color:var(--primary); font-size:20px;"></i>
                <h3 style="font-weight:700;">Gudang Persediaan</h3>
            </div>
            
            <!-- JAM REAL-TIME SINKRON -->
            <div class="clock-box" id="realtimeClock">00:00:00</div>

            <div class="user-capsule">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=22C55E&color=fff&bold=true" alt="User">
                <span style="font-weight:600; font-size:13px;"><?php echo $username; ?></span>
            </div>
        </header>

        <div class="content">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h4 style="font-size:18px;">Master Data Item</h4>
                        <p style="font-size:12px; color:var(--text-muted);">Manage your game diamonds & stock</p>
                    </div>
                    <button class="btn-add" onclick="openModal('add')"><i class="fas fa-plus"></i> Tambah Item Baru</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Nama Item</th>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($produk_query)): ?>
                        <tr>
                            <td><b style="color:#fff;"><?php echo $row['nama_produk']; ?></b></td>
                            <td style="color:var(--primary); font-weight:700;">Rp <?php echo number_format($row['harga'],0,',','.'); ?></td>
                            <td><span style="background:rgba(255,255,255,0.05); padding:4px 12px; border-radius:6px; font-size:13px;"><?php echo $row['stok']; ?> Pcs</span></td>
                            <td style="text-align:center;">
                                <button onclick="openModal('edit', '<?php echo $row['id']; ?>', '<?php echo $row['nama_produk']; ?>', '<?php echo $row['stok']; ?>', '<?php echo $row['harga']; ?>')" style="background:none; border:none; color:#3B82F6; cursor:pointer; font-size:16px; margin-right:15px;"><i class="fas fa-edit"></i></button>
                                <a href="?hapus=<?php echo $row['id']; ?>" style="color:var(--danger); font-size:16px;" onclick="return confirm('Hapus data ini?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- MODAL -->
    <div class="modal-overlay" id="crudModal">
        <div class="modal-box">
            <h3 id="modalTitle">Tambah Produk Baru</h3>
            <form action="" method="POST">
                <input type="hidden" name="id" id="formId">
                <label class="form-label">1. Kategori Game</label>
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
                <label class="form-label">2. Nominal Produk</label>
                <input type="text" name="nominal_produk" id="formNominal" class="form-input" placeholder="Misal: 50 Diamonds" required>
                <div style="display:flex; gap:20px;">
                    <div style="flex:1;"><label class="form-label">Harga (IDR)</label><input type="number" name="harga" id="formHarga" class="form-input" required></div>
                    <div style="flex:1;"><label class="form-label">Stok Unit</label><input type="number" name="stok" id="formStok" class="form-input" required></div>
                </div>
                <button type="submit" id="btnSubmit" name="tambah_barang" class="btn-save">Simpan Data Produk</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:none; color:var(--text-muted); border:none; margin-top:15px; cursor:pointer;">Batal</button>
            </form>
        </div>
    </div>

    <script>
        // CLOCK LOGIC
        function updateClock() {
            const now = new Date();
            document.getElementById('realtimeClock').innerText = now.toLocaleTimeString('id-ID');
        }
        setInterval(updateClock, 1000);
        updateClock();

        // MODAL LOGIC
        const modal = document.getElementById('crudModal');
        function openModal(mode, id='', nama='', stok='', harga='') {
            modal.classList.add('show');
            if(mode === 'edit') {
                document.getElementById('modalTitle').innerText = "Edit Data Produk";
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
                document.getElementById('modalTitle').innerText = "Tambah Produk Baru";
                document.getElementById('btnSubmit').name = "tambah_barang";
                document.getElementById('formNominal').value = '';
                document.getElementById('formHarga').value = '';
                document.getElementById('formStok').value = '';
            }
        }
        function closeModal() { modal.classList.remove('show'); }
        window.onclick = function(event) { if (event.target == modal) closeModal(); }
    </script>
</body>
</html>