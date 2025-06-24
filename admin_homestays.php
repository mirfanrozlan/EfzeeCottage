<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Handle add homestay form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_homestay'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $price_per_night = floatval($_POST['price_per_night']);
    $max_guests = intval($_POST['max_guests']);
    $bedrooms = intval($_POST['bedrooms']);
    $bathrooms = intval($_POST['bathrooms']);
    $status = trim($_POST['status']);
    
    if ($name && $description && $address && $price_per_night > 0 && $max_guests > 0 && $bedrooms >= 0 && $bathrooms >= 0 && $status) {
        $stmt = $conn->prepare("INSERT INTO homestays (name, description, address, price_per_night, max_guests, bedrooms, bathrooms, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdiiss", $name, $description, $address, $price_per_night, $max_guests, $bedrooms, $bathrooms, $status);
        if ($stmt->execute()) {
            $homestay_success = "Homestay added successfully.";
        } else {
            $homestay_error = "Failed to add homestay.";
        }
        $stmt->close();
    } else {
        $homestay_error = "Please fill in all fields correctly.";
    }
}

// Fetch all homestays
$homestays = $conn->query("SELECT * FROM homestays ORDER BY homestay_id DESC");

// Handle delete request
if (isset($_GET['delete_homestay'])) {
    $id = intval($_GET['delete_homestay']);
    $conn->query("DELETE FROM homestays WHERE homestay_id = $id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homestay Management - EFZEE COTTAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background-color: #495057; }
        .nav-link.active { background-color: #0d6efd; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <h3 class="text-white mb-4">Admin Panel</h3>
            <div class="nav flex-column">
                <a href="admin.php" class="nav-link mb-2">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="admin_homestays.php" class="nav-link active mb-2">
                    <i class="fas fa-home me-2"></i> Homestays
                </a>
                <a href="admin_users.php" class="nav-link mb-2">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <a href="admin_reviews.php" class="nav-link mb-2">
                    <i class="fas fa-star me-2"></i> Reviews
                </a>
                <a href="logout.php" class="nav-link mt-4 text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Homestay Management</h2>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addHomestayModal">
                    <i class="fas fa-plus"></i> Add Homestay
                </button>
            </div>
            <?php if (isset($homestay_success)): ?>
                <div class="alert alert-success"><?php echo $homestay_success; ?></div>
            <?php endif; ?>
            <?php if (isset($homestay_error)): ?>
                <div class="alert alert-danger"><?php echo $homestay_error; ?></div>
            <?php endif; ?>
            <div class="mb-3">
                <input type="text" id="homestaySearchInput" class="form-control" placeholder="Search homestays...">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="homestaysTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Address</th>
                            <th>Price/Night</th>
                            <th>Max Guests</th>
                            <th>Bedrooms</th>
                            <th>Bathrooms</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($h = $homestays->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $h['homestay_id']; ?></td>
                                <td><?php echo htmlspecialchars($h['name']); ?></td>
                                <td><?php echo htmlspecialchars($h['description']); ?></td>
                                <td><?php echo htmlspecialchars($h['address']); ?></td>
                                <td>RM <?php echo number_format($h['price_per_night'], 2); ?></td>
                                <td><?php echo $h['max_guests']; ?></td>
                                <td><?php echo $h['bedrooms']; ?></td>
                                <td><?php echo $h['bathrooms']; ?></td>
                                <td><?php echo htmlspecialchars($h['status']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger delete-homestay-btn" data-id="<?= $h['homestay_id'] ?>" data-name="<?= htmlspecialchars($h['name']) ?>"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Add Homestay Modal -->
<div class="modal fade" id="addHomestayModal" tabindex="-1" aria-labelledby="addHomestayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 shadow-lg border-0">
            <form method="POST" action="" id="addHomestayForm">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold" id="addHomestayModalLabel"><i class="fas fa-home me-2"></i>Add New Homestay</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="add_homestay" value="1">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control form-control-lg" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control form-control-lg" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control form-control-lg" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Price per Night (RM)</label>
                            <input type="number" name="price_per_night" class="form-control form-control-lg" min="1" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Guests</label>
                            <input type="number" name="max_guests" class="form-control form-control-lg" min="1" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Bedrooms</label>
                            <input type="number" name="bedrooms" class="form-control form-control-lg" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bathrooms</label>
                            <input type="number" name="bathrooms" class="form-control form-control-lg" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-lg" required>
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Homestay</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Homestay table search filter
    document.getElementById('homestaySearchInput').addEventListener('keyup', function() {
        var input = this.value.toLowerCase();
        var rows = document.querySelectorAll('#homestaysTable tbody tr');
        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    });

// SweetAlert2 for add
const addForm = document.getElementById('addHomestayForm');
if (addForm) {
    addForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        fetch('', { method: 'POST', body: new FormData(form) })
            .then(res => res.text())
            .then(html => {
                Swal.fire({ icon: 'success', title: 'Homestay Added!', text: 'The new homestay has been added successfully.' }).then(() => { location.reload(); });
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to add homestay.' });
            });
    });
}

// SweetAlert2 for delete
const deleteBtns = document.querySelectorAll('.delete-homestay-btn');
deleteBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        Swal.fire({
            title: 'Delete Homestay?',
            text: `Are you sure you want to delete "${name}"? This cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('?delete_homestay=' + id, { method: 'POST' })
                    .then(res => res.text())
                    .then(() => {
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Homestay deleted.' }).then(() => { location.reload(); });
                    })
                    .catch(() => {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete homestay.' });
                    });
            }
        });
    });
});
</script>
</body>
</html> 