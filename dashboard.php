<?php
session_start();

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// ==========================================
// 1. KONEKSI DATABASE
// ==========================================
$conn = mysqli_connect("localhost", "root", "", "umamusume_db");
if (!$conn) { die("Koneksi gagal!"); }

// ==========================================
// 2. LOGIKA PROSES BELI
// ==========================================
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

            $_SESSION['pesan'] = "<div class='toast success'><i class='fas fa-check-circle'></i> <div><b>Berhasil!</b><br>Pesanan $cust_name diproses.</div></div>";
        } else {
            $_SESSION['pesan'] = "<div class='toast error'><i class='fas fa-exclamation-triangle'></i> <div><b>Gagal!</b><br>Stok tidak cukup.</div></div>";
        }
    }
    header("Location: dashboard.php");
    exit();
}

$pesan = "";
if (isset($_SESSION['pesan'])) {
    $pesan = $_SESSION['pesan'];
    unset($_SESSION['pesan']); 
}

// Ambil data produk & 5 transaksi terakhir
$produk_query = mysqli_query($conn, "SELECT * FROM products ORDER BY harga ASC");
$recent_query = mysqli_query($conn, "SELECT * FROM transactions ORDER BY tanggal DESC LIMIT 5");

// Info Game (Lengkap sesuai Inventory)
$game_info = [
    'genshin'  => ['nama' => 'Genshin Impact', 'img' => 'https://play-lh.googleusercontent.com/iP2i_f23Z6I-5hoL2okPS4SxOGhj0q61Iyb0Y1m4xdTsbnaCmrjs7xKRnL6o5R4h-Yg'],
    'hsr'      => ['nama' => 'Honkai Star Rail', 'img' => 'https://stardb.gg/images/icons/star-rail-icon.webp'],
    'uma'      => ['nama' => 'Uma Musume', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.M4fsR_34nzK9w5KOWzn8QAHaHa?pid=Api&P=0&h=220'],
    'mlbb'     => ['nama' => 'Mobile Legends', 'img' => 'https://www.gamersoft.net/wp-content/uploads/2023/05/mobile-legends-bang-bang.webp'],
    'hok'      => ['nama' => 'Honor of Kings', 'img' => 'https://play-lh.googleusercontent.com/Xbe63-e45mI5V6Uf4F8R7vW8S7tN_f7F-V8k8G_M9_A_r-f0_L-K_v_p_K9_P_L_Y_A=w240-h480-rw'],
    'ff'       => ['nama' => 'Free Fire', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.gDIuTjSv6lO19IS5SdhTAAHaHa?pid=Api&P=0&h=220'],
    'pubg'     => ['nama' => 'PUBG Mobile', 'img' => 'https://play-lh.googleusercontent.com/JRd0v_v_98555-p_A_V9v_S_v_K-V9v_S_v_v-V9v_S_v_v-V9v_S_v_v-V9v_S_v_v=w240-h480-rw'],
    'val'      => ['nama' => 'Valorant', 'img' => 'https://tse1.mm.bing.net/th/id/OIP.9YvE8zD8f-v_I9uO6f7m8QHaHa?pid=Api&P=0&h=220']
];

// GROUPING LOGIC (FIX HOK & FF)
$grouped_products = [];
while($r = mysqli_fetch_assoc($produk_query)) {
    $n = strtolower($r['nama_produk']);
    $cat = 'other';
    
    if(strpos($n, 'genshin') !== false) $cat = 'genshin';
    elseif(strpos($n, 'honkai') !== false || strpos($n, 'hsr') !== false || strpos($n, 'star rail') !== false) $cat = 'hsr';
    elseif(strpos($n, 'uma') !== false) $cat = 'uma';
    elseif(strpos($n, 'mobile legends') !== false || strpos($n, 'mlbb') !== false) $cat = 'mlbb';
    // CEK HOK DULUAN BIAR HURUF 'F'-NYA GAK KEMAKAN FF
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
    
    <style>
        :root { --bg-base: #06090F; --bg-surface: rgba(18, 24, 38, 0.6); --border-color: rgba(255, 255, 255, 0.08); --primary: #22C55E; --text-main: #FFFFFF; --text-muted: #808B9F; --radius-lg: 24px; --radius-md: 14px; --blur: blur(20px); --danger: #EF4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; background-image: radial-gradient(circle at 10% 50%, rgba(34, 197, 94, 0.05), transparent 20%); }
        
        /* Sidebar */
        .sidebar { position: fixed; left: -320px; top: 0; bottom: 0; width: 300px; background: rgba(10, 15, 26, 0.9); backdrop-filter: var(--blur); border-right: 1px solid var(--border-color); padding: 30px 20px; display: flex; flex-direction: column; z-index: 1000; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.show { left: 0; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 999; display: none; }
        .sidebar-overlay.show { display: block; }

        .logo { display: flex; align-items: center; gap: 15px; margin-bottom: 35px; }
        .menu a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-md); font-weight: 500; margin-bottom: 5px; transition: 0.3s; }
        .menu a.active { background: rgba(34, 197, 94, 0.08); color: var(--primary); }

        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { position: sticky; top: 0; z-index: 5; background: rgba(6, 9, 15, 0.85); backdrop-filter: var(--blur); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        
        .btn-menu { background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); color: var(--text-main); font-size: 20px; width: 45px; height: 45px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .clock-box { background: rgba(255,255,255,0.05); padding: 8px 20px; border-radius: 50px; border: 1px solid var(--border-color); color: var(--primary); font-family: monospace; font-size: 16px; font-weight: bold; }

        .user-profile { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.05); padding: 5px 15px 5px 5px; border-radius: 50px; border: 1px solid var(--border-color); }
        .user-profile img { width: 35px; height: 35px; border-radius: 50%; border: 2px solid var(--primary); }

        .content { padding: 40px; max-width: 1400px; margin: 0 auto; width: 100%; }
        .grid-layout { display: grid; grid-template-columns: 1fr 380px; gap: 24px; }
        .box { background: var(--bg-surface); padding: 30px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); }

        /* Game Tabs */
        .game-tabs { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 25px; }
        .game-tab { min-width: 110px; padding: 15px 10px; background: rgba(0,0,0,0.4); border: 2px solid var(--border-color); border-radius: 20px; cursor: pointer; text-align: center; transition: 0.3s; flex-shrink: 0; }
        .game-tab.active { border-color: var(--primary); background: rgba(34, 197, 94, 0.1); transform: translateY(-5px); }
        .game-tab img { width: 45px; height: 45px; border-radius: 12px; margin-bottom: 8px; object-fit: cover; }
        
        /* Items Grid */
        .item-grid { display: none; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
        .item-grid.show { display: grid; }
        .item-card { border: 2px solid var(--border-color); padding: 18px; border-radius: 15px; cursor: pointer; text-align: center; transition: 0.3s; position: relative; }
        .item-card input { display: none; }
        .item-card:hover { border-color: rgba(34, 197, 94, 0.5); }
        .item-card:has(input:checked) { border-color: var(--primary); background: rgba(34, 197, 94, 0.12); box-shadow: 0 0 20px rgba(34, 197, 94, 0.15); transform: scale(1.02); }

        /* Inputs */
        .form-group { margin-top: 20px; }
        .form-group label { display: block; font-size: 11px; color: var(--primary); margin-bottom: 8px; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .form-group input { width: 100%; padding: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); color: white; border-radius: 12px; outline: none; transition: 0.3s; }
        .form-group input:focus { border-color: var(--primary); background: rgba(255,255,255,0.05); }

        .btn-buy { width: 100%; padding: 18px; background: #1E293B; color: #475569; border: none; border-radius: 15px; font-weight: 800; margin-top: 30px; cursor: not-allowed; transition: 0.4s; font-size: 16px; text-transform: uppercase; }
        .btn-buy.active { background: var(--primary); color: #000; cursor: pointer; box-shadow: 0 10px 25px var(--primary-glow); }

        /* Recent Activity */
        .activity-card { padding: 18px; background: rgba(255,255,255,0.02); border-radius: 15px; margin-bottom: 12px; border: 1px solid var(--border-color); border-left: 4px solid var(--primary); transition: 0.3s; }
        .activity-card:hover { transform: translateX(5px); background: rgba(255,255,255,0.04); }

        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { display: flex; align-items: center; gap: 15px; padding: 15px 25px; background: #0A0F1A; border: 1px solid var(--primary); border-radius: 12px; color: white; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if($pesan != "") echo "<div class='toast-container'>$pesan</div>"; ?>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <div style="background:var(--primary); color:#000; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-bolt"></i></div> 
            <h2 style="font-weight:800;">Suzuka</h2>
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="inventory.php"><i class="fas fa-box-open"></i> Inventory</a>
            <a href="transactions.php"><i class="fas fa-receipt"></i> Transactions</a>
            <a href="index.php" style="margin-top:auto; color:var(--danger)" onclick="return confirm('Logout sekarang?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:20px;">
                <button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div><h3 style="font-weight:700;">Top-Up Kasir</h3><p style="font-size:12px; color:var(--text-muted);">Trainer: <?php echo $username; ?></p></div>
            </div>
            
            <div class="clock-box" id="realtimeClock">00:00:00</div>

            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=22C55E&color=fff&bold=true">
                <span style="font-size:13px; font-weight:600;"><?php echo $username; ?></span>
            </div>
        </header>

        <div class="content">
            <div class="grid-layout">
                
                <!-- KOLOM KIRI -->
                <div class="box">
                    <form action="" method="POST">
                        <label class="form-group" style="font-weight:800; color:var(--primary); display:block; margin-bottom:15px;">1. PILIH KATEGORI GAME</label>
                        <div class="game-tabs">
                            <?php foreach($game_info as $kode => $info): ?>
                                <div class="game-tab" id="tab-<?php echo $kode; ?>" onclick="selectGame('<?php echo $kode; ?>')">
                                    <img src="<?php echo $info['img']; ?>">
                                    <div style="font-size:10px; font-weight:700; text-transform:uppercase;"><?php echo $info['nama']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <label class="form-group" style="font-weight:800; color:var(--primary); display:block; margin-bottom:15px;">2. PILIH NOMINAL ITEM</label>
                        <?php foreach($game_info as $kode => $info): ?>
                            <div class="item-grid" id="grid-<?php echo $kode; ?>">
                                <?php if(isset($grouped_products[$kode])): foreach($grouped_products[$kode] as $item): ?>
                                    <label class="item-card">
                                        <input type="radio" name="id_produk" value="<?php echo $item['id']; ?>" onchange="activateBtn()">
                                        <div style="font-size:13px; font-weight:700; color:#fff;"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                                        <div style="color:var(--primary); font-size:14px; font-weight:800; margin-top:8px;">Rp <?php echo number_format($item['harga'],0,',','.'); ?></div>
                                        <div style="font-size:10px; color:var(--text-muted); margin-top:5px;">Stok: <?php echo $item['stok']; ?></div>
                                    </label>
                                <?php endforeach; else: ?>
                                    <p style="color:var(--text-muted); font-size:13px; grid-column: 1/-1; text-align:center; padding:20px;">Belum ada produk untuk game ini.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:30px; border-top: 1px solid var(--border-color); padding-top:25px;">
                            <div class="form-group" style="margin-top:0;">
                                <label>Nama Customer</label>
                                <input type="text" name="cust_name" placeholder="Contoh: Dika Ganz" required>
                            </div>
                            <div class="form-group" style="margin-top:0;">
                                <label>WhatsApp Pelanggan</label>
                                <input type="text" name="cust_wa" placeholder="0812xxxxxxxx">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Jumlah Pembelian</label>
                            <input type="number" name="jumlah" value="1" min="1" style="width:100px;">
                        </div>

                        <button type="submit" name="beli_barang" class="btn-buy" id="btnBuy" disabled>Pilih Produk Dahulu</button>
                    </form>
                </div>

                <!-- KOLOM KANAN -->
                <div class="box">
                    <h4 style="margin-bottom:25px; color:var(--primary); display:flex; align-items:center; gap:12px; font-weight:700;">
                        <i class="fas fa-history"></i> SALES TERAKHIR
                    </h4>
                    <?php if(mysqli_num_rows($recent_query) > 0): ?>
                        <?php while($tx = mysqli_fetch_assoc($recent_query)): ?>
                            <div class="activity-card">
                                <div style="display:flex; justify-content:space-between;">
                                    <div style="font-size:13px; font-weight:700; color:#fff;"><?php echo htmlspecialchars($tx['customer_name']); ?></div>
                                    <div style="font-size:10px; color:var(--text-muted);"><?php echo date('H:i', strtotime($tx['tanggal'])); ?></div>
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:3px;"><?php echo $tx['nama_produk']; ?> (x<?php echo $tx['jumlah']; ?>)</div>
                                <div style="font-size:13px; color:var(--primary); font-weight:800; margin-top:8px;">Rp <?php echo number_format($tx['total_harga'],0,',','.'); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color:var(--text-muted); text-align:center; font-size:13px; padding:20px;">Belum ada penjualan hari ini.</p>
                    <?php endif; ?>
                    
                    <a href="transactions.php" style="display:block; text-align:center; color:var(--primary); font-size:12px; font-weight:600; text-decoration:none; margin-top:15px; padding:10px; background:rgba(34, 197, 94, 0.05); border-radius:10px;">Lihat Semua Laporan &rarr;</a>
                </div>

            </div>
        </div>
    </main>

    <script>
        // SIDEBAR CONTROL
        const btnMenu = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        btnMenu.addEventListener('click', () => { 
            sidebar.classList.add('show'); 
            overlay.classList.add('show'); 
        });
        
        overlay.addEventListener('click', () => { 
            sidebar.classList.remove('show'); 
            overlay.classList.remove('show'); 
        });

        // JAM REAL-TIME
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('realtimeClock').innerText = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // TAB SYSTEM
        function selectGame(kode) {
            document.querySelectorAll('.game-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.item-grid').forEach(g => g.classList.remove('show'));
            
            const selectedTab = document.getElementById('tab-'+kode);
            const selectedGrid = document.getElementById('grid-'+kode);
            
            if(selectedTab && selectedGrid) {
                selectedTab.classList.add('active');
                selectedGrid.classList.add('show');
            }
        }

        // BTN ACTIVATE
        function activateBtn() {
            const btn = document.getElementById('btnBuy');
            btn.disabled = false;
            btn.classList.add('active');
            btn.innerText = "🚀 PROSES PESANAN SEKARANG";
        }

        // Default Load
        window.onload = () => selectGame('genshin');
    </script>
</body>
</html>