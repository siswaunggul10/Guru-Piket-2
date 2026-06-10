<?php
include 'config.php';

// Ambil data absensi dari database
$sql = "SELECT a.tanggal, s.nama, a.keterangan, k.nama_kelas 
        FROM absensi a 
        JOIN siswa s ON a.siswa_id = s.id 
        JOIN kelas k ON s.kelas_id = k.id 
        ORDER BY a.tanggal DESC";
$stmt = $conn->query($sql);
$absensi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daftar Absensi Siswa</title>
</head>
<body>
    <h1>Daftar Absensi Siswa</h1>
    <table border="1">
        <tr>
            <th>Tanggal</th>
            <th>Kelas</th>
            <th>Nama Siswa</th>
            <th>Keterangan</th>
        </tr>
        <?php foreach ($absensi as $a): ?>
        <tr>
            <td><?= $a['tanggal'] ?></td>
            <td><?= $a['nama_kelas'] ?></td>
            <td><?= $a['nama'] ?></td>
            <td><?= $a['keterangan'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
