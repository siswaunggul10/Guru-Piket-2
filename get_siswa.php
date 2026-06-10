<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kelas_id'])) {
    $kelas_id = $_POST['kelas_id'];

    $stmt = $conn->prepare("SELECT id, nama FROM siswa WHERE kelas_id = ?");
    $stmt->execute([$kelas_id]);
    $siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($siswa);
}
?>