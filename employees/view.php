<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";
$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id'])) {
    $emp_id = $_GET['id'];
    try {
        // Using prepared statement for security
        $query = "SELECT 
            e.emp_id,
            e.nik,
            e.full_name,
            e.email,
            e.birth_date,
            e.hire_date,
            d.dept_name,
            p.position_name,
            e.salary
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
    } catch (PDOException $e) {
        // Log error and show user-friendly message
        error_log("Database Error: " . $e->getMessage());
        die("Maaf, terjadi kesalahan dalam mengakses data.");
    }
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employee - Sistem Kepegawaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2>Detail Pegawai</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>NIK:</strong> <?php echo htmlspecialchars($employee['nik']); ?></p>
                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($employee['full_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email']); ?></p>
                        <p><strong>Tanggal Lahir:</strong> <?php echo date('d F Y', strtotime($employee['birth_date'])); ?></p>
                        <p><strong>Tanggal Masuk:</strong> <?php echo date('d F Y', strtotime($employee['hire_date'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Departemen:</strong> <?php echo htmlspecialchars($employee['dept_name']); ?></p>
                        <p><strong>Jabatan:</strong> <?php echo htmlspecialchars($employee['position_name']); ?></p>
                        <p><strong>Gaji:</strong> Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="edit.php?id=<?php echo htmlspecialchars($employee['emp_id']); ?>" class="btn btn-warning">Edit</a>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>