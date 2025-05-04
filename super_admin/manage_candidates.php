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

function getPositions($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM positions");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCandidates($pdo)
{
    // Prepare and execute the SQL query
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

    // Fetch and return data as associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data
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
    <!-- jQuery -->
    <script src="../plugins/jquery/jquery.min.js"></script>
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../custom_css/side-bar.css">
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <style>
        .modern-modal .modal-content {
            border: none;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0,0,0,.2);
        }

        .modern-modal .modal-header {
            border-bottom: 1px solid #e9ecef;
            background-color: #007bff;
            color: white;
            padding: 15px 20px;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }

        .modern-modal .modal-title {
            font-weight: 500;
            font-size: 1.2rem;
        }

        .modern-modal .modal-body {
            padding: 20px;
        }

        .modern-modal .card {
            border: none;
            box-shadow: none;
        }

        .modern-modal .card-header {
            background-color: #f8f9fa;
            padding: 12px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 500;
        }

        .modern-modal .form-control {
            border-radius: 3px;
            border: 1px solid #ced4da;
            padding: 8px 12px;
            margin-bottom: 15px;
        }

        .modern-modal .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .modern-modal .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 15px 20px;
            display: flex;
            justify-content: flex-end;
        }

        .modern-modal .btn {
            border-radius: 3px;
            padding: 8px 16px;
            font-weight: 500;
        }

        .modern-modal .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .modern-modal .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .modern-modal .close {
            text-shadow: none;
            opacity: 1;
        }

        .modern-modal .close:hover {
            opacity: 0.8;
        }

        /* New table controls styling */
        .table-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
        }

        .filter-controls, .search-control {
            flex: 1;
            min-width: 250px;
        }

        /* Hide the default DataTables search */
        .dataTables_filter {
            display: none;
        }

        /* Filter dropdown styling */
        #statusFilter {
            width: 100%;
            display: block;
            margin-top: 5px;
        }

        /* Search input styling */
        #searchInput {
            width: 100%;
            display: block;
            margin-top: 5px;
        }

        /* Table header styling */
        #candidatesTable thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom-width: 2px;
        }

        /* Table body styling */
        #candidatesTable tbody td {
            padding: 0.75rem;
            vertical-align: middle;
        }

        /* Custom pagination styling */
        .dataTables_paginate .paginate_button {
            color: #333 !important;
            background-color: #fff !important;
            border: 1px solid #dee2e6 !important;
            margin: 0 2px;
        }

        .dataTables_paginate .paginate_button.current {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }

        .dataTables_paginate .paginate_button:hover {
            background-color: #e9ecef !important;
        }




    </style>
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
                <a class="nav-link" onclick="Logout()"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                    <li class="nav-item"><a href="manage_admin.php" class="nav-link">
                            <i class="nav-icon fas fa-user-shield"></i><p>College Admins</p></a></li>

                    <li class="nav-item">
                        <a href="manage_candidates.php" class="nav-link active">
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
        <h2>Manage Candidates</h2>
        <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addCandidateModal">
            <i class="fas fa-user-plus mr-2"></i> Add Candidates
        </button>

        <!-- Summary Cards -->
        <div class="row mt-3">
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-info">
                    <div class="inner"><h3><?=  $total_candidates ?></h3><p>Total Candidates</p></div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-success">
                    <div class="inner"><h3><?= $active_candidates ?></h3><p>Active</p></div>
                    <div class="icon"><i class="fas fa-user-astronaut"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-warning">
                    <div class="inner"><h3><?= $pending_candidates ?></h3><p>Pending</p></div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-danger">
                    <div class="inner"><h3><?= $archived_candidates ?></h3><p>Archived</p></div>
                    <div class="icon"><i class="fas fa-folder"></i></div>
                </div>
            </div>
        </div>


        <div class="card">
            <div class="card-header">
                <div class="table-controls">
                    <div class="filter-controls">
                        <label for="statusFilter">Filter by Status:</label>
                        <select class="form-control" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="search-control">
                        <label for="searchInput">Search:</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search candidates...">
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table id="candidatesTable" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Student Id</th>
                        <th>Full Name</th>
                        <th>College</th>
                        <th>Course</th>
                        <th>Position</th>
                        <th>Platform</th>
                        <th>Party List</th>
                        <th>Actions</th>
                        <th>Status</th>
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
                                <td>
                                    <?php if ($candidate['status'] === 'archived'): ?>
                                        <button class="btn btn-danger btn-sm delete-btn"
                                                data-id="<?= $candidate['candidate_id'] ?>"
                                                data-first-name="<?= htmlspecialchars($candidate['first_name']) ?>"
                                                data-last-name="<?= htmlspecialchars($candidate['last_name']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-warning btn-sm edit-btn"
                                                data-id="<?= $candidate['candidate_id'] ?>"
                                                data-student-id="<?= $candidate['student_id'] ?>"
                                                data-first-name="<?= htmlspecialchars($candidate['first_name']) ?>"
                                                data-last-name="<?= htmlspecialchars($candidate['last_name']) ?>"
                                                data-email="<?= htmlspecialchars($candidate['email']) ?>"
                                                data-college-id="<?= $candidate['college_id'] ?>"
                                                data-college-name="<?= htmlspecialchars($candidate['college_name']) ?>"
                                                data-course-id="<?= $candidate['course_id'] ?>"
                                                data-course-name="<?= htmlspecialchars($candidate['course_name']) ?>"
                                                data-position-id="<?= $candidate['position_id'] ?>"
                                                data-position-name="<?= htmlspecialchars($candidate['position_name']) ?>"
                                                data-platform="<?= htmlspecialchars($candidate['platform']) ?>"
                                                data-party-list="<?= htmlspecialchars($candidate['party_list']) ?>"
                                                data-status="<?= htmlspecialchars($candidate['status']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $candidate['status'];

                                    $color = '';
                                    switch ($status) {
                                        case 'active':
                                            $color = '#28a745'; // green
                                            break;
                                        case 'pending':
                                            $color = '#ffc107'; // yellow
                                            break;
                                        case 'archived':
                                            $color = '#dc3545'; // red
                                            break;
                                    }
                                    ?>
                                    <span style="
                                            display: inline-block;
                                            width: 12px;
                                            height: 12px;
                                            border-radius: 50%;
                                            background-color: <?= $color ?>;
                                            margin-right: 5px;
                                            "></span>
                                    <span><?= strtolower($status) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center bg-danger text-white">NO DATA AVAILABLE</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Candidate Modal -->
