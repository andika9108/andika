<?php
session_start();

// 1. CEK LOGIN
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username = $_SESSION['username'];

// 2. KONEKSI DATABASE (Sekarang manggil config.php biar satu pintu)
require 'config.php'; 

// 3. LOGIKA HAPUS & BERSIHKAN
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM transactions WHERE id='$id'");
    $_SESSION['pesan'] = "dihapus";
    header("Location: transactions.php"); exit();
}

if (isset($_POST['clear_all'])) {
    mysqli_query($conn, "TRUNCATE TABLE transactions");
    $_SESSION['pesan'] = "dibersihkan";
    header("Location: transactions.php"); exit();
}

// 4. DATA STATISTIK & TABEL (Safe for PHP 5.6+)
$query_stat = mysqli_query($conn, "SELECT COUNT(*) as total_tx, SUM(total_harga) as pendapatan FROM transactions");
$stat_tx = mysqli_fetch_assoc($query_stat);
$total_pendapatan = isset($stat_tx['pendapatan']) ? $stat_tx['pendapatan'] : 0;
$total_unit = isset($stat_tx['total_tx']) ? $stat_tx['total_tx'] : 0;

$transaksi_query = mysqli_query($conn, "SELECT * FROM transactions ORDER BY tanggal DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Sales Report</title>
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
        
        /* Topbar Sync */
        .topbar { position: sticky; top: 0; z-index: 100; background: rgba(6, 9, 15, 0.85); backdrop-filter: blur(20px); padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); width: 100%; }
        .btn-menu { background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); color: #fff; width: 45px; height: 45px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .clock-box { background: rgba(255,255,255,0.05); padding: 8px 20px; border-radius: 50px; border: 1px solid var(--border-color); color: var(--primary); font-family: monospace; font-size: 16px; font-weight: bold; }
        .sosmed-capsule { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 7px 15px; border-radius: 50px; border: 1px solid var(--border-color); }

        .content { padding: 25px; width: 100%; }
        .box { background: var(--bg-surface); padding: 25px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); }
        
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        
        /* Table Style */
        .table-container { width: 100%; overflow-x: auto; border-radius: var(--radius-md); }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { padding: 15px; text-align: left; color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border-color); }
        td { padding: 15px; font-size: 13px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .btn-clear { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); padding: 10px 20px; border-radius: 12px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-clear:hover { background: var(--danger); color: #fff; }

        /* Fix Select Option Visibility */
        select option { background-color: #121826; color: #FFFFFF; }

        @media (max-width: 992px) {
            .clock-box { display: none; }
            .report-header { flex-direction: column; align-items: flex-start; }
            .topbar { padding: 15px; }
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
            <a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="inventory.php"><i class="fas fa-box-open"></i> Inventory</a>
            <a href="transactions.php" class="active"><i class="fas fa-receipt"></i> Transactions</a>
            <a href="index.php" style="margin-top:auto; color:var(--danger)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-menu" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div>
                    <h4 style="font-weight:700; margin:0;">Sales Report</h4>
                    <p style="font-size:11px; color:var(--text-muted); margin:0;">Riwayat Transaksi</p>
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
                <div class="report-header">
                    <div>
                        <h2 style="color:var(--primary); font-weight:800;">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h2>
                        <p style="font-size:13px; color:var(--text-muted);"><?php echo $total_unit; ?> Transaksi Berhasil Teratat</p>
                    </div>
                    <button type="button" onclick="confirmClear()" class="btn-clear"><i class="fas fa-broom"></i> Clear All History</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Customer</th>
                                <th>WhatsApp</th>
                                <th>Item Terjual</th>
                                <th>Total Bayar</th>
                                <th>Admin</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($transaksi_query)): ?>
                            <tr>
                                <td style="color:var(--text-muted); font-size:12px;"><?php echo date('d M, H:i', strtotime($row['tanggal'])); ?></td>
                                <td><b style="color:#fff;"><?php echo htmlspecialchars($row['customer_name']); ?></b></td>
                                <td><a href="https://wa.me/<?php echo $row['customer_wa']; ?>" target="_blank" style="color:var(--primary); text-decoration:none;"><i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($row['customer_wa']); ?></a></td>
                                <td><?php echo htmlspecialchars($row['nama_produk']); ?> <span style="color:var(--primary)">x<?php echo $row['jumlah']; ?></span></td>
                                <td style="color:var(--primary); font-weight:800;">Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                <td style="font-size:11px; color:var(--text-muted);"><?php echo $row['pembeli']; ?></td>
                                <td style="text-align:center;">
                                    <button onclick="confirmHapus(<?php echo $row['id']; ?>)" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:16px;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($transaksi_query) == 0): ?>
                                <tr><td colspan="7" style="text-align:center; padding:100px; color:var(--text-muted);">Belum ada riwayat penjualan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <form id="clearForm" method="POST" style="display:none;"><input type="hidden" name="clear_all" value="1"></form>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        document.getElementById('menuToggle').onclick = () => { sidebar.classList.add('show'); overlay.classList.add('show'); }
        overlay.onclick = () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }

        function updateClock() {
            document.getElementById('realtimeClock').innerText = new Date().toLocaleTimeString('id-ID', { hour12: false });
        }
        setInterval(updateClock, 1000); updateClock();

        function confirmClear() {
            Swal.fire({
                title: 'Kosongkan Riwayat?',
                text: "Semua data transaksi akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Ya, Bersihkan!',
                background: '#0A0F1A', color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) { document.getElementById('clearForm').submit(); }
            })
        }

        function confirmHapus(id) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                confirmButtonText: 'Hapus',
                background: '#0A0F1A', color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) { window.location.href = '?hapus=' + id; }
            })
        }

        <?php if(isset($_SESSION['pesan'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?php echo ($_SESSION['pesan'] == "dihapus" ? "Satu baris data dibuang." : "Seluruh riwayat dibersihkan."); ?>',
                background: '#0A0F1A', color: '#fff', timer: 2000, showConfirmButton: false
            });
            <?php unset($_SESSION['pesan']); ?>
        <?php endif; ?>
    </script>
</body>
</html>