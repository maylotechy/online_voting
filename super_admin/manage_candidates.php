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

// Database functions (unchanged)
function getColleges($pdo) {
    $stmt = $pdo->query("SELECT id, college_name AS c_name, code FROM colleges");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPositions($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM positions");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCandidates($pdo) {
    $stmt = $pdo->query("
        SELECT 
            c.id AS candidate_id,
            c.student_id,
            c.`status`,
            CONCAT(s.first_name, ' ', s.last_name) AS full_name,
            s.first_name,
            s.last_name,
            s.email,
            s.college_id,
            col.college_name,
            s.course_id,
            crs.name AS course_name,
            c.position_id,
            pos.name AS position_name,
            c.platform,
            c.party_list
        FROM 
            candidates c
        JOIN 
            students s ON c.student_id = s.student_id
        JOIN 
            colleges col ON s.college_id = col.id
        JOIN 
            courses crs ON s.course_id = crs.id
        JOIN 
            positions pos ON c.position_id = pos.id
        ORDER BY 
            s.last_name, s.first_name;
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data (unchanged)
try {
    $colleges = getColleges($pdo);
    $positions = getPositions($pdo);
    $candidates = getCandidates($pdo);

    // Calculate candidate counts
    $total_candidates = count($candidates);
    $active_candidates = 0;
    $pending_candidates = 0;
    $archived_candidates = 0;

    foreach ($candidates as $candidate) {
        switch ($candidate['status']) {
            case 'active':
                $active_candidates++;
                break;
            case 'pending':
                $pending_candidates++;
                break;
            case 'archived':
                $archived_candidates++;
                break;
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Candidates</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
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

        .bg-students {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
        }

        .bg-voted {
            background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
        }

        .bg-not-voted {
            background: linear-gradient(135deg, #f72585 0%, #e63946 100%);
        }

        .bg-turnout {
            background: linear-gradient(135deg, #7209b7 0%, #560bad 100%);
        }

        .active-status {
            color: #28a745;
        }

        .pending-status {
            color: #ffc107;
        }

        .archived-status {
            color: #dc3545;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom-width: 1px;
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .pagination .page-item.active .page-link {
            background-color: #4361ee;
            border-color: #4361ee;
        }

        .pagination .page-link {
            color: #4361ee;
        }

        .filter-controls {
            background-color: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }

        .search-control {
            background-color: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
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

        /* Modal styles */
        .modal-header {
            background-color: var(--primary);
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
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
                <a class="nav-link" href="manage_admin.php">
                    <i class="fas fa-user-shield"></i>
                    <span>College Admins</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="manage_candidates.php">
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
                            <li><a class="dropdown-item" href="#" onclick="Logout()"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid">
            <h2 class="mb-4">Candidate Management</h2>

            <!-- Add Candidate Button -->
            <div class="mb-4">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                    <i class="fas fa-user-plus me-2"></i>Add Candidate
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card bg-students">
                        <i class="fas fa-users"></i>
                        <div class="count"><?= $total_candidates ?></div>
                        <div class="label">Total Candidates</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card bg-voted">
                        <i class="fas fa-user-check"></i>
                        <div class="count"><?= $active_candidates ?></div>
                        <div class="label">Active Candidates</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card bg-not-voted">
                        <i class="fas fa-hourglass-half"></i>
                        <div class="count"><?= $pending_candidates ?></div>
                        <div class="label">Pending Candidates</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card bg-turnout">
                        <i class="fas fa-archive"></i>
                        <div class="count"><?= $archived_candidates ?></div>
                        <div class="label">Archived Candidates</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-controls mb-4">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label for="statusFilter" class="form-label">Status:</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group mb-0">
                            <label for="searchInput" class="form-label">Search:</label>
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="Search candidates...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Candidates Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Candidate Records</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="candidatesTable">
                            <thead class="table-light">
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>College</th>
                                <th>Course</th>
                                <th>Position</th>
                                <th>Platform</th>
                                <th>Party List</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($candidates)): ?>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($candidate['student_id']) ?></td>
                                        <td><?= htmlspecialchars($candidate['full_name']) ?></td>
                                        <td><?= htmlspecialchars($candidate['college_name']) ?></td>
                                        <td><?= htmlspecialchars($candidate['course_name']) ?></td>
                                        <td><?= htmlspecialchars($candidate['position_name']) ?></td>
                                        <td><?= htmlspecialchars($candidate['platform']) ?></td>
                                        <td><?= htmlspecialchars($candidate['party_list']) ?></td>
                                        <td class="<?= $candidate['status'] ?>-status">
                                            <i class="fas <?=
                                            $candidate['status'] === 'active' ? 'fa-check-circle' :
                                                ($candidate['status'] === 'pending' ? 'fa-hourglass-half' : 'fa-archive')
                                            ?>"></i>
                                            <?= ucfirst($candidate['status']) ?>
                                        </td>
                                        <td>
                                            <?php if ($candidate['status'] === 'archived'): ?>
                                                <button class="btn btn-sm btn-danger delete-btn"
                                                        data-id="<?= $candidate['candidate_id'] ?>"
                                                        data-name="<?= htmlspecialchars($candidate['full_name']) ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?= $candidate['candidate_id'] ?>"
                                                        data-student-id="<?= $candidate['student_id'] ?>"
                                                        data-first-name="<?= htmlspecialchars($candidate['first_name']) ?>"
                                                        data-last-name="<?= htmlspecialchars($candidate['last_name']) ?>"
                                                        data-email="<?= htmlspecialchars($candidate['email']) ?>"
                                                        data-college-id="<?= $candidate['college_id'] ?>"
                                                        data-course-id="<?= $candidate['course_id'] ?>"
                                                        data-position-id="<?= $candidate['position_id'] ?>"
                                                        data-position-name="<?= htmlspecialchars($candidate['position_name']) ?>"
                                                        data-platform="<?= htmlspecialchars($candidate['platform']) ?>"
                                                        data-party-list="<?= htmlspecialchars($candidate['party_list']) ?>"
                                                        data-status="<?= htmlspecialchars($candidate['status']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No candidates found</td>
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

<!-- Add Candidate Modal -->
<div class="modal fade" id="addCandidateModal" tabindex="-1" aria-labelledby="addCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="candidateForm" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addCandidateModalLabel"><i class="fas fa-user-plus me-2"></i>Add New Candidate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" required>
                            </div>
                            <div class="mb-3">
                                <label for="position_id" class="form-label">Position</label>
                                <select class="form-select" id="position_id" name="position_id" required>
                                    <option value="">Select Position</option>
                                    <?php foreach ($positions as $position): ?>
                                        <option value="<?= $position['id'] ?>"><?= htmlspecialchars($position['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="party_list" class="form-label">Party List</label>
                                <input type="text" class="form-control" id="party_list" name="party_list" required>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select status</option>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="platform" class="form-label">Platform</label>
                        <textarea class="form-control" id="platform" name="platform" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Candidate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Candidate Modal -->
<div class="modal fade" id="editCandidateModal" tabindex="-1" aria-labelledby="editCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCandidateForm" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editCandidateModalLabel"><i class="fas fa-user-edit me-2"></i>Edit Candidate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="candidate_id" id="editCandidateId">
                    <input type="hidden" name="student_id" id="editStudentId">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="editStudentIdDisplay" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="editFullName" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Current Position</label>
                                <input type="text" class="form-control" id="editCurrentPosition" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="editPosition" class="form-label">Change Position</label>
                                <select class="form-select" id="editPosition" name="position_id" required>
                                    <option value="">Select New Position</option>
                                    <?php foreach ($positions as $position): ?>
                                        <option value="<?= $position['id'] ?>"><?= htmlspecialchars($position['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editPartyList" class="form-label">Party List</label>
                        <input type="text" class="form-control" id="editPartyList" name="party_list" required>
                    </div>

                    <div class="mb-3">
                        <label for="editPlatform" class="form-label">Platform</label>
                        <textarea class="form-control" id="editPlatform" name="platform" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Status</label>
                        <select class="form-select" id="editStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="archived">Archived</option>
                        </select>
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
<div class="modal fade" id="deleteCandidateModal" tabindex="-1" aria-labelledby="deleteCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCandidateModalLabel"><i class="fas fa-trash-alt me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete candidate <strong id="deleteCandidateName"></strong>?</p>
                <input type="hidden" id="deleteCandidateId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCandidate">Delete</button>
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
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#candidatesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            info: false,
            dom: 'lrtip',
            language: {
                search: "",
                searchPlaceholder: "Search candidates..."
            }
        });

        // Custom search input
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Status filter
        $('#statusFilter').on('change', function() {
            var val = $(this).val();
            if (val === '') {
                table.column(7).search('').draw();
            } else {
                table.column(7).search(val).draw();
            }
        });

        // DELETE BUTTON (fix)
        $(document).on('click', '.delete-btn', function() {
            var candidateId = $(this).data('id');
            var candidateName = $(this).data('name');

            $('#deleteCandidateId').val(candidateId);
            $('#deleteCandidateName').text(candidateName);
            $('#deleteCandidateModal').modal('show');
        });

// EDIT BUTTON (fix)
        $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            var studentId = $(this).data('student-id');
            var firstName = $(this).data('first-name');
            var lastName = $(this).data('last-name');
            var positionId = $(this).data('position-id');
            var positionName = $(this).data('position-name');
            var platform = $(this).data('platform');
            var partyList = $(this).data('party-list');
            var status = $(this).data('status');

            $('#editCandidateId').val(id);
            $('#editStudentId').val(studentId);
            $('#editStudentIdDisplay').val(studentId);
            $('#editFullName').val(firstName + ' ' + lastName);
            $('#editCurrentPosition').val(positionName);
            $('#editPosition').val(positionId);
            $('#editPartyList').val(partyList);
            $('#editPlatform').val(platform);
            $('#editStatus').val(status);

            $('#editCandidateModal').modal('show');
        });


        // Form submission handler for add candidate
        $('#candidateForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Adding...');

            $.ajax({
                url: 'add_candidate.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#addCandidateModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to add candidate');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Add Candidate');
                }
            });
        });

        // Form submission handler for edit candidate
        $('#editCandidateForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: 'edit_candidate.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#editCandidateModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to update candidate');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Save Changes');
                }
            });
        });

        // Delete confirmation handler
        $('#confirmDeleteCandidate').click(function() {
            const candidateId = $('#deleteCandidateId').val();
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...');

            $.ajax({
                url: 'delete_candidate.php',
                type: 'POST',
                data: { candidate_id: candidateId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#deleteCandidateModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to delete candidate');
                },
                complete: function() {
                    btn.prop('disabled', false).html('Delete');
                }
            });
        });
    });
</script>

<script src="/js/logout.js"></script>
</body>
</html>