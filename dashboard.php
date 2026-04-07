<?php
session_start();

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// 1. KONEKSI DATABASE
$conn = mysqli_connect("localhost", "root", "", "umamusume_db");
if (!$conn) { die("Koneksi gagal!"); }

// 2. LOGIKA PROSES BELI
if (isset($_POST['beli_barang'])) {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah_beli = (int)$_POST['jumlah'];
    $cust_name = !empty($_POST['cust_name']) ? mysqli_real_escape_string($conn, $_POST['cust_name']) : "Guest";
    $cust_wa = !empty($_POST['cust_wa']) ? mysqli_real_escape_string($conn, $_POST['cust_wa']) : "-";

    $cek_produk = mysqli_query($conn, "SELECT * FROM products WHERE id='$id_produk'");
    if ($data_produk = mysqli_fetch_assoc($cek_produk)) {
        if ($data_produk['stok'] >= $jumlah_beli && $jumlah_beli > 0) {
            $total_harga = $data_produk['harga'] * $jumlah_beli;
            $sisa_stok = $data_produk['stok'] - $jumlah_beli;
            $nama_produk = $data_produk['nama_produk'];

            mysqli_query($conn, "UPDATE products SET stok='$sisa_stok' WHERE id='$id_produk'");
            mysqli_query($conn, "INSERT INTO transactions (pembeli, customer_name, customer_wa, nama_produk, jumlah, total_harga) 
                         VALUES ('$username', '$cust_name', '$cust_wa', '$nama_produk', '$jumlah_beli', '$total_harga')");

            $_SESSION['pesan'] = "sukses";
        } else {
            $_SESSION['pesan'] = "stok_kurang";
        }
    }
    header("Location: dashboard.php");
    exit();
}

$produk_query = mysqli_query($conn, "SELECT * FROM products ORDER BY harga ASC");
$recent_query = mysqli_query($conn, "SELECT * FROM transactions ORDER BY tanggal DESC LIMIT 5");

$game_info = [
    'genshin'  => ['nama' => 'Genshin Impact', 'img' => 'https://play-lh.googleusercontent.com/iP2i_f23Z6I-5hoL2okPS4SxOGhj0q61Iyb0Y1m4xdTsbnaCmrjs7xKRnL6o5R4h-Yg'],
    'hsr'      => ['nama' => 'Honkai Star Rail', 'img' => 'https://stardb.gg/images/icons/star-rail-icon.webp'],
    'uma'      => ['nama' => 'Uma Musume', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.nM0u8c8-lJt5NYR-VbUIoAHaHa?pid=Api&P=0&h=220'],
    'mlbb'     => ['nama' => 'Mobile Legends', 'img' => 'https://www.gamersoft.net/wp-content/uploads/2023/05/mobile-legends-bang-bang.webp'],
    'hok'      => ['nama' => 'Honor of Kings', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.nM0u8c8-lJt5NYR-VbUIoAHaHa?pid=Api&P=0&h=220'],
    'ff'       => ['nama' => 'Free Fire', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.gDIuTjSv6lO19IS5SdhTAAHaHa?pid=Api&P=0&h=220'],
    'pubg'     => ['nama' => 'PUBG Mobile', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.VajQ0fTomIT_NVpcskyXhQHaF7?pid=Api&P=0&h=220'],
    'val'      => ['nama' => 'Valorant', 'img' => 'https://tse4.mm.bing.net/th/id/OIP.e0aqvc6qwmq9wqvfc6AkzwHaHa?pid=Api&P=0&h=220']
];

$grouped_products = [];
while($r = mysqli_fetch_assoc($produk_query)) {
    $n = strtolower($r['nama_produk']);
    $cat = 'other';
    if(strpos($n, 'genshin') !== false) $cat = 'genshin';
    elseif(strpos($n, 'honkai') !== false || strpos($n, 'hsr') !== false || strpos($n, 'star rail') !== false) $cat = 'hsr';
    elseif(strpos($n, 'uma') !== false) $cat = 'uma';
    elseif(strpos($n, 'mobile legends') !== false || strpos($n, 'mlbb') !== false) $cat = 'mlbb';
    elseif(strpos($n, 'honor of kings') !== false || strpos($n, 'hok') !== false) $cat = 'hok';
    elseif(strpos($n, 'free fire') !== false || strpos($n, 'ff') !== false) $cat = 'ff';
    elseif(strpos($n, 'pubg') !== false) $cat = 'pubg';
    elseif(strpos($n, 'valorant') !== false || strpos($n, 'val') !== false) $cat = 'val';
    $grouped_products[$cat][] = $r;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Dashboard Kasir</title>
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
        .topbar { position: sticky; top: 0; z-index: 100; background: rgba(6, 9, 15, 0.85); backdrop-filter: blur(20px); padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px; }
        
        /* Jam & Sosmed - Responsive */
        .clock-box { background: rgba(255,255,255,0.05); padding: 5px 15px; border-radius: 50px; border: 1px solid var(--border-color); color: var(--primary); font-family: monospace; font-size: 14px; font-weight: bold; }
        .sosmed-capsule { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.03); padding: 5px 12px; border-radius: 50px; border: 1px solid var(--border-color); }

        .content { padding: 20px; width: 100%; }
        .grid-layout { display: grid; grid-template-columns: 1fr 350px; gap: 20px; }
        
        /* Responsive Grid */
        @media (max-width: 992px) {
            .grid-layout { grid-template-columns: 1fr; }
            .topbar { padding: 10px; }
            .clock-box { display: none; } /* Jam sembunyi di HP biar gak penuh */
        }

        .box { background: var(--bg-surface); padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); height: fit-content; }
        .game-tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 20px; scrollbar-width: none; }
        .game-tab { min-width: 90px; padding: 10px; background: rgba(0,0,0,0.4); border: 2px solid var(--border-color); border-radius: 15px; cursor: pointer; text-align: center; transition: 0.3s; flex-shrink: 0; }
        .game-tab.active { border-color: var(--primary); background: rgba(34, 197, 94, 0.1); }
        .game-tab img { width: 35px; border-radius: 8px; margin-bottom: 5px; }

        .item-grid { display: none; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
        .item-grid.show { display: grid; }
        .item-card { border: 2px solid var(--border-color); padding: 15px; border-radius: 12px; cursor: pointer; text-align: center; transition: 0.2s; }
        .item-card input { display: none; }
        .item-card:has(input:checked) { border-color: var(--primary); background: rgba(34, 197, 94, 0.1); }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 11px; color: var(--primary); margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); color: white; border-radius: 10px; outline: none; }
        
        .btn-buy { width: 100%; padding: 15px; background: #334155; color: #94A3B8; border: none; border-radius: 12px; font-weight: bold; cursor: not-allowed; }
        .btn-buy.active { background: var(--primary); color: #000; cursor: pointer; box-shadow: 0 0 15px rgba(34,197,94,0.3); }

        .activity-card { padding: 12px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 10px; border-left: 3px solid var(--primary); font-size: 13px; }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="logo" style="display:flex; align-items:center; gap:10px;">
            <div style="background:var(--primary); color:#000; padding:8px; border-radius:8px;"><i class="fas fa-bolt"></i></div>
            <h2 style="font-weight:800; font-size:20px;">Suzuka</h2>
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="inventory.php"><i class="fas fa-box-open"></i> Inventory</a>
            <a href="transactions.php"><i class="fas fa-receipt"></i> Transactions</a>
            <a href="index.php" style="margin-top:auto; color:var(--danger)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <!-- Kiri -->
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div>
                    <h4 style="font-weight:700; margin:0;">Suzuka Store</h4>
                    <p style="font-size:10px; color:var(--text-muted); margin:0;">User: <?php echo $username; ?></p>
                </div>
            </div>

            <!-- Tengah (Mobile: Hidden) -->
            <div class="clock-box" id="realtimeClock">00:00:00</div>

            <!-- Kanan -->
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="sosmed-capsule">
                    <a href="https://www.facebook.com/share/1AiMvB5THB/" target="_blank" style="color:#1877F2;"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/zidni_tira?igsh=MWQ4eTdoZGUzdDEzbg==" target="_blank" style="color:#E4405F;"><i class="fab fa-instagram"></i></a>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=22C55E&color=fff&bold=true" style="width:32px; height:32px; border-radius:50%; border:1px solid var(--primary);">
            </div>
        </header>

        <div class="content">
            <div class="grid-layout">
                <div class="box">
                    <form action="" method="POST">
                        <label style="font-size:12px; font-weight:800; color:var(--primary); display:block; margin-bottom:10px;">1. PILIH GAME</label>
                        <div class="game-tabs">
                            <?php foreach($game_info as $kode => $info): ?>
                                <div class="game-tab" id="tab-<?php echo $kode; ?>" onclick="selectGame('<?php echo $kode; ?>')">
                                    <img src="<?php echo $info['img']; ?>">
                                    <div style="font-size:9px; font-weight:700;"><?php echo $info['nama']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <label style="font-size:12px; font-weight:800; color:var(--primary); display:block; margin-bottom:10px;">2. PILIH NOMINAL</label>
                        <?php foreach($game_info as $kode => $info): ?>
                            <div class="item-grid" id="grid-<?php echo $kode; ?>">
                                <?php if(isset($grouped_products[$kode])): foreach($grouped_products[$kode] as $item): ?>
                                    <label class="item-card">
                                        <input type="radio" name="id_produk" value="<?php echo $item['id']; ?>" onchange="activateBtn()">
                                        <div style="font-size:11px; font-weight:700;"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                                        <div style="color:var(--primary); font-size:12px; font-weight:800; margin-top:5px;">Rp <?php echo number_format($item['harga'],0,',','.'); ?></div>
                                    </label>
                                <?php endforeach; endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:20px;">
                            <div class="form-group">
                                <label>Customer</label>
                                <input type="text" name="cust_name" required placeholder="Nama...">
                            </div>
                            <div class="form-group">
                                <label>WhatsApp</label>
                                <input type="text" name="cust_wa" placeholder="08...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Jumlah</label>
                            <input type="number" name="jumlah" value="1" min="1">
                        </div>
                        <button type="submit" name="beli_barang" class="btn-buy" id="btnBuy" disabled>Pilih Item Dulu</button>
                    </form>
                </div>

                <div class="box">
                    <h5 style="color:var(--primary); margin-bottom:15px;"><i class="fas fa-history"></i> Recent Sales</h5>
                    <?php while($tx = mysqli_fetch_assoc($recent_query)): ?>
                        <div class="activity-card">
                            <b><?php echo htmlspecialchars($tx['customer_name']); ?></b><br>
                            <span style="color:var(--text-muted); font-size:11px;"><?php echo $tx['nama_produk']; ?></span>
                            <div style="display:flex; justify-content:space-between; margin-top:5px; font-weight:bold; color:var(--primary);">
                                <span>Rp <?php echo number_format($tx['total_harga'],0,',','.'); ?></span>
                                <span style="font-size:10px; color:var(--text-muted);"><?php echo date('H:i', strtotime($tx['tanggal'])); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <a href="transactions.php" style="color:var(--text-muted); font-size:11px; text-decoration:none;">Lihat Semua &rarr;</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        document.getElementById('menuToggle').onclick = () => { sidebar.classList.add('show'); overlay.classList.add('show'); }
        overlay.onclick = () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }

        function updateClock() {
            const el = document.getElementById('realtimeClock');
            if(el) el.innerText = new Date().toLocaleTimeString('id-ID');
        }
        setInterval(updateClock, 1000); updateClock();

        function selectGame(kode) {
            document.querySelectorAll('.game-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.item-grid').forEach(g => g.classList.remove('show'));
            document.getElementById('tab-'+kode).classList.add('active');
            document.getElementById('grid-'+kode).classList.add('show');
        }

        function activateBtn() {
            const btn = document.getElementById('btnBuy');
            btn.disabled = false; btn.classList.add('active'); btn.innerText = "🚀 PROSES SEKARANG";
        }
        window.onload = () => selectGame('genshin');

        <?php if(isset($_SESSION['pesan'])): ?>
            Swal.fire({
                icon: '<?php echo ($_SESSION['pesan'] == "sukses" ? "success" : "error"); ?>',
                title: '<?php echo ($_SESSION['pesan'] == "sukses" ? "Berhasil!" : "Gagal!"); ?>',
                text: '<?php echo ($_SESSION['pesan'] == "sukses" ? "Pesanan diproses" : "Stok tidak cukup"); ?>',
                background: '#0A0F1A', color: '#fff', timer: 2000, showConfirmButton: false
            });
            <?php unset($_SESSION['pesan']); ?>
        <?php endif; ?>
    </script>
</body>
</html>