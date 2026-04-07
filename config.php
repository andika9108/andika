<?php
session_start();

// Panggil file config.php biar langsung nyambung ke database
require 'config.php'; 

$error_message = "";

// Proses Login jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mencegah SQL Injection dasar
    $username = mysqli_real_escape_string($conn, $_POST['username']);
// ... (dan seterusnya ke bawah tetep sama persis) ...