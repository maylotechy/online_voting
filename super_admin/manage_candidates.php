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
        /* Modern Modal Styles */
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
        /* Add spacing around the filter and search elements */
        .card-header {
            padding: 1rem 1.25rem !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Style for the search input container */
        .dataTables_filter {
            margin-bottom: 1rem;
        }

        /* Style for the search input itself */
        .dataTables_filter input {
            margin-left: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid #ced4da;
        }

        /* Spacing for the filter dropdown */
        .form-inline {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 1rem;
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
                    <li class="nav-item"><a href="manage_admin.php" class="nav-link">
                            <i class="nav-icon fas fa-user-shield"></i><p>College Admins</p></a></li>

                    <li class="nav-item">
                        <a href="manage_candidates.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Candidates</p>
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
            <i class="fas fa-plus-circle"></i> Add Candidates
        </button>
        <div class="form-inline">
            <label for="statusFilter" class="mr-2">Filter by Status:</label>
            <select class="form-control" id="statusFilter">
                <option value="all">All Statuses</option>
                <option value="active">active</option>
                <option value="pending">pending</option>
                <option value="inactive">inactive</option>
            </select>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">List of Candidates</h3>
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
                                        <i class="fas fa-edit"></i></button>

                                    <button class="btn btn-danger btn-sm delete-btn"
                                            data-id="<?= $candidate['candidate_id'] ?>"
                                            data-first-name="<?= htmlspecialchars($candidate['first_name']) ?>"
                                            data-last-name="<?= htmlspecialchars($candidate['last_name']) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                                            $color = '#ffff00'; // yellow
                                            break;
                                        case 'inactive':
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
            <form id="editCandidateForm" action="process_edit_candidate.php" method="POST">
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
                                <label for="editStatus"><i class="fas fa-chart-line mr-1"></i>Status</label>
                                <select class="form-control" id="editStatus" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="inactive">Inactive</option>
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
            <form id="deleteCandidateForm" action="process_delete_candidate.php" method="POST">
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

        // Initialize DataTable
        var table = $('#candidatesTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "columnDefs": [
                { "orderable": false, "targets": [7] } // Disable sorting for Actions column
            ]
        });

        // Status filter functionality
        $('#statusFilter').change(function() {
            var status = $(this).val();
            if (status === 'all') {
                table.columns(8).search('').draw();
            } else {
                table.columns(8).search('^' + status + '$', true, false, true).draw();
            }
        });


        // Search functionality (using DataTables built-in search)
        $('#searchInput').keyup(function(){
            table.search($(this).val()).draw();
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

            // Check if status is "inactive" and prevent editing
            if (status === 'inactive') {
                toastr.error('You cannot edit a candidate with status "Inactive"');
                return; // Stop execution and don't show the modal
            }

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
        $('#editCandidateForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#editCandidateModal').modal('hide');
                        location.reload();
                    } else {
                        toastr.error(response.message || 'Failed to update candidate');
                    }
                },
                error: function() {
                    toastr.error('Error updating candidate');
                }
            });
        });

        // Delete form submission
        $('#deleteCandidateForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: 'process_delete_candidate.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'Candidate deleted successfully');
                        $('#deleteCandidateModal').modal('hide');
                        location.reload();
                    } else {
                        toastr.error(response.message || 'Failed to delete the candidate');
                    }
                },
                error: function() {
                    toastr.error('Error deleting candidate');
                }
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

            $.ajax({
                url: 'add_candidate.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    toastr.info('Processing...', 'Please wait');
                },
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
                }
            });
        });

        // Reset forms when modals are closed
        $('#editCandidateModal').on('hidden.bs.modal', function() {
            $('#editCandidateForm')[0].reset();
        });

        $('#addCandidateModal').on('hidden.bs.modal', function() {
            $('#candidateForm')[0].reset();
        });
    });
</script>

</body>
</html>