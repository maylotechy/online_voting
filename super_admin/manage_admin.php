<?php
global $pdo;
session_start();
require '../config/db.php';
include "../middleware/auth_admin.php";

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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning:  #ffc107;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            overflow-x: hidden;
        }

        .navbar {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 99;
            background: white;
            padding: 1rem 2rem;
        }

        .sidebar {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
            z-index: 100;
            width: 250px;
            overflow-y: auto;
            left: 0;
            top: 0;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 8px 16px;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .logo-text {
            font-weight: 700;
            font-size: 1.5rem;
            padding: 1rem;
            color: white;
            text-align: center;
            margin-bottom: 1.5rem;
            letter-spacing: 1px;
        }

        .content-wrapper {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: white;
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-weight: 600;
            margin-bottom: 0;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .stats-card {
            border-radius: 12px;
            color: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stats-card .count {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-card .label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .bg-admin {
            background: linear-gradient(135deg, #7209b7 0%, #560bad 100%);
        }

        .bg-college {
            background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom-width: 1px;
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }

        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }

        .btn-success:hover {
            background-color: #3db5d8;
            border-color: #3db5d8;
        }

        .btn-warning {
            background-color: var(--warning);
            border-color: var(--warning);
        }

        .btn-warning:hover {
            background-color: #e01a5e;
            border-color: #e01a5e;
        }

        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            border: 1px solid #ced4da;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
        }

        .user-dropdown {
            background-color: #f8f9fa;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-dropdown img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                padding-top: 15px;
            }

            .sidebar .nav-link {
                text-align: center;
                padding: 0.75rem;
                margin: 8px auto;
                max-width: 50px;
            }

            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.25rem;
            }

            .sidebar .nav-link span {
                display: none;
            }

            .logo-text {
                font-size: 0;
                padding: 0;
            }

            .logo-text::first-letter {
                font-size: 1.5rem;
            }

            .content-wrapper {
                margin-left: 80px;
                width: calc(100% - 80px);
                padding: 1rem;
            }

            .navbar {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar col-lg-2 col-md-3 d-none d-md-block">
        <div class="logo-text">
            <i class="fas fa-vote-yea me-2"></i>USMVote
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="manage_admin.php">
                    <i class="fas fa-user-shield"></i>
                    <span>College Admins</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_candidates.php">
                    <i class="fas fa-users"></i>
                    <span>Candidates</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="students.php">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="create_elections.php">
                    <i class="fas fa-rocket"></i>
                    <span>Launch Election</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="results.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Election Results</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="election_history.php">
                    <i class="fas fa-history"></i>
                    <span>Election History</span>
                </a>
            </li>

        </ul>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper col">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded-3 mb-4">
            <div class="container-fluid">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="ms-auto">
                    <div class="dropdown">
                        <button class="btn user-dropdown dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="../asssets/super_admin/usm_comelec.jpg" alt="Admin" width="32" height="32">
                            <span>USM Comelec</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../super_admin/logout.php" onclick="Logout()"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">College Admin Management</h2>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fas fa-user-plus me-2"></i>Add Admin
                </button>
            </div>

            <!-- Admin Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">College Admin List</h3>
                    <div class="input-group" style="width: 250px;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search admins...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>College</th>
                                <th>College Code</th>
                                <th>Date Created</th>
                                <th class="text-center">Actions</th>
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
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning edit-btn me-2"
                                                    data-id="<?= $admin['id'] ?>"
                                                    data-username="<?= htmlspecialchars($admin['username']) ?>"
                                                    data-email="<?= htmlspecialchars($admin['email']) ?>"
                                                    data-college-id="<?= $admin['college_id'] ?>">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                    data-id="<?= $admin['id'] ?>"
                                                    data-username="<?= htmlspecialchars($admin['username']) ?>">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle me-2"></i>No college admins found
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addAdminForm" action="process_add_admin.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addAdminModalLabel"><i class="fas fa-user-plus me-2"></i>Add College Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="college_id" class="form-label">College</label>
                        <select class="form-select" id="college_id" name="college_id" required>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editAdminForm" action="process_edit.admin.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editAdminModalLabel"><i class="fas fa-user-edit me-2"></i>Edit Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="editAdminId">
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCollege" class="form-label">College</label>
                        <select class="form-select" id="editCollege" name="college_id" required>
                            <option value="">Select College</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['id'] ?>">
                                    <?= htmlspecialchars($college['c_name']) ?> (<?= htmlspecialchars($college['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="editPassword" name="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAdminModalLabel"><i class="fas fa-trash-alt me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete admin <strong id="deleteAdminName"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
                <input type="hidden" id="deleteAdminId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 5000
    };

    $(document).ready(function() {
        // Search functionality
        $('#searchInput').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('table tbody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.includes(searchTerm));
            });
        });

        // Edit button handler
        $('.edit-btn').click(function() {
            const adminId = $(this).data('id');
            const username = $(this).data('username');
            const email = $(this).data('email');
            const collegeId = $(this).data('college-id');

            $('#editAdminId').val(adminId);
            $('#editUsername').val(username);
            $('#editEmail').val(email);
            $('#editCollege').val(collegeId);
            $('#editAdminModal').modal('show');
        });

        // Delete button handler
        $('.delete-btn').click(function() {
            $('#deleteAdminId').val($(this).data('id'));
            $('#deleteAdminName').text($(this).data('username'));
            $('#deleteAdminModal').modal('show');
        });

        // Confirm delete
        $('#confirmDelete').click(function () {
            const adminId = $('#deleteAdminId').val();
            const $btn = $(this);
            const originalHtml = $btn.html();

            // Show spinner and disable button
            $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...')
                .prop('disabled', true);

            $.ajax({
                url: 'process_delete_admin.php',
                type: 'POST',
                data: { admin_id: adminId },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#deleteAdminModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message || 'Failed to delete admin.');
                    }
                },
                error: function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'An unexpected error occurred.';
                    toastr.error(errorMsg);
                },
                complete: function () {
                    // Reset button after request is done (success or error)
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        });


        // AJAX form submission for adding admin
        $('#addAdminForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('[type="submit"]');

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Adding...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#addAdminModal').modal('hide');
                        form[0].reset();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to add admin');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Add Admin');
                }
            });
        });

        // AJAX form submission for editing admin
        $('#editAdminForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('[type="submit"]');

            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#editAdminModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to update admin');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Save Changes');
                }
            });
        });
    });
</script>
<script src="/plugins/sweet-alert/sweetalert.js"></script>
<script src="/js/logout.js"></script>
</body>
</html>