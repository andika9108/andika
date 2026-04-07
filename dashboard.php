<?php
session_start();

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// ==========================================
// 1. KONEKSI DATABASE & AUTO-CREATE
// ==========================================
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "umamusume_db";

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Koneksi gagal! Pastikan XAMPP nyala."); }

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY, nama_produk VARCHAR(100) NOT NULL, stok INT NOT NULL, harga INT NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY, pembeli VARCHAR(50) NOT NULL, nama_produk VARCHAR(100) NOT NULL,
    jumlah INT NOT NULL, total_harga INT NOT NULL, tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Data Bawaan Jika Kosong
$cek_isi = mysqli_query($conn, "SELECT COUNT(*) as jml FROM products");
$data_isi = mysqli_fetch_assoc($cek_isi);
if ($data_isi['jml'] < 10) {
    mysqli_query($conn, "TRUNCATE TABLE products");
    $default_products = [
        ['Genshin - 60 Genesis Crystals', 200, 16000], ['Genshin - Blessing of Welkin', 500, 79000], ['Genshin - 1980+260 Crystals', 50, 479000],
        ['HSR - 60 Oneiric Shards', 150, 16000], ['HSR - Express Supply Pass', 300, 79000], ['HSR - 1980+260 Oneiric Shards', 80, 479000],
        ['Uma - 50 Jewels', 1000, 15000], ['Uma - Daily Jewel Pack', 800, 75000], ['Uma - 1500 Jewels', 100, 450000],
        ['MLBB - 5 Diamonds', 10000, 2000], ['MLBB - Weekly Diamond Pass', 2000, 29000], ['MLBB - Starlight Member', 500, 149000], ['MLBB - 966+149 Diamonds', 200, 299000],
        ['FF - 50 Diamonds', 5000, 8000], ['FF - Weekly Membership', 1000, 39000], ['FF - 1060 Diamonds', 300, 159000]
    ];
    $query_insert = "INSERT INTO products (nama_produk, stok, harga) VALUES ";
    foreach ($default_products as $key => $p) {
        $query_insert .= "('" . mysqli_real_escape_string($conn, $p[0]) . "', {$p[1]}, {$p[2]})" . ($key < count($default_products) - 1 ? ", " : "");
    }
    mysqli_query($conn, $query_insert);
}

$pesan = "";

// ==========================================
// 2. LOGIKA TRANSAKSI (TETAP SAMA 100%)
// ==========================================
if (isset($_POST['beli_barang'])) {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah_beli = (int)$_POST['jumlah'];

    $cek_produk = mysqli_query($conn, "SELECT * FROM products WHERE id='$id_produk'");
    if ($data_produk = mysqli_fetch_assoc($cek_produk)) {
        if ($data_produk['stok'] >= $jumlah_beli && $jumlah_beli > 0) {
            $total_harga = $data_produk['harga'] * $jumlah_beli;
            $sisa_stok = $data_produk['stok'] - $jumlah_beli;
            $nama_produk = $data_produk['nama_produk'];

            mysqli_query($conn, "UPDATE products SET stok='$sisa_stok' WHERE id='$id_produk'");
            mysqli_query($conn, "INSERT INTO transactions (pembeli, nama_produk, jumlah, total_harga) 
                         VALUES ('$username', '$nama_produk', '$jumlah_beli', '$total_harga')");

            $pesan = "<div class='toast success'><i class='fas fa-check-circle'></i> <div><b>Transaksi Sukses!</b><br>$jumlah_beli x $nama_produk ditambahkan.</div></div>";
        } else {
            $pesan = "<div class='toast error'><i class='fas fa-exclamation-triangle'></i> <div><b>Transaksi Gagal!</b><br>Stok tidak cukup atau jumlah tidak valid.</div></div>";
        }
    }
}

// ==========================================
// 3. LOGIKA GROUPING DATA UNTUK UI BARU
// ==========================================
$produk_query = mysqli_query($conn, "SELECT * FROM products ORDER BY harga ASC");
$transaksi_query = mysqli_query($conn, "SELECT * FROM transactions ORDER BY tanggal DESC LIMIT 10");
$jumlah_transaksi = mysqli_num_rows($transaksi_query);
$stat_tx = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total_tx, SUM(total_harga) as pendapatan FROM transactions"));
$stat_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok) as total_stok FROM products"));

