<?php
require 'vendor/autoload.php'; // Pastikan jalur ini sesuai dengan letak autoload.php Anda
include 'config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $file = $_FILES['file']['tmp_name'];

    // Memuat file Excel
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();

    // Mulai transaksi
    $conn->beginTransaction();

    try {
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            // Asumsi kolom pertama adalah nama siswa, dan kolom kedua adalah nama kelas
            $namaSiswa = $data[0];
            $namaKelas = $data[1];

            // Cek apakah kelas sudah ada
            $stmt = $conn->prepare("SELECT id FROM kelas WHERE nama_kelas = :nama_kelas");
            $stmt->execute(['nama_kelas' => $namaKelas]);
            $kelas = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$kelas) {
                // Insert kelas baru jika belum ada
                $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas) VALUES (:nama_kelas)");
                $stmt->execute(['nama_kelas' => $namaKelas]);
                $kelasId = $conn->lastInsertId();
            } else {
                $kelasId = $kelas['id'];
            }

            // Insert siswa
            $stmt = $conn->prepare("INSERT INTO siswa (nama, kelas_id) VALUES (:nama, :kelas_id)");
            $stmt->execute([
                'nama' => $namaSiswa,
                'kelas_id' => $kelasId
            ]);
        }

        // Commit transaksi
        $conn->commit();

        echo "Data siswa berhasil diupload.";
    } catch (Exception $e) {
        // Rollback jika ada kesalahan
        $conn->rollBack();
        echo "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>
