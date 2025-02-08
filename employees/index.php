<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";
$database = new Database();
$db = $database->getConnection();

// Get all departments and positions for filter dropdowns
$dept_query = "SELECT * FROM departments ORDER BY dept_name";
$pos_query = "SELECT * FROM positions ORDER BY position_name";

$dept_stmt = $db->prepare($dept_query);
$pos_stmt = $db->prepare($pos_query);

$dept_stmt->execute();
$pos_stmt->execute();

$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
$positions = $pos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare parameters for stored procedure
$search_term = !empty($_GET['search']) ? $_GET['search'] : null;
$dept_id = !empty($_GET['department']) ? $_GET['department'] : null;
$position_id = !empty($_GET['position']) ? $_GET['position'] : null;

// Call stored procedure
$query = "CALL sp_search_employees(?, ?, ?)";
$stmt = $db->prepare($query);
$stmt->execute([$search_term, $dept_id, $position_id]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
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

        <!-- Search and Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Cari NIK, Nama, atau Email" 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="department" class="form-control">
                            <option value="">Pilih Departemen</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['dept_id']; ?>"
                                    <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['dept_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="position" class="form-control">
                            <option value="">Pilih Jabatan</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo $pos['position_id']; ?>"
                                    <?php echo (isset($_GET['position']) && $_GET['position'] == $pos['position_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
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
                    <?php if (count($employees) > 0): ?>
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
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data yang ditemukan</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
