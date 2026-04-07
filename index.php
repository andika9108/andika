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

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$pesan_login = "";

// ==========================================
// 2. PROSES KETIKA TOMBOL LOGIN DITEKAN
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $id_trainer = mysqli_real_escape_string($conn, $_POST['id_trainer']);
    $password_input = $_POST['password'];
    
    // Ubah password yang diketik menjadi MD5
    $password_md5 = md5($password_input);

    // Cari di database: Adakah id_trainer dan password MD5 yang cocok?
    $query = "SELECT * FROM trainers WHERE id_trainer='$id_trainer' AND password='$password_md5'";
    $hasil = mysqli_query($conn, $query);

    if (mysqli_num_rows($hasil) == 1) {
        // Jika cocok, login sukses
        $_SESSION['id_trainer'] = $id_trainer;
        $pesan_login = "<div class='alert success'>Berhasil Login! Selamat datang, Trainer $id_trainer.</div>";
       header("Location: dashboard.php"); // Hapus tanda // di awal baris ini jika lo udah punya file dashboard.php
    } else {
        // Jika tidak cocok, login gagal
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
           3. DESAIN TEMA UMA MUSUME
           ========================================== */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: radial-gradient(circle at top, #b2fca9, #56b96e); /* Background Hijau Turf */
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
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            border-top: 10px solid #ff66a3; /* Aksen Pink */
            border-bottom: 10px solid #2d8647; /* Aksen Hijau Gelap */
        }

        .logo-text {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-text h2 {
            margin: 0;
            color: #333;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logo-text p {
            margin: 5px 0 0 0;
            color: #ff66a3;
            font-size: 14px;
            font-weight: bold;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
            font-size: 13px;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            transition: 0.3s;
        }

        .input-group input:focus {
            border-color: #ff66a3;
            outline: none;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #ff66a3;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #e65c92;
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
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
            <p>TRAINER SYSTEM PORTAL</p>
        </div>

        <!-- Menampilkan pesan sukses/gagal di sini -->
        <?php echo $pesan_login; ?>

        <form action="" method="POST">
            <!-- Posisi Atas: ID Trainer -->
            <div class="input-group">
                <label for="id_trainer">ID TRAINER</label>
                <input type="text" id="id_trainer" name="id_trainer" placeholder="Ketik ID Anda..." required autocomplete="off">
            </div>

            <!-- Posisi Bawah: Password (otomatis jadi MD5 di PHP) -->
            <div class="input-group">
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" placeholder="Ketik Password..." required>
            </div>

            <button type="submit" class="btn-submit">LOGIN</button>
        </form>
    </div>

</body>
</html>