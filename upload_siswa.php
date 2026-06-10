<?php
// Nonaktifkan peringatan 'Notice'
error_reporting(E_ALL & ~E_NOTICE);

// Memuat autoload jika menggunakan Composer
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Memproses file Excel yang diupload
    $file_mime_type = $_FILES['file']['type'];
    $file_tmp_name = $_FILES['file']['tmp_name'];

    // Membaca file Excel
    try {
        $spreadsheet = IOFactory::load($file_tmp_name);
        $worksheet = $spreadsheet->getActiveSheet();

        $data_siswa = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $row_data = [];
            foreach ($cellIterator as $cell) {
                $row_data[] = $cell->getValue();
            }

            if (!empty($row_data[0]) && !empty($row_data[1])) { // Pastikan baris tidak kosong
                $data_siswa[] = $row_data;
            }
        }

        // Koneksi ke database
        include 'config.php';

        // Mulai transaksi
        $conn->beginTransaction();

        // Looping untuk insert data siswa ke dalam database
        foreach ($data_siswa as $siswa) {
            $nama_siswa = $siswa[0];
            $kelas = $siswa[1];

            // Cek apakah kelas sudah ada
            $stmt = $conn->prepare("SELECT id FROM kelas WHERE nama_kelas = :nama_kelas LIMIT 1");
            $stmt->execute(['nama_kelas' => $kelas]);
            $kelas_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($kelas_data) {
                $kelas_id = $kelas_data['id'];
            } else {
                // Insert kelas baru jika belum ada
                $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas) VALUES (:nama_kelas)");
                $stmt->execute(['nama_kelas' => $kelas]);
                $kelas_id = $conn->lastInsertId();
            }

            // Insert data siswa
            $stmt = $conn->prepare("INSERT INTO siswa (nama, kelas_id) VALUES (:nama, :kelas_id)");
            $stmt->execute(['nama' => $nama_siswa, 'kelas_id' => $kelas_id]);
        }

        // Commit transaksi
        $conn->commit();

        echo "Data siswa berhasil diupload.";
    } catch (Exception $e) {
        // Rollback jika ada error
        $conn->rollBack();
        echo "Gagal memproses file: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Data Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f7f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        label {
            font-size: 16px;
            margin-bottom: 10px;
            color: #555;
        }
        input[type="file"] {
            margin-bottom: 20px;
            padding: 10px;
            font-size: 14px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 20px;
            font-size: 16px;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Data Siswa dan Kelas</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <label for="file">Pilih file Excel:</label>
            <input type="file" name="file" id="file" accept=".xls,.xlsx" required>
            <input type="submit" value="Upload">
        </form>

        <!-- Pesan error atau sukses akan ditampilkan di sini -->
        <div class="message">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($e)) {
                    echo "Gagal memproses file: " . $e->getMessage();
                } else {
                    echo "Data siswa berhasil diupload.";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
