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
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "umamusume_db";

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Koneksi gagal!"); }

$pesan = "";

// ==========================================
// 2. LOGIKA HAPUS & BERSIHKAN LOG
// ==========================================
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM transactions WHERE id='$id'");
    $_SESSION['pesan'] = "<div class='toast success'><i class='fas fa-trash'></i> <div><b>Dihapus!</b><br>Data transaksi telah dibuang.</div></div>";
    header("Location: transactions.php"); exit();
}

if (isset($_POST['clear_all'])) {
    mysqli_query($conn, "TRUNCATE TABLE transactions");
    $_SESSION['pesan'] = "<div class='toast success'><i class='fas fa-broom'></i> <div><b>Log Bersih!</b><br>Semua riwayat telah dikosongkan.</div></div>";
    header("Location: transactions.php"); exit();
}

if (isset($_SESSION['pesan'])) {
    $pesan = $_SESSION['pesan'];
    unset($_SESSION['pesan']); 
}

// ==========================================
// 3. AMBIL DATA TRANSAKSI
// ==========================================
$query_stat = mysqli_query($conn, "SELECT COUNT(*) as total_tx, SUM(total_harga) as pendapatan FROM transactions");
$stat_tx = mysqli_fetch_assoc($query_stat);

// Perbaikan Error line 133: Pakai isset() biar support PHP versi lama
$total_pendapatan = isset($stat_tx['pendapatan']) ? $stat_tx['pendapatan'] : 0;
$total_unit = isset($stat_tx['total_tx']) ? $stat_tx['total_tx'] : 0;

$transaksi_query = mysqli_query($conn, "SELECT * FROM transactions ORDER BY tanggal DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Transactions</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --bg-base: #06090F; --bg-surface: rgba(18, 24, 38, 0.6); --border-color: rgba(255, 255, 255, 0.08); --primary: #22C55E; --text-main: #FFFFFF; --text-muted: #808B9F; --radius-lg: 24px; --radius-md: 14px; --blur: blur(20px); --danger: #EF4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; background-image: radial-gradient(circle at 10% 50%, rgba(34, 197, 94, 0.05), transparent 20%); }
        
        .sidebar { position: fixed; left: -320px; top: 0; bottom: 0; width: 300px; background: rgba(10, 15, 26, 0.9); backdrop-filter: var(--blur); border-right: 1px solid var(--border-color); padding: 30px 20px; display: flex; flex-direction: column; z-index: 1000; transition: all 0.3s ease; }
        .sidebar.show { left: 0; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); z-index: 999; display: none; }
        .sidebar-overlay.show { display: block; }

        .logo { display: flex; align-items: center; gap: 15px; margin-bottom: 35px; }
        .menu a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-md); font-weight: 500; margin-bottom: 5px; transition: 0.3s; }
        .menu a.active { background: rgba(34, 197, 94, 0.08); color: var(--primary); }

        .main { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { position: sticky; top: 0; z-index: 5; background: rgba(6, 9, 15, 0.85); backdrop-filter: var(--blur); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .btn-menu { background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); color: var(--text-main); font-size: 20px; width: 45px; height: 45px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; }

        .clock-box { background: rgba(255,255,255,0.05); padding: 8px 20px; border-radius: 50px; border: 1px solid var(--border-color); color: var(--primary); font-family: monospace; font-size: 16px; font-weight: bold; }

        .content { padding: 40px; max-width: 1400px; margin: 0 auto; width: 100%; }
        .box { background: var(--bg-surface); padding: 30px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); }
        .box-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 13px; }
        th { color: var(--text-muted); text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }

        .btn-clear { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); padding: 8px 15px; border-radius: 10px; cursor: pointer; font-weight: bold; }
        
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { display: flex; align-items: center; gap: 15px; padding: 15px 25px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px; animation: slide 0.5s forwards; }
        @keyframes slide { from { transform: translateX(100%); } to { transform: translateX(0); } }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if($pesan != "") echo "<div class='toast-container'>$pesan</div>"; ?>

    <aside class="sidebar" id="sidebar">
        <div class="logo"><div style="background:var(--primary); color:#000; padding:10px; border-radius:10px;"><i class="fas fa-bolt"></i></div> <h2>Suzuka</h2></div>
        <nav class="menu">
            <a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="inventory.php"><i class="fas fa-box-open"></i> Inventory</a>
            <a href="transactions.php" class="active"><i class="fas fa-receipt"></i> Transactions</a>
            <a href="index.php" style="margin-top:auto; color:var(--danger)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:20px;">
                <button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div><h3>Sales Report</h3><p style="font-size:12px; color:var(--text-muted);">Riwayat Transaksi</p></div>
            </div>
            <div class="clock-box" id="realtimeClock">00:00:00</div>
            <div class="user-profile"><img src="https://ui-avatars.com/api/?name=<?php echo $username; ?>&background=22C55E&color=fff" style="width:35px; border-radius:50%;"></div>
        </header>

        <div class="content">
            <div class="box">
                <div class="box-header">
                    <div>
                        <h4 style="color:var(--primary);">Total Penjualan: Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h4>
                        <p style="font-size:12px; color:var(--text-muted);"><?php echo $total_unit; ?> Transaksi Berhasil</p>
                    </div>
                    <form action="" method="POST" onsubmit="return confirm('Kosongkan semua log?');">
                        <button type="submit" name="clear_all" class="btn-clear"><i class="fas fa-broom"></i> Clear Log</button>
                    </form>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Customer</th>
                                <th>WhatsApp</th>
                                <th>Produk</th>
                                <th>Total</th>
                                <th>Admin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($transaksi_query)): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?php echo date('d M, H:i', strtotime($row['tanggal'])); ?></td>
                                <td><b><?php echo htmlspecialchars($row['customer_name']); ?></b></td>
                                <td><?php echo htmlspecialchars($row['customer_wa']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_produk']); ?> (x<?php echo $row['jumlah']; ?>)</td>
                                <td style="color:var(--primary); font-weight:bold;">Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                <td style="font-size:11px;"><?php echo $row['pembeli']; ?></td>
                                <td>
                                    <a href="?hapus=<?php echo $row['id']; ?>" style="color:var(--danger);" onclick="return confirm('Hapus baris ini?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($transaksi_query) == 0): ?>
                                <tr><td colspan="7" style="text-align:center; padding:50px; color:var(--text-muted);">Belum ada data.</td></tr>
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
        btnMenu.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); });

        function updateClock() {
            document.getElementById('realtimeClock').innerText = new Date().toLocaleTimeString('id-ID');
        }
        setInterval(updateClock, 1000); updateClock();
    </script>
</body>
</html>