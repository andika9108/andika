<?php
// 1. KONEKSI DATABASE
$conn = mysqli_connect("localhost", "root", "", "umamusume_db");
if (!$conn) { die("Koneksi gagal!"); }

// 2. AMBIL DATA TRANSAKSI (Definisikan variabel $query di sini agar dikenal di bawah)
$query = mysqli_query($conn, "SELECT * FROM transactions ORDER BY tanggal DESC LIMIT 20");

// 3. FUNGSI SENSOR (Nama & WA)
function sensorTeks($teks, $jumlah_bintang = 3) {
    if ($teks == "-" || empty($teks)) return "-";
    $len = strlen($teks);
    if ($len <= 3) return "***";
    return substr($teks, 0, 2) . str_repeat("*", $jumlah_bintang) . substr($teks, -2);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suzuka Store | Cek Transaksi Terkini</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-base: #06090F; --bg-surface: rgba(18, 24, 38, 0.6); --border-color: rgba(255, 255, 255, 0.08); --primary: #22C55E; --text-main: #FFFFFF; --text-muted: #808B9F; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-base); color: var(--text-main); min-height: 100vh; padding: 20px; background-image: radial-gradient(circle at 50% 50%, rgba(34, 197, 94, 0.05), transparent 50%); }
        .container { max-width: 900px; margin: 0 auto; position: relative; }
        .header { text-align: center; margin-bottom: 30px; padding-top: 10px; }
        .header h2 { color: var(--primary); font-weight: 800; letter-spacing: 1px; }
        .header p { color: var(--text-muted); font-size: 13px; }
        .box { background: var(--bg-surface); padding: 20px; border-radius: 20px; border: 1px solid var(--border-color); backdrop-filter: blur(10px); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th { padding: 15px; text-align: left; color: var(--primary); font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
        td { padding: 15px; font-size: 13px; border-bottom: 1px solid var(--border-color); color: #e2e8f0; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .status-badge { background: rgba(34, 197, 94, 0.1); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        .pay-badge { background: rgba(255, 255, 255, 0.05); color: #94A3B8; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--border-color); text-transform: uppercase; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-muted); text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.3s; margin-bottom: 10px; }
        .back-btn:hover { color: var(--primary); border-color: var(--primary); background: rgba(34, 197, 94, 0.1); }
    </style>
</head>
<body>

    <div class="container">
        <!-- Tombol Kembali -->
        <div style="text-align: left;">
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Toko</a>
        </div>

        <div class="header">
            <div style="background:var(--primary); color:#000; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin: 0 auto 15px;"><i class="fas fa-bolt"></i></div>
            <h2>TRANSAKSI TERBARU</h2>
            <p>Data transaksi diproses secara real-time (Privasi Terjaga)</p>
        </div>

        <div class="box">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Invoice / Game</th>
                            <th>Nama Pembeli</th>
                            <th>WhatsApp</th>
                            <th>Metode Bayar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Cek apakah query berhasil dan ada datanya
                        if ($query && mysqli_num_rows($query) > 0): 
                            while($row = mysqli_fetch_assoc($query)): 
                        ?>
                        <tr>
                            <td style="color:var(--text-muted); font-size:12px;"><?php echo date('H:i', strtotime($row['tanggal'])); ?> WIB</td>
                            <td>
                                <div style="font-weight:700; color:#fff;"><?php echo htmlspecialchars($row['nama_produk']); ?></div>
                                <div style="font-size:11px; color:var(--primary);">Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></div>
                            </td>
                            <td><?php echo sensorTeks($row['customer_name']); ?></td>
                            <td><?php echo sensorTeks($row['customer_wa'], 5); ?></td>
                            <td>
                                <span class="pay-badge">
                                    <?php echo (!empty($row['metode_bayar'])) ? htmlspecialchars($row['metode_bayar']) : 'QRIS'; ?>
                                </span>
                            </td>
                            <td><span class="status-badge">Berhasil</span></td>
                        </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr><td colspan="6" style="text-align:center; padding:50px; color:var(--text-muted);">Belum ada transaksi hari ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>