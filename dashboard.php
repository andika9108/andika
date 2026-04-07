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
$db_name = "umamusume_db"; // Sesuaikan nama database lo

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Koneksi gagal! Pastikan XAMPP nyala."); }

// Buat tabel otomatis jika belum ada (STRUKTUR TETAP SAMA)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(100) NOT NULL,
    stok INT NOT NULL,
    harga INT NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pembeli VARCHAR(50) NOT NULL,
    nama_produk VARCHAR(100) NOT NULL,
    jumlah INT NOT NULL,
    total_harga INT NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==========================================
// DATA PRODUK BAWAAN (SEKARANG JAUH LEBIH BANYAK!)
// ==========================================
$cek_isi = mysqli_query($conn, "SELECT COUNT(*) as jml FROM products");
if (mysqli_fetch_assoc($cek_isi)['jml'] == 0) {
    // Array data produk baru (Genshin, HSR, Uma, MLBB, FF)
    $default_products = [
        // --- GENSHIN IMPACT ---
        ['Genshin - 60 Genesis Crystals', 200, 16000],
        ['Genshin - Blessing of the Welkin Moon', 500, 79000],
        ['Genshin - 1980+260 Genesis Crystals', 50, 479000],
        
        // --- HONKAI: STAR RAIL ---
        ['HSR - 60 Oneiric Shards', 150, 16000],
        ['HSR - Express Supply Pass', 300, 79000],
        ['HSR - 1980+260 Oneiric Shards', 80, 479000],
        
        // --- UMA MUSUME (JP/KR) ---
        ['Uma - 50 Jewels', 1000, 15000],
        ['Uma - Daily Jewel Pack', 800, 75000],
        ['Uma - 1500 Jewels (Paid)', 100, 450000],
        
        // --- MOBILE LEGENDS ---
        ['MLBB - 5 Diamonds', 10000, 2000],
        ['MLBB - Weekly Diamond Pass', 2000, 29000],
        ['MLBB - Starlight Member', 500, 149000],
        ['MLBB - 966+149 Diamonds', 200, 299000],

        // --- FREE FIRE ---
        ['FF - 50 Diamonds', 5000, 8000],
        ['FF - Weekly Membership', 1000, 39000],
        ['FF - 1060 Diamonds', 300, 159000]
    ];

    // Query INSERT massal
    $query_insert = "INSERT INTO products (nama_produk, stok, harga) VALUES ";
    foreach ($default_products as $key => $p) {
        $query_insert .= "('" . mysqli_real_escape_string($conn, $p[0]) . "', {$p[1]}, {$p[2]})";
        if ($key < count($default_products) - 1) {
            $query_insert .= ", ";
        }
    }
    mysqli_query($conn, $query_insert);
}

$pesan = "";

// ==========================================
// 2. LOGIKA TRANSAKSI (TIDAK BERUBAH)
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
// 3. AMBIL DATA & FORMATTING "---" (TIDAK BERUBAH)
// ==========================================
$produk_query = mysqli_query($conn, "SELECT * FROM products ORDER BY nama_produk ASC"); // Diurutkan biar rapi
$transaksi_query = mysqli_query($conn, "SELECT * FROM transactions ORDER BY tanggal DESC LIMIT 10");
$jumlah_transaksi = mysqli_num_rows($transaksi_query);

$stat_tx = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total_tx, SUM(total_harga) as pendapatan FROM transactions"));
$stat_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok) as total_stok FROM products"));

function formatData($nilai, $is_currency = false) {
    if (empty($nilai) || $nilai == 0) return "<span class='strip-tiga'>---</span>";
    return $is_currency ? "Rp " . number_format($nilai, 0, ',', '.') : number_format($nilai);
}

