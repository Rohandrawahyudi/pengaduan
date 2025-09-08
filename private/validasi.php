<?php
session_start();
require_once("database.php");

// Inisialisasi variabel error dan input
$namaError = $emailError = $telponError = $alamatError = $pengaduanError = $captchaError = $buktiError = "";
$nama = $email = $telpon = $alamat = $pengaduan = $captcha = $bukti = "";
$is_valid = true;

if (isset($_POST['submit'])) {
    $nomor     = $_POST['nomor'];
    $nama      = $_POST['nama'];
    $email     = $_POST['email'];
    $telpon    = $_POST['telpon'];
    $alamat    = $_POST['alamat'];
    $pengaduan = $_POST['pengaduan'];
    $captcha   = $_POST['captcha'];

    // Proses upload file bukti
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['bukti']['type'];
        $file_size = $_FILES['bukti']['size'];
        $file_tmp = $_FILES['bukti']['tmp_name'];
        $file_name = basename($_FILES['bukti']['name']);
        $upload_dir = "../images/";

        if (!in_array($file_type, $allowed_types)) {
            $buktiError = "Format file harus JPG, PNG, atau GIF";
            $is_valid = false;
        }

        if ($file_size > 2 * 1024 * 1024) {
            $buktiError = "Ukuran file maksimal 2MB";
            $is_valid = false;
        }

        if ($is_valid) {
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid('bukti_') . '.' . $ext;
            $upload_path = $upload_dir . $new_file_name;

            if (!move_uploaded_file($file_tmp, $upload_path)) {
                $buktiError = "Gagal mengupload file";
                $is_valid = false;
            } else {
                $bukti = $new_file_name;
            }
        }
    } else {
        $buktiError = "File bukti harus diupload";
        $is_valid = false;
    }

    // Validasi input lain
    validate_input();

    if ($is_valid) {
        $sql = "INSERT INTO `laporan` (`id`, `nama`, `email`, `telpon`, `alamat`, `isi`, `bukti`, `tanggal`, `status`) 
                VALUES (:nomor, :nama, :email, :telpon, :alamat, :isi, :bukti, CURRENT_TIMESTAMP, :status)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':nomor', $nomor);
        $stmt->bindValue(':nama', $nama);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':telpon', $telpon);
        $stmt->bindValue(':alamat', htmlspecialchars($alamat));
        $stmt->bindValue(':isi', htmlspecialchars($pengaduan));
        $stmt->bindValue(':bukti', $bukti);
        $stmt->bindValue(':status', "menunggu");

        if ($stmt->execute()) {
            header("Location: ../index?status=success");
            exit();
        } else {
            // Jika gagal insert, bisa beri pesan error
            header("Location: ../lapor.php?error=insert_failed");
            exit();
        }
    } else {
        // Redirect kembali ke form dengan error dan input lama
        $params = http_build_query([
            'nomor' => $nomor,
            'nama' => $nama,
            'namaError' => $namaError,
            'email' => $email,
            'emailError' => $emailError,
            'telpon' => $telpon,
            'telponError' => $telponError,
            'alamat' => $alamat,
            'alamatError' => $alamatError,
            'pengaduan' => $pengaduan,
            'pengaduanError' => $pengaduanError,
            'captcha' => $captcha,
            'captchaError' => $captchaError,
            'buktiError' => $buktiError
        ]);
        header("Location: ../lapor.php?$params");
        exit();
    }
}

// Fungsi validasi input (cek_nama, cek_email, dll) tetap sama
    // Fungsi Untuk Melakukan Pengecekan Dari Setiap Inputan Di Masing - masing Fungsi
    function validate_input() {
        global $nama , $email , $telpon , $alamat , $pengaduan , $captcha , $is_valid;
        cek_nama($nama);
        cek_email($email);
        cek_telpon($telpon);
        cek_alamat($alamat);
		cek_pengaduan($pengaduan);
        cek_captcha($captcha);
    }

    // validasi nama
    function cek_nama ($nama) {
        global $nama, $is_valid, $namaError;
        echo "cek_nama      : ", $nama      , "<br>";
        if (!preg_match("/^[a-zA-Z ]*$/",$nama)) { // cek nama bukan huruf
            $namaError = "Nama Hanya Boleh Huruf dan Spasi";
            $is_valid = false;
        } else { // jika nama valid kosongkan error
            $namaError = "";
        }
    }

    // validasi email
    function cek_email($email) {
        global $email, $is_valid, $emailError;
        echo "cek_email     : ", $email     , "<br>";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // cek format email
            $emailError = "Email Tidak Valid";
            $is_valid = false;
        } else { // jika email valid kosongkan eror
            $emailError = "";
        }
    }

    // validasi telpon
    function cek_telpon($telpon) {
        global $telpon, $telponError, $is_valid;
        echo "cek_telpon    : ", $telpon    , "<br>";
        if (!preg_match("/^[0-9]*$/",$telpon)) { // cek telpon hanya boleh angka
            $telponError = "Telpon Hanya Boleh Angka";
            $is_valid = false;
        } elseif (strlen($telpon) != 12) { // cek panjang telpon harus >= 6
            $telponError = "Panjang Telpon Harus 12 Digit";
            $is_valid = false;
        } else { // jika telpon valid kosongkan error
            $telponError = "";
        }
    }

    // validasi alamat
    function cek_alamat($alamat) {
        global $alamat, $is_valid, $alamatError;
        echo "cek_alamat    : ", $alamat    , "<br>";
        if (!preg_match("/^[a-zA-Z0-9 ]*$/",$alamat)) { // cek fullname bukan huruf
            $alamatError = "Alamat Hanya Boleh Huruf dan Angka";
            $is_valid = false;
        } else { // jika fullname valid kosongkan error
            $alamatError = "";
        }
    }

    // validasi pengaduan
    function cek_pengaduan($pengaduan) {
        global $pengaduan, $is_valid, $pengaduanError;
        echo "cek_pengaduan : ", $pengaduan , "<br>";
        if (strlen($pengaduan) > 2048) { // cek fullname bukan huruf
            $pengaduanError = "Isi Pengaduan Tidak Boleh Huruf Lebih Dari 2048 Karakter";
            $is_valid = false;
        } else { // jika pengaduan valid kosongkan error
            $pengaduanError = "";
        }
    }

    // validasi captcha
    function cek_captcha($captcha) {
        global $captcha, $is_valid, $captchaError;
        echo "cek_captcha   : ", $captcha   , "<br>";
        if ($captcha != $_SESSION['bilangan']) { // cek fullname bukan huruf
            $captchaError = "Captcha Salah atau Silahkan Reload Browser Anda";
            $is_valid = false;
        } else { // jika pengaduan valid kosongkan error
            $captchaError = "";
        }
    }
?>
