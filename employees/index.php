<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";
$database = new Database();
$db = $database->getConnection();

// Fetch employees with department and position info
$query = "SELECT 
    e.emp_id, e.nik, e.full_name, e.email,
    d.dept_name, p.position_name
    FROM employees e
    LEFT JOIN departments d ON e.dept_id = d.dept_id
    LEFT JOIN positions p ON e.position_id = p.position_id
    ORDER BY e.emp_id DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<!-- Sisanya tetap sama seperti kode Anda -->
<html>
<head>
    <title>Employees - Sistem Kepegawaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sistem Kepegawaian</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-light">Welcome, <?php echo $_SESSION['username']; ?></span>
                <a class="nav-item nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Daftar Pegawai</h2>
            <a href="create.php" class="btn btn-primary">Tambah Pegawai</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Departemen</th>
                        <th>Jabatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['nik']); ?></td>
                        <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                        <td><?php echo htmlspecialchars($employee['dept_name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['position_name']); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $employee['emp_id']; ?>" class="btn btn-sm btn-info">View</a>
                            <a href="edit.php?id=<?php echo $employee['emp_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete.php?id=<?php echo $employee['emp_id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>