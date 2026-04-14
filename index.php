<?php
session_start();

// ==========================================
// 1. PENGATURAN KONEKSI DATABASE
// ==========================================
$host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "umamusume_db";

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

require 'config.php'; 

$pesan_login = "";

// ==========================================
// 2. PROSES KETIKA TOMBOL LOGIN DITEKAN
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mencegah SQL Injection dasar
    // Perhatikan: di HTML namanya sekarang "username" biar cocok sama database lo
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password_input = $_POST['password'];
    
    // Ubah password yang diketik menjadi MD5
    $password_md5 = md5($password_input);

    // Cari di database tabel users
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password_md5'";
    $hasil = mysqli_query($conn, $query);

    if (mysqli_num_rows($hasil) == 1) {
        $_SESSION['username'] = $username;
        $pesan_login = "<div class='alert success'>Berhasil Login! Selamat datang, Trainer.</div>";
        header("Location: dashboard.php"); // Hapus tanda // jika file dashboard.php sudah ada
    } else {
        $pesan_login = "<div class='alert error'>Gagal! ID Trainer atau Password salah.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracen Academy Login</title>
    <style>
        /* ==========================================
           3. DESAIN TEMA TRACEN ACADEMY (SILENCE SUZUKA)
           ========================================== */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            /* Background gradasi hijau muda dan putih (cerah) */
            background: linear-gradient(135deg, #d4fced 0%, #ffffff 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background-color: #ffffff;
            width: 320px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(46, 139, 87, 0.15); /* Shadow agak kehijauan */
            border-top: 8px solid #2ba153; /* Hijau Seragam Tracen */
            border-bottom: 8px solid #f1c40f; /* Kuning/Emas kancing seragam */
        }

        .logo-text {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-text h2 {
            margin: 0;
            color: #2ba153; /* Hijau Tracen */
            font-size: 26px;
            text-transform: uppercase;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .logo-text p {
            margin: 5px 0 0 0;
            color: #ff7a00; /* Oranye khas Suzuka */
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #444;
            font-weight: bold;
            font-size: 13px;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            transition: 0.3s;
            background-color: #fcfcfc;
        }

        .input-group input:focus {
            border-color: #2ba153; /* Fokus kotak input warna hijau */
            background-color: #ffffff;
            outline: none;
            box-shadow: 0 0 5px rgba(43, 161, 83, 0.3);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #2ba153; /* Tombol Hijau Tracen */
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(43, 161, 83, 0.2);
        }

        .btn-submit:hover {
            background-color: #ff7a00; /* Berubah jadi Oranye saat di-hover */
            box-shadow: 0 4px 6px rgba(255, 122, 0, 0.3);
        }
        
        .btn-submit:active {
            transform: scale(0.98);
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
        }

        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div class="login-box">
        <div class="logo-text">
            <h2>Tracen Academy</h2>
            <p>TRAINER PORTAL</p>
        </div>

        <!-- Menampilkan pesan sukses/gagal -->
        <?php echo $pesan_login; ?>

        <form action="" method="POST">
            <!-- Tampilan di layar: ID TRAINER, tapi di PHP dibaca sebagai 'username' -->
            <div class="input-group">
                <label for="username">ID TRAINER</label>
                <input type="text" id="username" name="username" placeholder="Masukkan ID Trainer..." required autocomplete="off">
            </div>

            <div class="input-group">
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" placeholder="Masukkan Password..." required>
            </div>

            <button type="submit" class="btn-submit">RACE START!</button>
        </form>
    </div>

</body>
</html>