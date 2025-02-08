<?php
// ... (kode sesi dan koneksi database tetap sama)

// Mengambil data jabatan dengan rentang gaji
$pos_query = "SELECT position_id, position_name, salary_range_min, salary_range_max FROM positions ORDER BY position_name";
$pos_stmt = $db->prepare($pos_query);
$pos_stmt->execute();
$positions = $pos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Mengubah array jabatan menjadi JSON untuk digunakan di JavaScript
$positions_json = json_encode($positions);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - Sistem Kepegawaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Tambah Pegawai Baru</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3" id="employeeForm">
            <!-- Field form sebelumnya tetap sama sampai pemilihan jabatan -->

            <div class="col-md-6 mb-3">
                <label for="position_id" class="form-label">Jabatan</label>
                <select name="position_id" id="position_id" class="form-control" required onchange="perbaruiRentangGaji()">
                    <option value="">Pilih Jabatan</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?php echo htmlspecialchars($pos['position_id']); ?>"
                                data-min="<?php echo $pos['salary_range_min']; ?>"
                                data-max="<?php echo $pos['salary_range_max']; ?>">
                            <?php echo htmlspecialchars($pos['position_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label for="salary" class="form-label">Gaji</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="salary" id="salary" class="form-control" required>
                </div>
                <small class="form-text text-muted" id="rentangGaji"></small>
            </div>

            <!-- Sisa form tetap sama -->
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Menyimpan data jabatan
    const jabatan = <?php echo $positions_json; ?>;

    function perbaruiRentangGaji() {
        const pilihJabatan = document.getElementById('position_id');
        const inputGaji = document.getElementById('salary');
        const textRentangGaji = document.getElementById('rentangGaji');
        
        if (pilihJabatan.value) {
            // Mencari data jabatan yang dipilih
            const jabatanTerpilih = jabatan.find(p => p.position_id === pilihJabatan.value);
            const gajiMinimal = parseFloat(jabatanTerpilih.salary_range_min);
            const gajiMaksimal = parseFloat(jabatanTerpilih.salary_range_max);
            
            // Memperbarui batasan input
            inputGaji.min = gajiMinimal;
            inputGaji.max = gajiMaksimal;
            
            // Format tampilan rentang gaji
            const formatGajiMin = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(gajiMinimal);
            
            const formatGajiMax = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(gajiMaksimal);
            
            textRentangGaji.textContent = `Rentang gaji untuk jabatan ini: ${formatGajiMin} - ${formatGajiMax}`;
            
            // Jika gaji saat ini di luar rentang, sesuaikan nilainya
            if (inputGaji.value < gajiMinimal) inputGaji.value = gajiMinimal;
            if (inputGaji.value > gajiMaksimal) inputGaji.value = gajiMaksimal;
        } else {
            // Jika tidak ada jabatan dipilih, hapus batasan
            inputGaji.removeAttribute('min');
            inputGaji.removeAttribute('max');
            textRentangGaji.textContent = '';
        }
    }

    // Menambahkan validasi form
    document.getElementById('employeeForm').addEventListener('submit', function(e) {
        const inputGaji = document.getElementById('salary');
        const pilihJabatan = document.getElementById('position_id');
        
        if (pilihJabatan.value) {
            const jabatanTerpilih = jabatan.find(p => p.position_id === pilihJabatan.value);
            const gaji = parseFloat(inputGaji.value);
            
            if (gaji < jabatanTerpilih.salary_range_min || gaji > jabatanTerpilih.salary_range_max) {
                e.preventDefault();
                alert('Gaji yang dimasukkan harus sesuai dengan rentang gaji untuk jabatan yang dipilih.');
            }
        }
    });

    // Menjalankan fungsi saat halaman dimuat (untuk form edit)
    document.addEventListener('DOMContentLoaded', function() {
        perbaruiRentangGaji();
    });
    </script>
</body>
</html>





