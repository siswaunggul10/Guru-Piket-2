<?php
include 'config.php';
include 'includes/header.php';

// Fetch class data
$stmt = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");
$kelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process attendance input
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggal = $_POST['tanggal'];
    $kelas_id = $_POST['kelas_id'];
    $attendance_data = $_POST['attendance'] ?? [];

    try {
        $conn->beginTransaction();

        // Check for existing attendance
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM absensi WHERE tanggal = :tanggal AND siswa_id IN (SELECT id FROM siswa WHERE kelas_id = :kelas_id)");
        $checkStmt->execute(['tanggal' => $tanggal, 'kelas_id' => $kelas_id]);
        $existingCount = $checkStmt->fetchColumn();

        if ($existingCount > 0) {
            throw new Exception('Absensi untuk tanggal ini sudah diinputkan sebelumnya.');
        }

        // Get all students in the class
        $stmt = $conn->prepare("SELECT id FROM siswa WHERE kelas_id = ? ORDER BY nama ASC");
        $stmt->execute([$kelas_id]);
        $allStudents = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Prepare insert statement
        $insertStmt = $conn->prepare("INSERT INTO absensi (siswa_id, tanggal, keterangan) VALUES (:siswa_id, :tanggal, :keterangan)");
        
        foreach ($allStudents as $siswa_id) {
            $keterangan = $attendance_data[$siswa_id] ?? 'hadir';
            if ($keterangan !== 'hadir') {
                $insertStmt->execute([
                    'siswa_id' => $siswa_id,
                    'tanggal' => $tanggal,
                    'keterangan' => $keterangan
                ]);
            }
        }

        // Record class attendance
        $insertKelasAbsensiStmt = $conn->prepare("INSERT INTO kelas_absensi (kelas_id, tanggal) VALUES (:kelas_id, :tanggal)");
        $insertKelasAbsensiStmt->execute([
            'kelas_id' => $kelas_id,
            'tanggal' => $tanggal
        ]);

        $conn->commit();
        $successMessage = 'Absensi berhasil disimpan!';
    } catch (Exception $e) {
        $conn->rollBack();
        $errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Absensi Siswa</title>
    <link rel="icon" href="img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            min-height: 100vh;
            padding: 20px;
        }
        .form-container {
            max-width: 900px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .progress {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 10px;
        }
        .progress-bar {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            transition: width 0.3s ease;
        }
        .card {
            border: none;
            border-radius: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .attendance-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: border-color 0.2s;
        }
        .attendance-select:focus {
            border-color: #6e8efb;
            box-shadow: none;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }
        .btn-close {
            filter: brightness(0) invert(1);
        }
        .modal-body {
            padding: 2rem;
        }
        .modal-footer {
            border-top: none;
            padding: 1rem 2rem 2rem;
        }
        .btn {
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(110, 142, 251, 0.4);
        }
        .save-info {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container form-container">
        <h1 class="text-center mb-4">
            📌 Input Absensi Siswa
        </h1>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $successMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $errorMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" id="absensiForm">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            📚 Pilih Kelas
                        </label>
                        <select name="kelas_id" id="kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= $k['nama_kelas'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            📅 Tanggal
                        </label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="progress mb-4">
                <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%">0%</div>
            </div>

            <div id="siswa-list" class="mb-4"></div>

            <div class="alert alert-danger d-none" id="errorMessage">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Silakan pilih tanggal sebelum menyimpan absensi.
            </div>

            <button type="button" class="btn btn-primary w-100" id="saveButton">
                <i class="fas fa-save me-2"></i>Simpan Absensi
            </button>
            <p class="save-info text-center">
                <i class="fas fa-info-circle me-1"></i>
                Pastikan semua data telah terisi dengan benar sebelum menyimpan
            </p>
        </form>
    </div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">
                        <i class="fas fa-check-circle me-2"></i>
                        Konfirmasi Simpan Absensi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-circle" style="font-size: 4rem; color: #6e8efb;"></i>
                    </div>
                    <h4 class="mb-3">Konfirmasi Penyimpanan</h4>
                    <p class="mb-2">Apakah Anda yakin ingin menyimpan data absensi ini?</p>
                    <p class="text-muted small">
                        Data yang sudah disimpan tidak dapat diubah kembali.<br>
                        Pastikan data yang Anda input sudah benar.
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="button" id="confirmSave" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#kelas').change(function() {
            var kelas_id = $(this).val();
            if (kelas_id) {
                $.post('get_siswa.php', { kelas_id: kelas_id }, function(response) {
                    var students = JSON.parse(response);
                    var html = '';
                    
                    students.forEach(function(student) {
                        html += `
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user me-2"></i>
                                        <strong>${student.nama}</strong>
                                    </div>
                                    <select name="attendance[${student.id}]" class="form-select attendance-select ms-3" style="width: auto;">
                                        <option value="hadir">✅ Hadir</option>
                                        <option value="alpha">❌ Alpha</option>
                                        <option value="sakit">🤒 Sakit</option>
                                        <option value="izin">📄 Izin</option>
                                    </select>
                                </div>
                            </div>
                        `;
                    });
                    
                    $('#siswa-list').html(html);
                    updateProgressBar();
                });
            } else {
                $('#siswa-list').empty();
                updateProgressBar();
            }
        });

        $(document).on('change', '.attendance-select', function() {
            updateProgressBar();
        });

        function updateProgressBar() {
            var total = $('.attendance-select').length; // Total siswa
            if (total === 0) {
                $('#progressBar').css('width', '0%').text('0%');
                return;
            }
            
            // Hitung jumlah siswa yang hadir
            var hadirCount = $('.attendance-select').filter(function() {
                return $(this).val() === 'hadir'; // Hanya menghitung yang hadir
            }).length;
            
            var percentage = (hadirCount / total) * 100; // Hitung persentase kehadiran
            $('#progressBar').css('width', percentage + '%')
                             .text(Math.round(percentage) + '%');
        }

        $('#saveButton').click(function() {
            if (!$('#tanggal').val()) {
                $('#errorMessage').removeClass('d-none');
                $('html, body').animate({
                    scrollTop: $('#errorMessage').offset().top - 100
                }, 500);
            } else {
                $('#errorMessage').addClass('d-none');
                $('#confirmModal').modal('show');
            }
        });

        $('#confirmSave').click(function() {
            $('#confirmModal').modal('hide');
            $('#absensiForm').submit();
        });

        // Initialize date input with current date
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        today = yyyy + '-' + mm + '-' + dd;
        $('#tanggal').val(today);
    });
    </script>
</body>
</html>