<?php
session_start();

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// 1. KONEKSI DATABASE
require 'config.php'; 

// ================================================================
// 2. MAGIC TRICK: AUTO-INSERT SALDO E-WALLET KE DATABASE JIKA KOSONG
// ================================================================
$cek_ewallet = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE nama_produk LIKE '%DANA%' OR nama_produk LIKE '%GoPay%'");
$data_ew = mysqli_fetch_assoc($cek_ewallet);

if ($data_ew['total'] == 0) {
    // ================================================================
// 2. MAGIC TRICK: AUTO-INSERT SALDO E-WALLET KE DATABASE JIKA KOSONG
// ================================================================
$cek_ewallet = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE nama_produk LIKE '%DANA%' OR nama_produk LIKE '%GoPay%'");
$data_ew = mysqli_fetch_assoc($cek_ewallet);

if ($data_ew['total'] == 0) {
    // Daftar E-Wallet dan Nominal Saldo
    $ewallets = ['DANA', 'GoPay', 'OVO', 'ShopeePay'];
    $nominals = [5.000, 10.000, 15.000, 25.000, 45.000, 50.000, 75.000, 100.000];

    foreach ($ewallets as $ew) {
        foreach ($nominals as $nom) {
            // Konversi angka jadi huruf K (contoh: 5000 dibagi 1000 = 5K)
            $label_k = ($nom / 1000) . "K"; 
            
            $nama = "Saldo $ew $label_k";
            $harga = $nom + 1000; // Harga database tetep angka beneran + Fee 1000
            
            mysqli_query($conn, "INSERT INTO products (nama_produk, harga, stok) VALUES ('$nama', '$harga', 999)");
        }
    }
    // Refresh halaman biar nominalnya langsung nongol
    header("Location: dashboard.php");
    exit();
}
// ================================================================
}
// ================================================================