<div class="modal fade modern-modal" id="addCandidateModal" tabindex="-1" aria-labelledby="addCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="candidateForm" action="add_candidate.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCandidateModalLabel">
                        <i class="fas fa-user-plus mr-2"></i>Add New Candidate
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Candidate Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="student_id"><i class="fas fa-id-card mr-1"></i>Student ID</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Position -->
                                    <div class="form-group">
                                        <label for="position_id"><i class="fas fa-briefcase mr-1"></i>Position</label>
                                        <select class="form-control" id="position_id" name="position_id" required>
                                            <option value="">Select Position</option>
                                            <?php foreach ($positions as $position): ?>
                                                <option value="<?= $position['id'] ?>"><?= htmlspecialchars($position['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Party List -->
                                    <div class="form-group">
                                        <label for="party_list"><i class="fas fa-flag mr-1"></i>Party List</label>
                                        <input type="text" class="form-control" id="party_list" name="party_list" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Status -->
                                    <div class="form-group">
                                        <label for="status"><i class="fas fa-chart-line mr-1"></i>Status</label>
                                        <select class="form-control" id="status" name="status" required>
                                            <option value="">Select status</option>
                                            <option value="active">Active</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Platform -->
                            <div class="form-group">
                                <label for="platform"><i class="fas fa-bullhorn mr-1"></i>Platform</label>
                                <textarea class="form-control" id="platform" name="platform" rows="4" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle mr-1"></i>Add Candidate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Candidate Modal -->
<div class="modal fade modern-modal" id="editCandidateModal" tabindex="-1" role="dialog" aria-labelledby="editCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="editCandidateForm" action="edit_candidate.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCandidateModalLabel">
                        <i class="fas fa-user-edit mr-2"></i>Edit Candidate
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Hidden Fields -->
                    <input type="hidden" name="candidate_id" id="editCandidateId">
                    <input type="hidden" name="student_id" id="editStudentId">

                    <!-- Candidate Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Candidate Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-id-card mr-1"></i>Student ID</label>
                                        <input type="text" class="form-control" id="editStudentIdDisplay" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user mr-1"></i>Full Name</label>
                                        <input type="text" class="form-control" id="editFullName" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-briefcase mr-1"></i>Current Position</label>
                                        <input type="text" class="form-control" id="editCurrentPosition" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="editPosition"><i class="fas fa-exchange-alt mr-1"></i>Change Position</label>
                                        <select class="form-control" id="editPosition" name="position_id" required>
                                            <option value="">Select New Position</option>
                                            <?php foreach ($positions as $position): ?>
                                                <option value="<?= $position['id'] ?>">
                                                    <?= htmlspecialchars($position['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="editPartyList"><i class="fas fa-flag mr-1"></i>Party List</label>
                                <input type="text" class="form-control" id="editPartyList" name="party_list" required>
                            </div>

                            <div class="form-group">
                                <label for="editPlatform"><i class="fas fa-bullhorn mr-1"></i>Platform</label>
                                <textarea class="form-control" id="editPlatform" name="platform" rows="4" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="editStatus"><i class="fas fa-chart-line mr-1"></i>Status <small class="text-danger">(Changing to archived cannot be reverted)</small></label>
                                <select class="form-control" id="editStatus" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade modern-modal" id="deleteCandidateModal" tabindex="-1" role="dialog" aria-labelledby="deleteCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="deleteCandidateForm" action="delete_candidate.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCandidateModalLabel">
                        <i class="fas fa-trash-alt mr-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span>Warning: This action cannot be undone!</span>
                    </div>
                    <p>Are you sure you want to delete the candidate <strong id="deleteCandidateName"></strong>?</p>
                    <input type="hidden" name="candidate_id" id="deleteCandidateId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt mr-1"></i>Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="../dist/js/adminlte.min.js"></script>
<!-- DataTables JS -->
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        // Toastr configuration
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000
        };

        // Initialize DataTable with your original configuration
        var table = $('#candidatesTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": false,
            "info": false,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 10,
            "language": {
                "paginate": {
                    "previous": "<i class='fas fa-angle-left'></i>",
                    "next": "<i class='fas fa-angle-right'></i>",
                    "first": "<i class='fas fa-angle-double-left'></i>",
                    "last": "<i class='fas fa-angle-double-right'></i>"
                },
                "info": "Showing _START_ to _END_ of _TOTAL_ candidates",
                "infoEmpty": "No candidates found",
                "infoFiltered": "(filtered from _MAX_ total candidates)",
                "search": "",
                "searchPlaceholder": "Search candidates..."
            },
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "columnDefs": [
                { "orderable": false, "targets": [7] } // Disable sorting for Actions column
            ],
            "drawCallback": function(settings) {
                // Add custom classes after each draw
                $('.paginate_button').addClass('btn btn-sm btn-outline-secondary');
                $('.paginate_button.current').removeClass('btn-outline-secondary')
                    .addClass('btn-primary');
            }
        });

        // Custom search input functionality
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Status filter functionality (your original logic)
        $('#statusFilter').on('change', function() {
            var val = $(this).val();

            // Special handling for empty value (All Statuses)
            if (val === '') {
                table.column(8).search('').draw();
            } else {
                // Filter the status column with case-insensitive exact match
                table.column(8).search(val, false, false).draw();
            }
        });

        // Edit button click handler
        $('.edit-btn').click(function() {
            // Get data attributes
            var id = $(this).data('id');
            var studentId = $(this).data('student-id');
            var firstName = $(this).data('first-name');
            var lastName = $(this).data('last-name');
            var positionId = $(this).data('position-id');
            var positionName = $(this).data('position-name');
            var platform = $(this).data('platform');
            var partyList = $(this).data('party-list');
            var status = $(this).data('status');


            // Set values in the form fields
            $('#editCandidateId').val(id);
            $('#editStudentId').val(studentId);
            $('#editStudentIdDisplay').val(studentId);
            $('#editFullName').val(firstName + ' ' + lastName);
            $('#editCurrentPosition').val(positionName);
            $('#editPosition').val(positionId);
            $('#editPartyList').val(partyList);
            $('#editPlatform').val(platform);
            $('#editStatus').val(status);

            // Show the modal
            $('#editCandidateModal').modal('show');
        });


        // Delete candidate button click
        $('.delete-btn').click(function() {
            var candidateId = $(this).data('id');
            var firstName = $(this).data('first-name');
            var lastName = $(this).data('last-name');
            var fullName = firstName + ' ' + lastName;

            // Set the values in the delete modal
            $('#deleteCandidateId').val(candidateId);
            $('#deleteCandidateName').text(fullName);

            // Show the delete modal
            $('#deleteCandidateModal').modal('show');
        });


        // Form submission handler for edit candidate
        $('#editCandidateForm').on('submit', function(e) {
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
                        $('#editCandidateModal').modal('hide');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Failed to update candidate';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.message || errorMsg;
                    } catch (e) {
                        errorMsg = xhr.responseText || errorMsg;
                    }
                    toastr.error(errorMsg);
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Save Changes');
                }
            });
        });
        // Reset forms when modals are closed
        $('#editCandidateModal').on('hidden.bs.modal', function() {
            $('#editCandidateForm')[0].reset();
        });


        $(document).ready(function() {
            // Delete button click handler - shows modal
            $(document).on('click', '.delete-btn', function() {
                const candidateId = $(this).data('id');
                const candidateName = $(this).data('full-name');

                $('#deleteCandidateId').val(candidateId);
                $('#deleteCandidateName').text(candidateName);
                $('#deleteCandidateModal').modal('show');
            });

            // Form submission handler
            $('#deleteCandidateForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('[type="submit"]');
                const cancelBtn = form.find('[data-dismiss="modal"]');

                // Disable buttons and show loading state
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Deleting...');
                cancelBtn.prop('disabled', true);

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            toastr.success(response.message);

                            // Close modal
                            $('#deleteCandidateModal').modal('hide');

                            // Refresh page after 1 second
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            // Show error message
                            toastr.error(response.message || 'Failed to delete candidate');

                            // Reset buttons
                            submitBtn.prop('disabled', false).html('<i class="fas fa-trash-alt mr-1"></i> Delete');
                            cancelBtn.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        // Parse error response
                        let errorMessage = 'An error occurred while deleting the candidate';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            errorMessage = xhr.statusText || errorMessage;
                        }

                        // Show error
                        toastr.error(errorMessage);

                        // Reset buttons
                        submitBtn.prop('disabled', false).html('<i class="fas fa-trash-alt mr-1"></i> Delete');
                        cancelBtn.prop('disabled', false);
                    }
                });
            });

            // Reset form when modal is closed
            $('#deleteCandidateModal').on('hidden.bs.modal', function() {
                $('#deleteCandidateForm')[0].reset();
                const submitBtn = $('#deleteCandidateForm').find('[type="submit"]');
                const cancelBtn = $('#deleteCandidateForm').find('[data-dismiss="modal"]');
                submitBtn.prop('disabled', false).html('<i class="fas fa-trash-alt mr-1"></i> Delete');
                cancelBtn.prop('disabled', false);
            });
        });

        // Add Candidate form submission
        $('#candidateForm').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            if ($('#student_id').val() === '' || $('#position_id').val() === '' ||
                $('#platform').val() === '' || $('#party_list').val() === '' || $('#status').val() === '') {
                toastr.error('All fields are required.');
                return;
            }
            // Fixed: Using $(this) instead of undefined 'form' variable
            const submitBtn = $(this).find('[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            $.ajax({
                url: 'add_candidate.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success(response.message);
                        $('#addCandidateModal').modal('hide');
                        $('#candidateForm')[0].reset();
                        location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('An error occurred while adding the candidate.');
                },
                complete: function() {
                    // Re-enable the submit button when request completes
                    submitBtn.prop('disabled', false).html('Submit');
                }
            });
        });
        $('#addCandidateModal').on('hidden.bs.modal', function() {
            $('#candidateForm')[0].reset();
        });
    });
</script>

<!-- Modified table status indicators -->
<script>
    // Function to update status indicators and improve accessibility
    function updateStatusIndicators() {
        $('table tbody tr').each(function() {
            var statusText = $(this).find('td:last-child span:last-child').text().trim().toLowerCase();
            var statusIndicator = $(this).find('td:last-child span:first-child');

            // Update status indicator classes
            statusIndicator.removeClass('status-active status-pending status-archived');
            statusIndicator.addClass('status-indicator status-' + statusText);

            // Add title attribute for accessibility
            statusIndicator.attr('title', statusText + ' status');
        });
    }

    $(document).ready(function() {
        // Call the function when page loads
        updateStatusIndicators();

        // Also update whenever DataTables redraws
        $('#candidatesTable').on('draw.dt', function() {
            updateStatusIndicators();
        });
    });
</script>
<script src="/plugins/sweet-alert/sweetalert.js"></script>
<script src="/js/logout.js"></script>

</body>
</html>