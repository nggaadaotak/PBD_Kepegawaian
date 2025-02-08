<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";
$database = new Database();
$db = $database->getConnection();

// Fetch departments and positions for dropdowns
$dept_query = "SELECT * FROM departments ORDER BY dept_name";
$pos_query = "SELECT * FROM positions ORDER BY position_name";

$dept_stmt = $db->prepare($dept_query);
$pos_stmt = $db->prepare($pos_query);

$dept_stmt->execute();
$pos_stmt->execute();

$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
$positions = $pos_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Pastikan semua data dikirim
        if (
            empty($_POST['nik']) || empty($_POST['full_name']) || empty($_POST['email']) ||
            empty($_POST['birth_date']) || empty($_POST['hire_date']) ||
            empty($_POST['dept_id']) || empty($_POST['position_id']) || empty($_POST['salary'])
        ) {
            throw new Exception("Semua kolom wajib diisi.");
        }

        // Ambil rentang gaji berdasarkan posisi
        $pos_query = "SELECT salary_range_min, salary_range_max FROM positions WHERE position_id = ?";
        $pos_stmt = $db->prepare($pos_query);
        $pos_stmt->execute([$_POST['position_id']]);
        $position = $pos_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$position) {
            throw new Exception("Posisi tidak ditemukan.");
        }

        $salary = floatval($_POST['salary']);
        if ($salary < $position['salary_range_min'] || $salary > $position['salary_range_max']) {
            throw new Exception("Gaji harus berada dalam rentang Rp " . number_format($position['salary_range_min']) . " - Rp " . number_format($position['salary_range_max']));
        }

        // Query untuk insert data
        $query = "INSERT INTO employees (nik, full_name, email, birth_date, hire_date, dept_id, position_id, salary) 
                  VALUES (:nik, :full_name, :email, :birth_date, :hire_date, :dept_id, :position_id, :salary)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':nik' => $_POST['nik'],
            ':full_name' => $_POST['full_name'],
            ':email' => $_POST['email'],
            ':birth_date' => $_POST['birth_date'],
            ':hire_date' => $_POST['hire_date'],
            ':dept_id' => $_POST['dept_id'],
            ':position_id' => $_POST['position_id'],
            ':salary' => $_POST['salary']
        ]);

        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add Employee - Sistem Kepegawaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Tambah Pegawai Baru</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>NIK</label>
                    <input type="text" name="nik" class="form-control" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Tanggal Masuk</label>
                    <input type="date" name="hire_date" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Departemen</label>
                    <select name="dept_id" class="form-control" required>
                        <option value="">Pilih Departemen</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['dept_id']; ?>">
                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Jabatan</label>
                    <select name="position_id" class="form-control" required>
                        <option value="">Pilih Jabatan</option>
                        <?php foreach ($positions as $pos): ?>
                            <option value="<?php echo $pos['position_id']; ?>">
                                <?php echo htmlspecialchars($pos['position_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Gaji</label>
                        <input type="number" name="salary" class="form-control" required>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
    <script>
const positionSalaryRanges = <?php echo json_encode(array_map(function($pos) {
    return [
        'id' => $pos['position_id'],
        'min' => (float)$pos['salary_range_min'],
        'max' => (float)$pos['salary_range_max']
    ];
}, $positions)); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const positionSelect = document.querySelector('select[name="position_id"]');
    const salaryInput = document.querySelector('input[name="salary"]');
    const salaryFeedback = document.createElement('div');
    salaryFeedback.className = 'form-text';
    salaryInput.parentNode.appendChild(salaryFeedback);

    function updateSalaryRange() {
        const selectedPosition = positionSelect.value;
        const range = positionSalaryRanges.find(r => r.id === selectedPosition);
        
        if (range) {
            salaryInput.min = range.min;
            salaryInput.max = range.max;
            salaryFeedback.textContent = `Rentang gaji: Rp ${range.min.toLocaleString()} - Rp ${range.max.toLocaleString()}`;
            
            const currentValue = Number(salaryInput.value);
            if (currentValue < range.min || currentValue > range.max) {
                salaryInput.classList.add('is-invalid');
                salaryFeedback.className = 'invalid-feedback';
            } else {
                salaryInput.classList.remove('is-invalid');
                salaryFeedback.className = 'form-text text-muted';
            }
        }
    }

    positionSelect.addEventListener('change', updateSalaryRange);
    salaryInput.addEventListener('input', updateSalaryRange);
    
    if (positionSelect.value) {
        updateSalaryRange();
    }
});
</script>
</body>
</html>