function formatData($nilai, $is_currency = false) {
    if (empty($nilai) || $nilai == 0) return "<span class='strip-tiga'>---</span>";
    return $is_currency ? "Rp " . number_format($nilai, 0, ',', '.') : number_format($nilai);
}

// Detail Informasi Tiap Game
$game_info = [
    'genshin' => ['nama' => 'Genshin Impact', 'img' => 'https://play-lh.googleusercontent.com/So91qs_eRRrakK-EHKZXu8ktJWnSQnc1kE12i9l-BvyV1VvP2X6Zk1y0kU96T16ZpQ=w240-h480-rw'],
    'hsr'     => ['nama' => 'Honkai Star Rail', 'img' => 'https://play-lh.googleusercontent.com/s1K8q7iO9ZtE7a2u14sZqYpAITRxy_c4O0h_01O2tV-RngGkGj0oQz40e2_3T_R11w=w240-h480-rw'],
    'uma'     => ['nama' => 'Uma Musume', 'img' => 'https://play-lh.googleusercontent.com/yU4lXl81dZIfLrt1iT_ZzO8mXXp0LgNlBqV8p02J9OemvUqXnFm2z3EwzT75wU1bKw=w240-h480-rw'],
    'mlbb'    => ['nama' => 'Mobile Legends', 'img' => 'https://play-lh.googleusercontent.com/b9hQ-31b0uE6i_1523R17o4uE9f2D830w0jD_Nmb_4T5g_Zz_g2D0bX8S2A6g4w6=w240-h480-rw'],
    'ff'      => ['nama' => 'Free Fire', 'img' => 'https://play-lh.googleusercontent.com/1B-oQOer0P1eC_04wD6uO2cE5y5yEwGftJ4qOqJ6bC06H9j3Uu_S1Qv_4K9G9Yh8=w240-h480-rw']
];