// ==========================================
// FUNGSI LOGIKA IKON PRODUK (Inovasi Baru!)
// ==========================================
function getProductIcon($nama_produk) {
    $nama_produk = strtolower($nama_produk);
    // Tentukan URL ikon berdasarkan nama gim
    if (strpos($nama_produk, 'genshin') !== false) {
        return 'https://ui-avatars.com/api/?name=GI&background=22C55E&color=fff&rounded=true&bold=true';
    } elseif (strpos($nama_produk, 'hsr') !== false) {
        return 'https://ui-avatars.com/api/?name=HSR&background=3B82F6&color=fff&rounded=true&bold=true';
    } elseif (strpos($nama_produk, 'uma') !== false) {
        return 'https://ui-avatars.com/api/?name=UMA&background=ff7a00&color=fff&rounded=true&bold=true';
    } elseif (strpos($nama_produk, 'mlbb') !== false) {
        return 'https://ui-avatars.com/api/?name=ML&background=8e44ad&color=fff&rounded=true&bold=true';
    } elseif (strpos($nama_produk, 'ff') !== false) {
        return 'https://ui-avatars.com/api/?name=FF&background=e74c3c&color=fff&rounded=true&bold=true';
    } else {
        return 'https://ui-avatars.com/api/?name=TP&background=ddd&color=333&rounded=true&bold=true'; // Default
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Digital Top-Up Hub</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ==========================================
           REFRESH VISUAL: Premium Silence Suzuka Theme
           ========================================== */
        :root {
            --bg-base: #06090F;         /* Lebih gelap, lebih 'malam' */
            --bg-surface: rgba(18, 24, 38, 0.6); /* Lebih transparan */
            --border-color: rgba(255, 255, 255, 0.06); /* Lebih tipis */
            --primary: #22C55E;         /* Hijau Suzuka */
            --primary-glow: rgba(34, 197, 94, 0.25);
            --accent: #ff7a00;          /* Oranye Suzuka */
            --text-main: #FFFFFF;       
            --text-muted: #808B9F;      /* Abu-abu dengan tone biru */
            --danger: #EF4444;
            --radius-lg: 24px;          /* Lebih membulat */
            --radius-md: 14px;          /* Lebih membulat */
            --blur: blur(20px);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body { 
            background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden;
            background-image: radial-gradient(circle at 10% 50%, rgba(34, 197, 94, 0.05), transparent 20%),
                              radial-gradient(circle at 90% 30%, rgba(255, 122, 0, 0.03), transparent 20%);
        }

        ::-webkit-scrollbar { width: 6px; height: 6px;}
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1C2433; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #333C4F; }

        .strip-tiga { color: #3F4857; font-weight: 400; letter-spacing: 2px; }

        /* ==========================================
           SIDEBAR OFF-CANVAS (TETAP SAMA)
           ========================================== */
        .sidebar {
            position: fixed; left: -320px; top: 0; bottom: 0; width: 300px;
            background: rgba(10, 15, 26, 0.9); backdrop-filter: var(--blur); -webkit-backdrop-filter: var(--blur);
            border-right: 1px solid var(--border-color); padding: 30px 20px; 
            display: flex; flex-direction: column; z-index: 1000; transition: var(--transition);
            box-shadow: 15px 0 40px rgba(0,0,0,0.6);
        }
        .sidebar.show { left: 0; }

        .sidebar-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
            z-index: 999; opacity: 0; visibility: hidden; transition: 0.4s ease;
        }
        .sidebar-overlay.show { opacity: 1; visibility: visible; cursor: pointer; }

        .logo { display: flex; align-items: center; gap: 15px; margin-bottom: 35px; padding: 0 10px; }
        .logo-icon { 
            width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), var(--accent)); 
            border-radius: var(--radius-md); display: flex; justify-content: center; align-items: center; 
            font-size: 20px; color: white; box-shadow: 0 5px 25px rgba(34, 197, 94, 0.2);
        }
        .logo-text h2 { font-size: 22px; font-weight: 700; color: var(--text-main); line-height: 1.1; }
        .logo-text span { font-size: 11px; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; }

        .menu { flex: 1; display: flex; flex-direction: column; gap: 7px; }
        .menu a {
            display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: var(--text-muted); 
            text-decoration: none; border-radius: var(--radius-md); font-weight: 500; font-size: 14px; transition: 0.3s;
        }
        .menu a i { font-size: 18px; width: 24px; text-align: center; }
        .menu a:hover, .menu a.active { background: rgba(34, 197, 94, 0.08); color: var(--primary); box-shadow: inset 3px 0 0 var(--primary); }
        .menu a.logout { margin-top: auto; color: var(--danger); font-weight: 600;}
        .menu a.logout:hover { background: rgba(239, 68, 68, 0.1); box-shadow: inset 3px 0 0 var(--danger); }

        /* GAMBAR SUZUKA SIDEBAR (Updated URL) */
        .sidebar-img {
            margin-top: 15px; height: 170px; border-radius: var(--radius-md);
            background: url('https://i.pinimg.com/736x/cb/ac/b4/cbacb415a97d75a61e721d6076b6680a.jpg') center top/cover;
            border: 1px solid var(--border-color); box-shadow: 0 5px 20px rgba(0,0,0,0.4);
            position: relative;
        }
        .sidebar-img::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 50%;
            background: linear-gradient(to top, rgba(10, 15, 26, 0.85), transparent);
        }

        /* MAIN CONTENT & TOPBAR (TETAP SAMA) */
        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; width: 100%; }
        .topbar { 
            position: sticky; top: 0; z-index: 5; background: rgba(6, 9, 15, 0.85); backdrop-filter: var(--blur);
            padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color);
        }
        
        .topbar-left { display: flex; align-items: center; gap: 20px; }
        .btn-menu {
            background: rgba(255,255,255,0.04); border: 1px solid var(--border-color);
            color: var(--text-main); font-size: 20px; width: 45px; height: 45px; border-radius: 12px;
            cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center;
        }
        .btn-menu:hover { background: var(--primary); color: #000; border-color: var(--primary); box-shadow: 0 0 15px var(--primary-glow); }

        .topbar-title h3 { font-size: 18px; font-weight: 600; letter-spacing: 0.5px; }
        .topbar-title p { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
        
        .user-profile { 
            display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); 
            padding: 7px 18px 7px 7px; border-radius: 50px; border: 1px solid var(--border-color); cursor: pointer; transition: 0.3s;
        }
        .user-profile:hover { border-color: var(--primary); background: rgba(34, 197, 94, 0.06); }
        .user-profile img { width: 34px; height: 34px; border-radius: 50%; }
        .user-profile span { font-size: 13px; font-weight: 600; letter-spacing: 0.5px; }

        .content { padding: 40px; max-width: 1400px; margin: 0 auto; width: 100%; }

        /* WELCOME BANNER SUZUKA (TETAP SAMA) */
        .welcome-banner {
            background: linear-gradient(to right, rgba(6, 9, 15, 1) 15%, rgba(6, 9, 15, 0.3)),
                        url('https://i.pinimg.com/736x/77/b0/0c/77b00ca36e6517bb8f1ffb851457497d.jpg') center 20%/cover;
            height: 200px; border-radius: var(--radius-lg); margin-bottom: 40px;
            display: flex; align-items: center; padding: 40px; border: 1px solid var(--border-color);
            box-shadow: 0 10px 40px rgba(0,0,0,0.4); overflow: hidden;
        }
        .banner-content h2 { font-size: 28px; font-weight: 700; color: var(--primary); margin-bottom: 5px; letter-spacing: 0.5px; text-shadow: 0 2px 10px rgba(0,0,0,0.3);}
        .banner-content p { color: rgba(255,255,255,0.9); font-size: 14px; }

        /* STATS CARDS (REFRESH VISUAL) */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .stat-card { 
            background: var(--bg-surface); backdrop-filter: var(--blur); padding: 25px 30px; border-radius: var(--radius-lg); 
            border: 1px solid var(--border-color); display: flex; align-items: center; gap: 25px; transition: 0.4s;
        }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(255,255,255,0.15); box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
        .stat-icon { width: 60px; height: 60px; border-radius: 16px; background: rgba(34, 197, 94, 0.1); color: var(--primary); display: flex; justify-content: center; align-items: center; font-size: 24px; border: 1px solid rgba(34, 197, 94, 0.1); }
        .stat-card:nth-child(2) .stat-icon { background: rgba(255, 122, 0, 0.1); color: var(--accent); border-color: rgba(255, 122, 0, 0.1); }
        .stat-card:nth-child(3) .stat-icon { background: rgba(59, 130, 246, 0.1); color: #3B82F6; border-color: rgba(59, 130, 246, 0.1); }

        .stat-info h4 { color: var(--text-muted); font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 7px; }
        .stat-info h2 { font-size: 30px; font-weight: 700; letter-spacing: 1px; line-height: 1.1; }

        /* GRID CONTENT (TETAP SAMA) */
        .grid-layout { display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px; margin-bottom: 40px; }
        .box { 
            background: var(--bg-surface); backdrop-filter: var(--blur); padding: 35px; 
            border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .box-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; }
        .box-header i { color: var(--primary); font-size: 18px; padding: 12px; background: rgba(34, 197, 94, 0.1); border-radius: 12px; border: 1px solid rgba(34, 197, 94, 0.1); }
        .box-header h3 { font-size: 17px; font-weight: 600; letter-spacing: 0.5px; }

        /* FORM POS (TETAP SAMA) */
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; color: var(--text-muted); font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group select, .form-group input {
            width: 100%; padding: 16px 18px; background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); 
            color: var(--text-main); border-radius: var(--radius-md); outline: none; font-size: 14px; transition: 0.3s;
        }
        .form-group select:focus, .form-group input:focus { border-color: var(--primary); background: rgba(34, 197, 94, 0.03); }
        .form-group select option { background: #0A0F1A; }
        
        .btn-buy { 
            width: 100%; background: var(--primary); color: #000; padding: 17px; border: none; 
            border-radius: var(--radius-md); font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s;
            display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 15px;
        }
        .btn-buy:hover { background: var(--primary-hover); box-shadow: 0 0 20px var(--primary-glow); transform: translateY(-3px); }

        /* TABLE (NEW: Ikon Produk!) */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 18px 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 13px; vertical-align: middle; }
        th { color: var(--text-muted); font-weight: 500; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; padding-bottom: 20px; }
        tr:hover td { background: rgba(255,255,255,0.03); }
        
        /* Ikon Produk di Tabel */
        .table-item { display: flex; align-items: center; gap: 15px; }
        .prod-icon { width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--border-color); }
        
        .badge { padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; background: rgba(34, 197, 94, 0.1); color: var(--primary); border: 1px solid rgba(34, 197, 94, 0.2); }

        /* TOAST ALERT (TETAP SAMA) */
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 9999; }
        .toast {
            display: flex; align-items: center; gap: 18px; padding: 18px 26px; border-radius: var(--radius-md);
            background: var(--bg-surface); backdrop-filter: var(--blur); border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 15px 50px rgba(0,0,0,0.6); color: white; font-size: 13px; line-height: 1.5;
            animation: slideInRight 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards, fadeOut 0.5s ease 4.5s forwards;
        }
        .toast.success i { color: var(--primary); font-size: 26px; }
        .toast.error i { color: var(--danger); font-size: 26px; }
        
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php if($pesan != "") echo "<div class='toast-container'>$pesan</div>"; ?>

    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-bolt"></i></div>
            <div class="logo-text">
                <h2>Suzuka</h2>
                <span>Premium Store</span>
            </div>
        </div>
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
            <div class="topbar-left">
                <button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div class="topbar-title">
                    <h3>Premium Hub Overview</h3>
                    <p>Manage digital top-ups for multi-universe games securely.</p>
                </div>
            </div>
            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=22C55E&color=fff&bold=true" alt="User">
                <span>Trainer <?php echo htmlspecialchars($username); ?></span>
            </div>
        </header>

        <div class="content">

            <div class="welcome-banner">
                <div class="banner-content">
                    <h2>Race Forward, Trainer!</h2>
                    <p>Waktunya meningkatkan performa. Kelola top-up multi-semesta tanpa batas di Suzuka Store.</p>
                </div>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-info">
                        <h4>Total Orders</h4>
                        <h2><?php echo formatData($stat_tx['total_tx']); ?></h2>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    <div class="stat-info">
                        <h4>Gross Revenue</h4>
                        <h2><?php echo formatData($stat_tx['pendapatan'], true); ?></h2>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-cubes"></i></div>
                    <div class="stat-info">
                        <h4>Active Stock</h4>
                        <h2><?php echo formatData($stat_stok['total_stok']); ?></h2>
                    </div>
                </div>
            </div>

            <div class="grid-layout">
                <div class="box">
                    <div class="box-header">
                        <i class="fas fa-cash-register"></i>
                        <h3>Multi-Universe Kasir POS</h3>
                    </div>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label>Pilih Item Top-Up (Genshin, Uma, HSR, ML, FF)</label>
                            <select name="id_produk" required>
                                <option value="" disabled selected>-- Pilih Semesta & Item --</option>
                                <?php 
                                mysqli_data_seek($produk_query, 0); 
                                while($row = mysqli_fetch_assoc($produk_query)): ?>
                                    <option value="<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['nama_produk']); ?> — Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity (Satuan Paket)</label>
                            <input type="number" name="jumlah" min="1" value="1" required>
                        </div>
                        <button type="submit" name="beli_barang" class="btn-buy">
                            <i class="fas fa-bolt"></i> Complete Transaction
                        </button>
                    </form>
                </div>

                <div class="box">
                    <div class="box-header">
                        <i class="fas fa-layer-group"></i>
                        <h3>Gim Inventory Stok</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Item Produk & Gim</th>
                                    <th>Harga Unit</th>
                                    <th>Sisa Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($produk_query, 0);
                                while($p = mysqli_fetch_assoc($produk_query)): ?>
                                <tr>
                                    <td>
                                        <div class="table-item">
                                            <img src="<?php echo getProductIcon($p['nama_produk']); ?>" alt="Game Icon" class="prod-icon">
                                            <span style="font-weight: 500; color: #E5E7EB;"><?php echo htmlspecialchars($p['nama_produk']); ?></span>
                                        </div>
                                    </td>
                                    <td style="color: var(--text-muted);">Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></td>
                                    <td><span class="badge"><?php echo formatData($p['stok']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header">
                    <i class="fas fa-receipt"></i>
                    <h3>Recent Transactions Log</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Customer ID</th>
                                <th>Product Item</th>
                                <th>Qty</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($jumlah_transaksi > 0): ?>
                                <?php while($tx = mysqli_fetch_assoc($transaksi_query)): ?>
                                <tr>
                                    <td style="color: var(--text-muted);"><?php echo date('d M Y, H:i', strtotime($tx['tanggal'])); ?></td>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($tx['pembeli']); ?></td>
                                    <td>
                                        <div class="table-item">
                                            <img src="<?php echo getProductIcon($tx['nama_produk']); ?>" alt="Game Icon" class="prod-icon">
                                            <span style="color: var(--primary); font-weight: 500;"><?php echo htmlspecialchars($tx['nama_produk']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $tx['jumlah']; ?>x</td>
                                    <td style="font-weight: 600;">Rp <?php echo number_format($tx['total_harga'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td class="strip-tiga">---</td>
                                    <td class="strip-tiga">---</td>
                                    <td class="strip-tiga">---</td>
                                    <td class="strip-tiga">---</td>
                                    <td class="strip-tiga">---</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        const btnMenu = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        btnMenu.addEventListener('click', function() {
            sidebar.classList.add('show');
            overlay.classList.add('show');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    </script>
</body>
</html>