<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";
$database = new Database();
$db = $database->getConnection();

// Get employee data
if (isset($_GET['id'])) {
    $emp_id = $_GET['id'];
    
    // Ubah query untuk mengambil data pegawai
    $query = "SELECT e.*, d.dept_name, p.position_name 
              FROM employees e
              LEFT JOIN departments d ON e.dept_id = d.dept_id
              LEFT JOIN positions p ON e.position_id = p.position_id
              WHERE e.emp_id = ?";
              
    $stmt = $db->prepare($query);
    $stmt->execute([$emp_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        header("Location: index.php");
        exit();
    }
    
    // Fetch departments and positions
    $dept_query = "SELECT * FROM departments ORDER BY dept_name";
    $pos_query = "SELECT * FROM positions ORDER BY position_name";
    
    $dept_stmt = $db->prepare($dept_query);
    $pos_stmt = $db->prepare($pos_query);
    
    $dept_stmt->execute();
    $pos_stmt->execute();
    
    $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
    $positions = $pos_stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validasi rentang gaji berdasarkan posisi
        $pos_query = "SELECT salary_range_min, salary_range_max FROM positions WHERE position_id = ?";
        $pos_stmt = $db->prepare($pos_query);
        $pos_stmt->execute([$_POST['position_id']]);
        $position = $pos_stmt->fetch(PDO::FETCH_ASSOC);
        
        $salary = floatval($_POST['salary']);
        if ($salary < $position['salary_range_min'] || $salary > $position['salary_range_max']) {
            throw new Exception("Gaji harus berada dalam rentang yang ditentukan untuk posisi ini (Rp " . 
                number_format($position['salary_range_min'], 0, ',', '.') . " - Rp " . 
                number_format($position['salary_range_max'], 0, ',', '.') . ")");
        }

        $query = "UPDATE employees SET 
                  nik = ?, 
                  full_name = ?, 
                  email = ?, 
                  birth_date = ?, 
                  hire_date = ?, 
                  dept_id = ?, 
                  position_id = ?, 
                  salary = ?
                  WHERE emp_id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $_POST['nik'],
            $_POST['full_name'],
            $_POST['email'],
            $_POST['birth_date'],
            $_POST['hire_date'],
            $_POST['dept_id'],
            $_POST['position_id'],
            $_POST['salary'],
            $emp_id
        ]);
        
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Employee - Sistem Kepegawaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 1;
            display: block;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 24 24"><path fill="%23000000" d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/></svg>');
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        input[type="date"] {
            position: relative;
            padding-right: 25px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Edit Data Pegawai</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>NIK</label>
                    <input type="text" name="nik" class="form-control" value="<?php echo htmlspecialchars($employee['nik']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($employee['full_name']); ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control" value="<?php echo $employee['birth_date']; ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Tanggal Masuk</label>
                    <input type="date" name="hire_date" class="form-control" value="<?php echo $employee['hire_date']; ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Departemen</label>
                    <select name="dept_id" class="form-control" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['dept_id']; ?>" 
                                <?php echo $dept['dept_id'] == $employee['dept_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Jabatan</label>
                    <select name="position_id" class="form-control" required>
                        <?php foreach ($positions as $pos): ?>
                            <option value="<?php echo $pos['position_id']; ?>"
                                <?php echo $pos['position_id'] == $employee['position_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pos['position_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Gaji</label>
                    <input type="number" name="salary" class="form-control" value="<?php echo $employee['salary']; ?>" required>
                    <div class="form-text" id="salaryRange"></div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Konversi data posisi dari PHP ke JavaScript
    const positionSalaryRanges = <?php echo json_encode(array_map(function($pos) {
        return [
            'id' => $pos['position_id'],
            'min' => (float)$pos['salary_range_min'],
            'max' => (float)$pos['salary_range_max']
        ];
    }, $positions)); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        // Date picker functionality
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            input.addEventListener('click', function() {
                if (!this.showPicker) {
                    const event = document.createEvent('MouseEvents');
                    event.initMouseEvent('click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
                    this.dispatchEvent(event);
                } else {
                    this.showPicker();
                }
            });
        });

        // Salary range validation
        const positionSelect = document.querySelector('select[name="position_id"]');
        const salaryInput = document.querySelector('input[name="salary"]');
        const salaryRange = document.getElementById('salaryRange');

        function updateSalaryRange() {
            const selectedPosition = positionSelect.value;
            const range = positionSalaryRanges.find(r => r.id === selectedPosition);
            
            if (range) {
                salaryInput.min = range.min;
                salaryInput.max = range.max;
                salaryRange.textContent = `Rentang gaji: Rp ${range.min.toLocaleString()} - Rp ${range.max.toLocaleString()}`;
                
                const currentValue = Number(salaryInput.value);
                if (currentValue < range.min || currentValue > range.max) {
                    salaryInput.classList.add('is-invalid');
                    salaryRange.className = 'invalid-feedback';
                } else {
                    salaryInput.classList.remove('is-invalid');
                    salaryRange.className = 'form-text text-muted';
                }
            }
        }

        positionSelect.addEventListener('change', updateSalaryRange);
        salaryInput.addEventListener('input', updateSalaryRange);
        
        // Initialize salary range display
        if (positionSelect.value) {
            updateSalaryRange();
        }
    });
    </script>
</body>
</html>