// Pisahkan produk ke dalam kategori berdasarkan nama
$grouped_products = [];
while($r = mysqli_fetch_assoc($produk_query)) {
    $n = strtolower($r['nama_produk']);
    $cat = 'other';
    if(strpos($n, 'genshin') !== false) $cat = 'genshin';
    elseif(strpos($n, 'hsr') !== false) $cat = 'hsr';
    elseif(strpos($n, 'uma') !== false) $cat = 'uma';
    elseif(strpos($n, 'mlbb') !== false) $cat = 'mlbb';
    elseif(strpos($n, 'ff') !== false) $cat = 'ff';
    $grouped_products[$cat][] = $r;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Web Top-Up</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === (CSS TEMA TETAP SAMA DARI SEBELUMNYA) === */
        :root {
            --bg-base: #06090F; --bg-surface: rgba(18, 24, 38, 0.6); --border-color: rgba(255, 255, 255, 0.08); 
            --primary: #22C55E; --primary-glow: rgba(34, 197, 94, 0.25); --accent: #ff7a00;          
            --text-main: #FFFFFF; --text-muted: #808B9F; --danger: #EF4444;
            --radius-lg: 24px; --radius-md: 14px; --blur: blur(20px); --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; background-image: radial-gradient(circle at 10% 50%, rgba(34, 197, 94, 0.05), transparent 20%), radial-gradient(circle at 90% 30%, rgba(255, 122, 0, 0.03), transparent 20%); }
        ::-webkit-scrollbar { width: 6px; height: 6px;} ::-webkit-scrollbar-track { background: transparent; } ::-webkit-scrollbar-thumb { background: #1C2433; border-radius: 10px; }
        .strip-tiga { color: #3F4857; font-weight: 400; letter-spacing: 2px; }

        .sidebar { position: fixed; left: -320px; top: 0; bottom: 0; width: 300px; background: rgba(10, 15, 26, 0.9); backdrop-filter: var(--blur); border-right: 1px solid var(--border-color); padding: 30px 20px; display: flex; flex-direction: column; z-index: 1000; transition: var(--transition); box-shadow: 15px 0 40px rgba(0,0,0,0.6); }
        .sidebar.show { left: 0; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 999; opacity: 0; visibility: hidden; transition: 0.4s ease; }
        .sidebar-overlay.show { opacity: 1; visibility: visible; cursor: pointer; }
        .logo { display: flex; align-items: center; gap: 15px; margin-bottom: 35px; padding: 0 10px; }
        .logo-icon { width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: var(--radius-md); display: flex; justify-content: center; align-items: center; font-size: 20px; color: white; box-shadow: 0 5px 25px rgba(34, 197, 94, 0.2); }
        .logo-text h2 { font-size: 22px; font-weight: 700; color: var(--text-main); line-height: 1.1; }
        .logo-text span { font-size: 11px; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; }

        .menu { flex: 1; display: flex; flex-direction: column; gap: 7px; }
        .menu a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-md); font-weight: 500; font-size: 14px; transition: 0.3s; }
        .menu a i { font-size: 18px; width: 24px; text-align: center; }
        .menu a:hover, .menu a.active { background: rgba(34, 197, 94, 0.08); color: var(--primary); box-shadow: inset 3px 0 0 var(--primary); }
        .menu a.logout { margin-top: auto; color: var(--danger); font-weight: 600;}

        .sidebar-img { margin-top: 15px; height: 170px; border-radius: var(--radius-md); background: url('https://i.pinimg.com/736x/cb/ac/b4/cbacb415a97d75a61e721d6076b6680a.jpg') center top/cover; border: 1px solid var(--border-color); position: relative; }
        .sidebar-img::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 50%; background: linear-gradient(to top, rgba(10, 15, 26, 0.85), transparent); }

        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; width: 100%; }
        .topbar { position: sticky; top: 0; z-index: 5; background: rgba(6, 9, 15, 0.85); backdrop-filter: var(--blur); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .topbar-left { display: flex; align-items: center; gap: 20px; }
        .btn-menu { background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); color: var(--text-main); font-size: 20px; width: 45px; height: 45px; border-radius: 12px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
        .btn-menu:hover { background: var(--primary); color: #000; border-color: var(--primary); box-shadow: 0 0 15px var(--primary-glow); }
        .topbar-title h3 { font-size: 18px; font-weight: 600; letter-spacing: 0.5px; }
        .topbar-title p { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
        .user-profile { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 7px 18px 7px 7px; border-radius: 50px; border: 1px solid var(--border-color); cursor: pointer; transition: 0.3s; }
        .user-profile img { width: 34px; height: 34px; border-radius: 50%; }
        .user-profile span { font-size: 13px; font-weight: 600; letter-spacing: 0.5px; }

        .content { padding: 40px; max-width: 1400px; margin: 0 auto; width: 100%; }
        
        /* BANNER */
        .welcome-banner { background: linear-gradient(to right, rgba(6, 9, 15, 1) 15%, rgba(6, 9, 15, 0.3)), url('https://i.pinimg.com/736x/77/b0/0c/77b00ca36e6517bb8f1ffb851457497d.jpg') center 20%/cover; height: 180px; border-radius: var(--radius-lg); margin-bottom: 40px; display: flex; align-items: center; padding: 40px; border: 1px solid var(--border-color); box-shadow: 0 10px 40px rgba(0,0,0,0.4); }
        .banner-content h2 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 5px; }
        .banner-content p { color: rgba(255,255,255,0.9); font-size: 14px; }

        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: var(--bg-surface); backdrop-filter: var(--blur); padding: 25px 30px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 25px; transition: 0.4s; }
        .stat-icon { width: 60px; height: 60px; border-radius: 16px; background: rgba(34, 197, 94, 0.1); color: var(--primary); display: flex; justify-content: center; align-items: center; font-size: 24px; border: 1px solid rgba(34, 197, 94, 0.1); }
        .stat-info h4 { color: var(--text-muted); font-size: 12px; font-weight: 500; text-transform: uppercase; margin-bottom: 7px; }
        .stat-info h2 { font-size: 26px; font-weight: 700; line-height: 1.1; }

        .grid-layout { display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 40px; }
        .box { background: var(--bg-surface); backdrop-filter: var(--blur); padding: 35px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .box-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .box-header i { color: var(--primary); font-size: 18px; padding: 12px; background: rgba(34, 197, 94, 0.1); border-radius: 12px; border: 1px solid rgba(34, 197, 94, 0.1); }
        .box-header h3 { font-size: 17px; font-weight: 600; }

        /* ==========================================
           DESAIN BARU: PILIH GAME & KARTU ITEM (WEB TOP-UP)
           ========================================== */
        
        /* Step 1: Pilih Game (TABS) */
        .game-tabs { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 15px; margin-bottom: 10px; }
        .game-tab { 
            min-width: 120px; flex: 1; padding: 15px; background: rgba(0,0,0,0.4); border: 2px solid var(--border-color); 
            border-radius: var(--radius-md); display: flex; flex-direction: column; align-items: center; gap: 10px; 
            cursor: pointer; transition: var(--transition);
        }
        .game-tab img { width: 50px; height: 50px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); transition: 0.3s; }
        .game-tab span { font-size: 13px; font-weight: 600; text-align: center; color: var(--text-muted); }
        
        .game-tab:hover { background: rgba(255,255,255,0.05); }
        .game-tab.active { background: rgba(34, 197, 94, 0.1); border-color: var(--primary); box-shadow: 0 0 20px rgba(34,197,94,0.15); }
        .game-tab.active img { transform: scale(1.1); box-shadow: 0 5px 15px rgba(34,197,94,0.4); border: 2px solid var(--primary); }
        .game-tab.active span { color: var(--text-main); }

        /* Step 2: Pilih Item (KARTU RADIO) */
        .step-title { margin: 30px 0 15px 0; font-size: 16px; font-weight: 600; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .step-title span { background: var(--primary); color: #000; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; font-weight: 700; font-size: 13px; }
        
        .item-grid { display: none; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px; }
        .item-grid.show { display: grid; animation: fadeIn 0.4s ease; }

        .item-card { cursor: pointer; display: block; position: relative; }
        .item-card input[type="radio"] { display: none; }
        .item-content { 
            padding: 20px 15px; background: rgba(0,0,0,0.4); border: 2px solid var(--border-color); 
            border-radius: var(--radius-md); text-align: center; transition: 0.3s; height: 100%;
        }
        .item-content h4 { font-size: 14px; font-weight: 600; color: var(--text-main); margin-bottom: 8px; line-height: 1.3; }
        .item-content p { font-size: 15px; font-weight: 700; color: var(--primary); }
        .item-content .stok { font-size: 11px; color: var(--text-muted); margin-top: 10px; display: block;}

        .item-card input[type="radio"]:checked + .item-content { 
            border-color: var(--primary); background: rgba(34, 197, 94, 0.1); 
            box-shadow: 0 0 20px var(--primary-glow); transform: translateY(-5px);
        }
        .item-card input[type="radio"]:checked + .item-content h4 { color: var(--primary); }

        /* Step 3: Tombol Beli */
        .payment-section { margin-top: 30px; background: rgba(0,0,0,0.3); padding: 25px; border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; align-items: flex-end; gap: 20px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 10px; color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .form-group input { width: 100%; padding: 15px; background: #0A0F1A; border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); outline: none; font-size: 16px; font-weight: 600; }
        
        .btn-buy { 
            flex: 2; background: #334155; color: #94A3B8; padding: 16px; border: none; 
            border-radius: var(--radius-md); font-weight: 700; font-size: 16px; cursor: not-allowed; transition: 0.3s;
        }
        .btn-buy.active { background: var(--primary); color: #000; cursor: pointer; box-shadow: 0 10px 25px var(--primary-glow); }
        .btn-buy.active:hover { transform: translateY(-3px); }

        /* TABLE & TOASTS TETAP SAMA */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 18px 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 13px; }
        th { color: var(--text-muted); font-weight: 500; font-size: 11px; text-transform: uppercase; }
        .table-item { display: flex; align-items: center; gap: 15px; }
        .prod-icon { width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--border-color); }
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 9999; }
        .toast { display: flex; align-items: center; gap: 18px; padding: 18px 26px; border-radius: var(--radius-md); background: var(--bg-surface); backdrop-filter: var(--blur); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 15px 50px rgba(0,0,0,0.6); color: white; font-size: 13px; animation: slideInRight 0.5s forwards, fadeOut 0.5s ease 4.5s forwards; }
        .toast.success i { color: var(--primary); font-size: 26px; }
        .toast.error i { color: var(--danger); font-size: 26px; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if($pesan != "") echo "<div class='toast-container'>$pesan</div>"; ?>

    <aside class="sidebar" id="sidebar">
        <!-- Sidebar TETAP SAMA -->
        <div class="logo"><div class="logo-icon"><i class="fas fa-bolt"></i></div><div class="logo-text"><h2>Suzuka</h2><span>Premium Store</span></div></div>
        <nav class="menu">
            <a href="#" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="#"><i class="fas fa-box-open"></i> Inventory</a>
            <a href="#"><i class="fas fa-receipt"></i> Transactions</a>
            <a href="#"><i class="fas fa-cog"></i> Settings</a>
            <div class="sidebar-img"></div>
            <a href="index.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout Session</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <!-- Topbar TETAP SAMA -->
            <div class="topbar-left"><button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button><div class="topbar-title"><h3>Top-Up Station</h3><p>Fast, Secure, and Reliable.</p></div></div>
            <div class="user-profile"><img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=22C55E&color=fff&bold=true" alt="User"><span>Trainer <?php echo htmlspecialchars($username); ?></span></div>
        </header>

        <div class="content">

            <!-- Stats (Diringkas agar hemat tempat) -->
            <div class="stats">
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-shopping-cart"></i></div><div class="stat-info"><h4>Total Orders</h4><h2><?php echo formatData($stat_tx['total_tx']); ?></h2></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-wallet"></i></div><div class="stat-info"><h4>Gross Revenue</h4><h2><?php echo formatData($stat_tx['pendapatan'], true); ?></h2></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-cubes"></i></div><div class="stat-info"><h4>Active Stock</h4><h2><?php echo formatData($stat_stok['total_stok']); ?></h2></div></div>
            </div>

            <div class="grid-layout">
                <!-- ==========================================
                     FORM KASIR WEB TOP-UP BARU!
                     ========================================== -->
                <div class="box">
                    <div class="box-header">
                        <i class="fas fa-store"></i>
                        <h3>Web Top-Up Kasir</h3>
                    </div>

                    <form action="" method="POST" id="topupForm">
                        
                        <!-- STEP 1: PILIH GAME -->
                        <div class="step-title"><span>1</span> Pilih Game Tujuan</div>
                        <div class="game-tabs">
                            <?php foreach($game_info as $kode => $info): ?>
                                <div class="game-tab" onclick="selectGame('<?php echo $kode; ?>')" id="tab-<?php echo $kode; ?>">
                                    <img src="<?php echo $info['img']; ?>" alt="<?php echo $info['nama']; ?>">
                                    <span><?php echo $info['nama']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- STEP 2: PILIH NOMINAL/ITEM (GRID RADIO) -->
                        <div class="step-title"><span>2</span> Pilih Nominal Top-Up</div>
                        
                        <!-- Print div container untuk setiap game (di-hide via CSS awal) -->
                        <?php foreach($game_info as $kode => $info): ?>
                            <div class="item-grid" id="grid-<?php echo $kode; ?>">
                                <?php 
                                // Jika kategori ini ada produknya
                                if(isset($grouped_products[$kode])): 
                                    foreach($grouped_products[$kode] as $item): 
                                ?>
                                        <label class="item-card">
                                            <!-- INI KUNCI UTAMANYA: Input Radio menggantikan Select Option! -->
                                            <input type="radio" name="id_produk" value="<?php echo $item['id']; ?>" onchange="enableBuyButton()" required>
                                            <div class="item-content">
                                                <h4><?php echo htmlspecialchars($item['nama_produk']); ?></h4>
                                                <p>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></p>
                                                <span class="stok"><i class="fas fa-box"></i> Sisa Stok: <?php echo $item['stok']; ?></span>
                                            </div>
                                        </label>
                                <?php 
                                    endforeach; 
                                else: 
                                ?>
                                    <p style="color:var(--text-muted); font-size:14px; text-align:center; grid-column: 1 / -1; padding:20px;">Belum ada item tersedia untuk game ini.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- STEP 3: JUMLAH & BAYAR -->
                        <div class="payment-section">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Jumlah Paket Beli</label>
                                <input type="number" name="jumlah" min="1" value="1" required>
                            </div>
                            <button type="submit" name="beli_barang" class="btn-buy" id="btnBuy" disabled>
                                <i class="fas fa-lock"></i> Pilih Item Dulu
                            </button>
                        </div>
                    </form>
                </div>

                <!-- RIWAYAT TRANSAKSI (Dipindah ke bawah POS biar UI Web banget) -->
                <div class="box">
                    <div class="box-header">
                        <i class="fas fa-history"></i>
                        <h3>Recent Transactions Log</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Product Item</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($jumlah_transaksi > 0): ?>
                                    <?php while($tx = mysqli_fetch_assoc($transaksi_query)): 
                                        // Cari ikon untuk transaksi ini
                                        $n = strtolower($tx['nama_produk']);
                                        $icn = $game_info['ff']['img']; // Default FF
                                        if(strpos($n, 'genshin') !== false) $icn = $game_info['genshin']['img'];
                                        elseif(strpos($n, 'hsr') !== false) $icn = $game_info['hsr']['img'];
                                        elseif(strpos($n, 'uma') !== false) $icn = $game_info['uma']['img'];
                                        elseif(strpos($n, 'mlbb') !== false) $icn = $game_info['mlbb']['img'];
                                    ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?php echo htmlspecialchars($tx['pembeli']); ?> <br><span style="color: var(--text-muted); font-size: 11px;"><?php echo date('d M, H:i', strtotime($tx['tanggal'])); ?></span></td>
                                        <td>
                                            <div class="table-item">
                                                <img src="<?php echo $icn; ?>" alt="Game Icon" class="prod-icon">
                                                <span style="color: var(--text-main); font-weight: 500;"><?php echo htmlspecialchars($tx['nama_produk']); ?> <br><span style="color: var(--text-muted); font-size: 11px;">Qty: <?php echo $tx['jumlah']; ?>x</span></span>
                                            </div>
                                        </td>
                                        <td style="color: var(--primary); font-weight: 600;">Rp <?php echo number_format($tx['total_harga'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td class="strip-tiga">---</td><td class="strip-tiga">---</td><td class="strip-tiga">---</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- SCRIPT INTERAKTIF (BARU!) -->
    <script>
        // --- Logic Sidebar ---
        const btnMenu = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        btnMenu.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });

        // --- Logic Pilih Game (TABS) ---
        function selectGame(gameCode) {
            // 1. Matikan semua tab yang aktif
            document.querySelectorAll('.game-tab').forEach(tab => tab.classList.remove('active'));
            // 2. Sembunyikan semua grid item
            document.querySelectorAll('.item-grid').forEach(grid => grid.classList.remove('show'));
            
            // 3. Nyalakan tab yang diklik & munculkan grid yang sesuai
            document.getElementById('tab-' + gameCode).classList.add('active');
            document.getElementById('grid-' + gameCode).classList.add('show');

            // 4. Reset pilihan radio button & matikan tombol Beli
            document.querySelectorAll('input[type="radio"]').forEach(radio => radio.checked = false);
            disableBuyButton();
        }

        // --- Logic Tombol Beli ---
        const btnBuy = document.getElementById('btnBuy');
        function enableBuyButton() {
            btnBuy.disabled = false;
            btnBuy.classList.add('active');
            btnBuy.innerHTML = '<i class="fas fa-bolt"></i> Beli Sekarang';
        }
        function disableBuyButton() {
            btnBuy.disabled = true;
            btnBuy.classList.remove('active');
            btnBuy.innerHTML = '<i class="fas fa-lock"></i> Pilih Item Dulu';
        }

        // Auto-select game pertama (Genshin) saat web diload
        window.onload = function() {
            selectGame('genshin');
        };
    </script>
</body>
</html>