// 3. LOGIKA PROSES BELI
if (isset($_POST['beli_barang'])) {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah_beli = (int)$_POST['jumlah'];
    $cust_name = !empty($_POST['cust_name']) ? mysqli_real_escape_string($conn, $_POST['cust_name']) : "Guest";
    $cust_wa = !empty($_POST['cust_wa']) ? mysqli_real_escape_string($conn, $_POST['cust_wa']) : "-";
    $metode_bayar = isset($_POST['metode_bayar']) ? $_POST['metode_bayar'] : "QRIS";

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

// ARRAY DATA GAME
$game_info = [
    'genshin'  => ['nama' => 'Genshin Impact', 'img' => 'https://tse4.mm.bing.net/th/id/OIP.M4zO4XAX1j5De5qK5rUF1gHaHa?pid=Api&h=220&P=0', 'dev' => 'HoYoverse'],
    'hsr'      => ['nama' => 'Honkai Star Rail', 'img' => 'https://stardb.gg/images/icons/star-rail-icon.webp', 'dev' => 'HoYoverse'],
    'uma'      => ['nama' => 'Uma Musume', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.M4fsR_34nzK9w5KOWzn8QAHaHa?pid=Api&h=220&P=0', 'dev' => 'Cygames'],
    'mlbb'     => ['nama' => 'Mobile Legends', 'img' => 'https://www.gamersoft.net/wp-content/uploads/2023/05/mobile-legends-bang-bang.webp', 'dev' => 'Moonton'],
    'hok'      => ['nama' => 'Honor of Kings', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.nM0u8c8-lJt5NYR-VbUIoAHaHa?pid=Api&P=0&h=220', 'dev' => 'Level Infinite'],
    'ff'       => ['nama' => 'Free Fire', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.gDIuTjSv6lO19IS5SdhTAAHaHa?pid=Api&P=0&h=220', 'dev' => 'Garena'],
    'pubg'     => ['nama' => 'PUBG Mobile', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.VajQ0fTomIT_NVpcskyXhQHaF7?pid=Api&P=0&h=220', 'dev' => 'Tencent'],
    'val'      => ['nama' => 'Valorant', 'img' => 'https://tse4.mm.bing.net/th/id/OIP.e0aqvc6qwmq9wqvfc6AkzwHaHa?pid=Api&P=0&h=220', 'dev' => 'Riot Games']
];

// ARRAY DATA E-WALLET
$ewallet_info = [
    'dana'      => ['nama' => 'Saldo DANA', 'img' => 'https://tse4.mm.bing.net/th/id/OIP.-YC0LT_rwD2KmdAMmHDYmwHaHa?pid=Api&P=0&h=220', 'dev' => 'E-Wallet'],
    'gopay'     => ['nama' => 'Saldo GoPay', 'img' => 'https://tse1.mm.bing.net/th/id/OIP.RvVW0QxAyIp5eonB9QzFoQHaHa?pid=Api&P=0&h=220', 'dev' => 'E-Wallet'],
    'shopeepay' => ['nama' => 'ShopeePay', 'img' => 'https://tse4.mm.bing.net/th/id/OIP.-ZUGFWs4sP7mkOS3p2R1cAHaEJ?pid=Api&P=0&h=220', 'dev' => 'E-Wallet'],
    'ovo'       => ['nama' => 'Saldo OVO', 'img' => 'https://tse2.mm.bing.net/th/id/OIP.hg6Vvx7_DxWXXuo5_k8i1AHaHZ?pid=Api&P=0&h=220', 'dev' => 'E-Wallet']
];

$grouped_products = [];
while($r = mysqli_fetch_assoc($produk_query)) {
    $n = strtolower($r['nama_produk']);
    $cat = 'other';
    
    // Filter Kategori Game
    if(strpos($n, 'genshin') !== false) $cat = 'genshin';
    elseif(strpos($n, 'honkai') !== false || strpos($n, 'hsr') !== false || strpos($n, 'star rail') !== false) $cat = 'hsr';
    elseif(strpos($n, 'uma') !== false) $cat = 'uma';
    elseif(strpos($n, 'mobile legends') !== false || strpos($n, 'mlbb') !== false) $cat = 'mlbb';
    elseif(strpos($n, 'honor of kings') !== false || strpos($n, 'hok') !== false) $cat = 'hok';
    elseif(strpos($n, 'free fire') !== false || strpos($n, 'ff') !== false) $cat = 'ff';
    elseif(strpos($n, 'pubg') !== false) $cat = 'pubg';
    elseif(strpos($n, 'valorant') !== false || strpos($n, 'val') !== false) $cat = 'val';
    
    // Filter Kategori E-Wallet Baru
    elseif(strpos($n, 'dana') !== false) $cat = 'dana';
    elseif(strpos($n, 'gopay') !== false || strpos($n, 'go-pay') !== false) $cat = 'gopay';
    elseif(strpos($n, 'shopeepay') !== false || strpos($n, 'spay') !== false) $cat = 'shopeepay';
    elseif(strpos($n, 'ovo') !== false) $cat = 'ovo';

    $grouped_products[$cat][] = $r;
}

// Gabungin array Game & E-Wallet buat dilooping form nominal
$all_categories = array_merge($game_info, $ewallet_info);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Dashboard Top Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --bg-base: #06090F; --bg-surface: rgba(18, 24, 38, 0.6); --border-color: rgba(255, 255, 255, 0.08); --primary: #22C55E; --text-main: #FFFFFF; --text-muted: #808B9F; --radius-lg: 24px; --radius-md: 14px; --danger: #EF4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; min-height: 100vh; background-image: radial-gradient(circle at 10% 50%, rgba(34, 197, 94, 0.05), transparent 20%); }
        
        /* Sidebar */
        .sidebar { position: fixed; left: -320px; top: 0; bottom: 0; width: 280px; background: rgba(10, 15, 26, 0.95); backdrop-filter: blur(20px); border-right: 1px solid var(--border-color); padding: 30px 20px; display: flex; flex-direction: column; z-index: 2000; transition: 0.3s; }
        .sidebar.show { left: 0; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1999; display: none; }
        .sidebar-overlay.show { display: block; }
        .menu a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-md); font-weight: 500; margin-bottom: 5px; transition: 0.3s; }
        .menu a.active { background: rgba(34, 197, 94, 0.08); color: var(--primary); }

        .main { flex: 1; width: 100%; display: flex; flex-direction: column; overflow-x: hidden; }

        /* Top Bar */
        .top-nav { background: rgba(6, 9, 15, 0.85); padding: 15px 25px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(20px); }
        .btn-menu { background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); color: #fff; width: 45px; height: 45px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        
        .search-box { position: relative; width: 350px; }
        .search-box input { 
            width: 100%; 
            background: rgba(255, 255, 255, 0.03); 
            border: 1px solid var(--border-color); 
            padding: 10px 45px; 
            border-radius: 50px; 
            color: white; 
            font-size: 13px; 
            outline: none;
            transition: 0.3s;
        }
        .search-box input:focus { border-color: var(--primary); background: rgba(255, 255, 255, 0.07); }
        .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }

        .sosmed-capsule { display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.05); padding: 8px 15px; border-radius: 50px; border: 1px solid var(--border-color); }
        .clock-box { background: rgba(255,255,255,0.05); padding: 8px 20px; border-radius: 50px; border: 1px solid var(--border-color); color: var(--primary); font-family: monospace; font-size: 16px; font-weight: bold; }

        .tab-nav { background: rgba(18, 24, 38, 0.4); padding: 0 30px; display: flex; gap: 20px; border-bottom: 1px solid var(--border-color); overflow-x: auto; scrollbar-width: none; }
        .tab-item { padding: 15px 10px; color: var(--text-muted); text-decoration: none; font-size: 14px; white-space: nowrap; border-bottom: 2px solid transparent; transition: 0.3s; }
        .tab-item.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: bold; }
        .tab-special { background: rgba(34, 197, 94, 0.15); color: var(--primary) !important; border-radius: 8px; padding: 8px 15px; margin: 10px 0; font-weight: bold; }

        .content { padding: 30px; max-width: 1400px; margin: 0 auto; width: 100%; }
        .grid-layout { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }

        .game-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 20px; margin-top: 20px; }
        .game-card { background: var(--bg-surface); border-radius: 12px; overflow: hidden; cursor: pointer; transition: 0.3s; border: 2px solid transparent; position: relative; }
        .game-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .game-card.active { border-color: var(--primary); box-shadow: 0 0 15px rgba(34, 197, 94, 0.2); }
        .game-card img { width: 100%; aspect-ratio: 1/1; object-fit: cover; }
        .game-card-body { padding: 10px; text-align: center; }

        .box { background: var(--bg-surface); padding: 25px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); height: fit-content; }
        .item-grid { display: none; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; margin-top: 15px; }
        .item-grid.show { display: grid; }
        .item-card { background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); padding: 12px; border-radius: 10px; text-align: center; cursor: pointer; transition: 0.2s; }
        .item-card input { display: none; }
        .item-card:has(input:checked) { border-color: var(--primary); background: rgba(34, 197, 94, 0.08); }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 11px; color: var(--primary); margin-bottom: 5px; font-weight: bold; text-transform: uppercase; }
        .form-group input { width: 100%; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); color: white; border-radius: 8px; outline: none; }
        
        .btn-buy { width: 100%; padding: 15px; background: #1a202c; color: #4a5568; border: none; border-radius: 10px; font-weight: bold; cursor: not-allowed; transition: 0.3s; }
        .btn-buy.active { background: var(--primary); color: #000; cursor: pointer; box-shadow: 0 0 15px rgba(34, 197, 94, 0.3); }

        /* KUNCI STYLE METODE PEMBAYARAN */
        .pay-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 10px; 
            margin-top: 12px; 
        }
        .pay-card { 
            background: #FFFFFF; 
            border: 2px solid transparent; 
            border-radius: 12px; 
            height: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            transition: 0.3s;
            padding: 8px; 
        }
        .pay-card img { 
            max-width: 80%;
            max-height: 80%;
            object-fit: contain; 
            filter: grayscale(1); 
            opacity: 0.6; 
            transition: 0.3s;
        }
        .pay-card b {
            color: #94A3B8;
            transition: 0.3s;
        }
        .pay-card input { display: none; }
        
        .pay-card:hover { 
            border-color: rgba(34, 197, 94, 0.5); 
        }
        
        /* KONDISI SAAT DIPILIH (TERANG) */
        .pay-card:has(input:checked) { 
            border-color: var(--primary) !important; 
            background: #F0FDF4 !important; 
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.3);
        }
        .pay-card:has(input:checked) img { 
            filter: grayscale(0) !important; 
            opacity: 1 !important; 
        }
        .pay-card:has(input:checked) b {
            color: #FF6600 !important;
        }

        @media (max-width: 992px) { 
            .grid-layout { grid-template-columns: 1fr; } 
            .search-box, .clock-box { display: none; } 
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="logo" style="display:flex; align-items:center; gap:12px; margin-bottom: 30px;">
            <div style="background:var(--primary); color:#000; padding:10px; border-radius:12px;"><i class="fas fa-bolt"></i></div>
            <h2 style="font-weight:800; font-size:22px;">Suzuka</h2>
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="inventory.php"><i class="fas fa-box-open"></i> Inventory</a>
            <a href="transactions.php"><i class="fas fa-receipt"></i> Transactions</a>
            <a href="index.php" style="margin-top:auto; color:var(--danger)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main">
        <header class="top-nav">
            <div style="display:flex; align-items:center; gap:20px;">
                <button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="gameSearch" placeholder="Cari Voucher atau E-Wallet...">
                </div>
            </div>
            
            <div class="clock-box" id="realtimeClock">00:00:00</div>

            <div style="display:flex; align-items:center; gap:15px;">
                <div class="sosmed-capsule">
                    <a href="https://www.facebook.com/share/1AiMvB5THB/" target="_blank" style="color:#1877F2;"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/zidni_tira?igsh=MWQ4eTdoZGUzdDEzbg==" target="_blank" style="color:#E4405F;"><i class="fab fa-instagram"></i></a>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=22C55E&color=fff&bold=true" style="width:38px; border-radius:50%; border: 2px solid var(--primary);">
            </div>
        </header>

        <nav class="tab-nav">
            <a href="#" class="tab-item active tab-special">Top Up Game & Saldo</a>
            <a href="cektransaksi.php" class="tab-item">Cek Transaksi</a>
        </nav>

        <div class="content">
            <div class="grid-layout">
                <div>
                    <!-- KATEGORI GAME -->
                    <h3 style="margin-bottom: 20px; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-fire" style="color:var(--primary);"></i> Game Terpopuler
                    </h3>
                    <div class="game-grid" id="gameGrid">
                        <?php foreach($game_info as $kode => $info): ?>
                        <div class="game-card" id="card-<?php echo $kode; ?>" data-name="<?php echo strtolower($info['nama']); ?>" onclick="selectGame('<?php echo $kode; ?>')">
                            <img src="<?php echo $info['img']; ?>" alt="">
                            <div class="game-card-body">
                                <h4><?php echo $info['nama']; ?></h4>
                                <p><?php echo $info['dev']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- KATEGORI E-WALLET -->
                    <h3 style="margin-top: 40px; margin-bottom: 20px; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-wallet" style="color:var(--primary);"></i> Isi Saldo E-Wallet
                    </h3>
                    <div class="game-grid" id="ewalletGrid">
                        <?php foreach($ewallet_info as $kode => $info): ?>
                        <div class="game-card" id="card-<?php echo $kode; ?>" data-name="<?php echo strtolower($info['nama']); ?>" onclick="selectGame('<?php echo $kode; ?>')">
                            <!-- Background diset putih biar gambar logo E-Wallet gak pecah -->
                            <img src="<?php echo $info['img']; ?>" alt="" style="object-fit:contain; background:#fff; padding:15px;">
                            <div class="game-card-body">
                                <h4><?php echo $info['nama']; ?></h4>
                                <p><?php echo $info['dev']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="box" id="order-section">
                    <h4 style="margin-bottom:20px; border-bottom: 1px solid var(--border-color); padding-bottom:10px;">
                        <i class="fas fa-shopping-cart" style="color:var(--primary);"></i> Form Order
                    </h4>
                    
                    <form action="" method="POST">
                        <!-- 1. PILIH NOMINAL (GABUNGAN GAME & E-WALLET) -->
                        <label style="font-size:11px; font-weight:800; color:var(--primary);">1. Pilih Nominal</label>
                        <?php foreach($all_categories as $kode => $info): ?>
                            <div class="item-grid" id="grid-<?php echo $kode; ?>">
                                <?php if(isset($grouped_products[$kode]) && !empty($grouped_products[$kode])): foreach($grouped_products[$kode] as $item): ?>
                                    <label class="item-card">
                                        <input type="radio" name="id_produk" value="<?php echo $item['id']; ?>" onchange="activateBtn()">
                                        <div style="font-size:11px; font-weight:700; color:white;"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                                        <div style="color:var(--primary); font-size:12px; font-weight:800; margin-top:5px;">Rp <?php echo number_format($item['harga'],0,',','.'); ?></div>
                                    </label>
                                <?php endforeach; else: ?>
                                    <p style="grid-column: span 2; font-size:11px; color:var(--text-muted); padding:10px;">Item stok kosong.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- 2. DATA PELANGGAN -->
                        <div style="margin-top:25px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                            <label style="font-size:11px; font-weight:800; color:var(--primary);">2. Data Pelanggan / Nomor Tujuan</label>
                            
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:10px;">
                                <div class="form-group">
                                    <label>Nama / User ID</label>
                                    <input type="text" name="cust_name" required placeholder="User ID / No Tujuan">
                                </div>
                                <div class="form-group">
                                    <label>WhatsApp</label>
                                    <input type="text" name="cust_wa" placeholder="08xxxxxxx">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top:5px;">
                                <label>Jumlah</label>
                                <input type="number" name="jumlah" value="1" min="1">
                            </div>
                        </div>

                        <!-- 3. METODE PEMBAYARAN -->
                        <div style="margin-top:25px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                            <label style="font-size:11px; font-weight:800; color:var(--primary);">3. METODE PEMBAYARAN</label>
                            <div class="pay-grid">
                                
                                <label class="pay-card">
                                    <input type="radio" name="metode_bayar" value="DANA" required>
                                    <img src="https://tse4.mm.bing.net/th/id/OIP.-YC0LT_rwD2KmdAMmHDYmwHaHa?pid=Api&P=0&h=220" alt="DANA">
                                </label>

                                <label class="pay-card">
                                    <input type="radio" name="metode_bayar" value="GOPAY">
                                    <img src="https://tse1.mm.bing.net/th/id/OIP.RvVW0QxAyIp5eonB9QzFoQHaHa?pid=Api&P=0&h=220" alt="GOPAY">
                                </label>

                                <label class="pay-card">
                                    <input type="radio" name="metode_bayar" value="SHOPEEPAY">
                                    <img src="https://tse4.mm.bing.net/th/id/OIP.-ZUGFWs4sP7mkOS3p2R1cAHaEJ?pid=Api&P=0&h=220" alt="SPAY">
                                </label>

                                <label class="pay-card">
                                    <input type="radio" name="metode_bayar" value="OVO">
                                    <img src="https://tse2.mm.bing.net/th/id/OIP.hg6Vvx7_DxWXXuo5_k8i1AHaHZ?pid=Api&P=0&h=220" alt="OVO">
                                </label>

                                <label class="pay-card">
                                    <input type="radio" name="metode_bayar" value="SEABANK">
                                    <b style="font-size: 13px; letter-spacing: 1px;">SeaBank</b>
                                </label>

                                <label class="pay-card">
                                    <input type="radio" name="metode_bayar" value="QRIS">
                                    <b style="font-size: 14px; letter-spacing: 1.5px; font-weight: 900;">QRIS</b>
                                </label>

                            </div>
                        </div>

                        <button type="submit" name="beli_barang" class="btn-buy" id="btnBuy" disabled style="margin-top: 20px;">Pilih Produk & Nominal</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const btnMenu = document.getElementById('menuToggle');

        btnMenu.onclick = () => { sidebar.classList.add('show'); overlay.classList.add('show'); }
        overlay.onclick = () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }

        function updateClock() {
            document.getElementById('realtimeClock').innerText = new Date().toLocaleTimeString('id-ID', { hour12: false });
        }
        setInterval(updateClock, 1000); updateClock();

        document.getElementById('gameSearch').addEventListener('input', function() {
            let keyword = this.value.toLowerCase();
            document.querySelectorAll('.game-card').forEach(card => {
                card.style.display = card.getAttribute('data-name').includes(keyword) ? "block" : "none";
            });
        });

        function selectGame(kode) {
            document.querySelectorAll('.game-card').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.item-grid').forEach(g => g.classList.remove('show'));
            document.getElementById('card-'+kode).classList.add('active');
            document.getElementById('grid-'+kode).classList.add('show');
            if(window.innerWidth < 992) { document.getElementById('order-section').scrollIntoView({behavior: 'smooth'}); }
        }

        window.onload = () => selectGame('genshin');

        function activateBtn() {
            const btn = document.getElementById('btnBuy');
            btn.disabled = false; btn.classList.add('active'); btn.innerText = "KONFIRMASI BELI 🚀";
        }

        <?php if(isset($_SESSION['pesan'])): ?>
            Swal.fire({
                icon: '<?php echo ($_SESSION['pesan'] == "sukses" ? "success" : "error"); ?>',
                title: '<?php echo ($_SESSION['pesan'] == "sukses" ? "Transaksi Berhasil" : "Gagal"); ?>',
                text: '<?php echo ($_SESSION['pesan'] == "sukses" ? "Pesanan sedang diproses admin." : "Stok tidak mencukupi!"); ?>',
                background: '#06090F', color: '#fff', timer: 2000, showConfirmButton: false
            });
            <?php unset($_SESSION['pesan']); ?>
        <?php endif; ?>
    </script>
</body>
</html>