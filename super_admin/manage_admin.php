<?php
global $pdo;
session_start();
require '../config/db.php';

// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}
// Display toastr notification if set
if (!empty($_SESSION['toastr']) && is_array($_SESSION['toastr'])) {
    $toastr = $_SESSION['toastr'];
    $alert = '<script>
        $(document).ready(function() {
            toastr.' . htmlspecialchars($toastr['type']) . '("' . htmlspecialchars($toastr['message']) . '");
        });
    </script>';
    unset($_SESSION['toastr']);
} else {
    $alert = '';
}

// Database functions
function getColleges($pdo) {
    $stmt = $pdo->query("SELECT id, college_name AS c_name, code FROM colleges");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAdmins($pdo) {
    $stmt = $pdo->query("
        SELECT a.id, a.username, a.email, a.created_at, a.college_id, 
               c.college_name AS c_name, c.code AS college_code
        FROM admins a
        JOIN colleges c ON a.college_id = c.id
        WHERE a.role_id = 2
        ORDER BY a.created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data
try {
    $colleges = getColleges($pdo);
    $admins = getAdmins($pdo);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Check for success/error messages
$alert = '';
if (isset($_GET['success'])) {
    $alert = '<script>
        $(document).ready(function() {
            toastr.success("Admin added successfully!");
        });
    </script>';
} elseif (isset($_GET['error'])) {
    $alert = '<script>
        $(document).ready(function() {
            toastr.error("' . addslashes($_GET['error']) . '");
        });
    </script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage College Admins</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <!-- In your <head> section -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="../custom_css/side-bar.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <?= $alert ?>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item d-flex align-items-center mr-3">
                <img src="../asssets/super_admin/usm_comelec.jpg" class="img-circle elevation-2" style="width:30px; height:30px;">
                <span class="ml-2 font-weight-bold">USM Comelec (Super Admin)</span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="dashboard.php" class="brand-link">
            <i class="fas fa-vote-yea ml-3"></i>
            <span class="brand-text font-weight-light">USM Voting System</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>

                    <li class="nav-item"><a href="manage_admin.php" class="nav-link active">
                            <i class="nav-icon fas fa-user-shield"></i><p>College Admins</p></a></li>
                    <li class="nav-item">
                        <a href="manage_candidates.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Candidates</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="students.php" class="nav-link">
                            <i class="nav-icon fas fa-user"></i>
                            <p>Students</p>
                        </a>
                    </li>



                    <li class="nav-item"><a href="create_elections.php" class="nav-link">
                            <i class="nav-icon fas fa-rocket"></i><p>Launch Univ. Election</p></a></li>

                    <li class="nav-item"><a href="results.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i><p>Election Results</p></a></li>

                    <li class="nav-item"><a href="election_history.php" class="nav-link">
                            <i class="nav-icon fas fa-history"></i><p>Election History</p></a></li>

                    <li class="nav-item"><a href="export_results.php" class="nav-link">
                            <i class="nav-icon fas fa-download"></i><p>Export Results</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper p-4">
        <h2>Manage College Admins</h2>

        <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addAdminModal">
            <i class="fas fa-user-plus mr-2"></i> Add Admin
        </button>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">List of College Admins</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 150px;">
                        <input type="text" name="table_search" id="searchInput" class="form-control float-right" placeholder="Search">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>College</th>
                        <th>College Code</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($admins)): ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?= htmlspecialchars($admin['username']) ?></td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td><?= htmlspecialchars($admin['c_name']) ?></td>
                                <td><?= htmlspecialchars($admin['college_code']) ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($admin['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn"
                                            data-id="<?= $admin['id'] ?>"
                                            data-username="<?= htmlspecialchars($admin['username']) ?>"
                                            data-email="<?= htmlspecialchars($admin['email']) ?>"
                                            data-college-id="<?= $admin['college_id'] ?>"> <!-- Add this -->
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-btn"
                                            data-id="<?= $admin['id'] ?>"
                                            data-username="<?= htmlspecialchars($admin['username']) ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center bg-danger text-white">NO DATA AVAILABLE</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" role="dialog" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addAdminForm" action="process_add_admin.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAdminModalLabel">Add College Admin</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="college_id">College</label>
                        <select class="form-control" id="college_id" name="college_id" required>
                            <option value="">Select College</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['id'] ?>">
                                    <?= htmlspecialchars($college['c_name']) ?> (<?= htmlspecialchars($college['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="role_id" value="2">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" role="dialog" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editAdminForm" action="process_edit.admin.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAdminModalLabel">Edit Admin</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="editAdminId">
                    <div class="form-group">
                        <label for="editUsername">Username</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="editEmail">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="editCollege">College</label>
                        <select class="form-control" id="editCollege" name="college_id" required>
                            <option value="">Select College</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['id'] ?>">
                                    <?= htmlspecialchars($college['c_name']) ?> (<?= htmlspecialchars($college['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editPassword">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="editPassword" name="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" role="dialog" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="deleteAdminForm" action="process_delete_admin.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAdminModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete admin <strong id="deleteAdminName"></strong>?</p>
                    <input type="hidden" name="admin_id" id="deleteAdminId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.getElementById("searchInput");
        const tableRows = document.querySelectorAll("tbody tr");

        searchInput.addEventListener("keyup", function () {
            const searchTerm = searchInput.value.toLowerCase();

            tableRows.forEach(row => {
                const collegeName = row.cells[2]?.textContent.toLowerCase() || "";
                if (collegeName.includes(searchTerm)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Edit button handler
        $('.edit-btn').click(function() {
            const adminId = $(this).data('id');
            const username = $(this).data('username');
            const email = $(this).data('email');
            const collegeId = $(this).data('college-id'); // Get college_id

            $('#editAdminId').val(adminId);
            $('#editUsername').val(username);
            $('#editEmail').val(email);
            $('#editCollege').val(collegeId); // Set the selected college
            $('#editAdminModal').modal('show');
        });

        // Delete button handler
        $('.delete-btn').click(function() {
            $('#deleteAdminId').val($(this).data('id'));
            $('#deleteAdminName').text($(this).data('username'));
            $('#deleteAdminModal').modal('show');
        });

        // AJAX form submission for adding admin
        // Modified AJAX call
        $('#addAdminForm').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let submitBtn = form.find('[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                success: function(response) {
                    // Parse JSON response if needed
                    if (typeof response === 'object' && response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        toastr.success('Admin added successfully!');
                        $('#addAdminModal').modal('hide');
                        form[0].reset();
                        setTimeout(() => location.reload(), 1000);
                    }
                },
                error: function(xhr) {
                    let errorMsg = xhr.responseJSON?.message || xhr.responseText || 'Operation failed';
                    toastr.error(errorMsg);
                    submitBtn.prop('disabled', false).html('Save Changes');
                }
            });
        });

        // AJAX form submission for editing admin
        // AJAX form submission for editing admin
        $('#editAdminForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('[type="submit"]');

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#editAdminModal').modal('hide');
                        setTimeout(() => location.reload(), 1500); // Refresh after 1.5 sec
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    let response = xhr.responseJSON;
                    let errorMsg = response && response.message ? response.message : 'Failed to update admin';
                    toastr.error(errorMsg);
                    submitBtn.prop('disabled', false).html('Save Changes');
                }

                ,
                complete: function() {
                    submitBtn.prop('disabled', false).html('Save Changes');
                }
            });
        });

        // AJAX form submission for deleting admin
        $('#deleteAdminForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            let submitBtn = form.find('[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function(response) {
                    // Parse JSON response if needed
                    if (typeof response === 'object' && response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        toastr.success('Admin deleted successfully!');
                        $('#deleteAdminModal').modal('hide');
                        form[0].reset();
                        setTimeout(() => location.reload(), 1000);
                    }

                },
                error: function(xhr) {
                    toastr.error(xhr.responseText || 'Failed to delete admin');
                }
            });
        });
    });
</script>
<!-- Before closing </body> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 5000
    };
</script>
</body>
</html>