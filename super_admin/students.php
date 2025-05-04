<?php
require "../config/db.php";
$pdo = $GLOBALS['pdo'];

// Get filter values from query string
$votedFilter = isset($_GET['voted']) ? $_GET['voted'] : '';
$yearLevelFilter = isset($_GET['year_level']) ? $_GET['year_level'] : '';

// Base query
$sql = "
    SELECT 
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS full_name,
        s.college_id,
        c.college_name,
        s.course_id,
        cr.name AS course_name,
        s.year_level,
        s.cor_number,
        s.email,
        s.has_voted
    FROM 
        students s
    JOIN 
        colleges c ON s.college_id = c.id
    JOIN 
        courses cr ON s.course_id = cr.id
";

// Add filters to query
$whereClauses = [];
$params = [];

if ($votedFilter !== '') {
    $whereClauses[] = "s.has_voted = :has_voted";
    $params[':has_voted'] = ($votedFilter === 'yes') ? 1 : 0;
}

if ($yearLevelFilter !== '') {
    $whereClauses[] = "s.year_level = :year_level";
    $params[':year_level'] = $yearLevelFilter;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY s.last_name, s.first_name";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination variables
$itemsPerPage = 10;
$totalItems = count($students);
$totalPages = ceil($totalItems / $itemsPerPage);

// Get current page from query string or default to 1
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, min($currentPage, $totalPages));

// Slice the array to get only items for the current page
$startIndex = ($currentPage - 1) * $itemsPerPage;
$paginatedStudents = array_slice($students, $startIndex, $itemsPerPage);

// Query for Total Students
$sqlStudents = "SELECT COUNT(*) AS total_students FROM students";
$stmtStudents = $pdo->prepare($sqlStudents);
$stmtStudents->execute();
$totalStudents = $stmtStudents->fetch(PDO::FETCH_ASSOC)['total_students'];

// Query for Total Admins
$sqlAdmins = "SELECT COUNT(*) AS total_admins FROM admins WHERE role_id = 2";
$stmtAdmins = $pdo->prepare($sqlAdmins);
$stmtAdmins->execute();
$totalAdmins = $stmtAdmins->fetch(PDO::FETCH_ASSOC)['total_admins'];

// Query for Total Colleges
$sqlColleges = "SELECT COUNT(*) AS total_colleges FROM colleges";
$stmtColleges = $pdo->prepare($sqlColleges);
$stmtColleges->execute();
$totalColleges = $stmtColleges->fetch(PDO::FETCH_ASSOC)['total_colleges'];

// Query for Total Courses
$sqlCourses = "SELECT COUNT(*) AS total_courses FROM courses";
$stmtCourses = $pdo->prepare($sqlCourses);
$stmtCourses->execute();
$totalCourses = $stmtCourses->fetch(PDO::FETCH_ASSOC)['total_courses'];

// Query for voting statistics
$sqlVoted = "SELECT COUNT(*) AS voted FROM students WHERE has_voted = 1";
$stmtVoted = $pdo->prepare($sqlVoted);
$stmtVoted->execute();
$votedStudents = $stmtVoted->fetch(PDO::FETCH_ASSOC)['voted'];

$voterTurnout = ($totalStudents > 0) ? round(($votedStudents / $totalStudents) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- AdminLTE + Bootstrap CSS -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <!-- Satoshi Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Satoshi:wght@400;600&display=swap">
    <link rel="stylesheet" href="../custom_css/side-bar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        <style>
        .bg-dark {
            background-color: #fffffc !important;
        }

        .filter-controls {
            display: flex;
            align-items: center;
        }

        .has-voted {
            color: #28a745;
        }

        .not-voted {
            color: #dc3545;
        }

        /* DataTables styling */
        .dataTables_wrapper .dataTables_paginate {
            padding: 15px 0;
            margin-top: 20px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 3px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #007bff;
            color: white !important;
            border-color: #007bff;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            color: #fffffc !important;
            background: transparent;
            border-color: transparent;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

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
                        <a href="manage_candidates.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Candidates</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="students.php" class="nav-link active">
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

    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
        <h2>Student Management</h2>
        <div class="mb-3">
            <button class="btn btn-success" data-toggle="modal" data-target="#addStudentModal">
                <i class="fas fa-user-plus mr-2"></i>Add Student
            </button>
        </div>

        <!-- Cards -->
        <div class="row mt-3">
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-info">
                    <div class="inner"><h3><?= $totalStudents ?></h3><p>Total Students</p></div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-success">
                    <div class="inner"><h3><?= $votedStudents ?></h3><p>Voted Students</p></div>
                    <div class="icon"><i class="fas fa-vote-yea"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-warning">
                    <div class="inner"><h3><?= $totalStudents - $votedStudents ?></h3><p>Not Voted</p></div>
                    <div class="icon"><i class="fas fa-user-times"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-primary">
                    <div class="inner"><h3><?= $voterTurnout ?>%</h3><p>Voter Turnout</p></div>
                    <div class="icon"><i class="fas fa-percentage"></i></div>
                </div>
            </div>
        </div>


        <!-- Students Table -->
        <div class="card mt-4">
            <div class="card-body p-0">
                <!-- Filters Section -->
                <div class="bg-gradient-white p-3 d-flex align-items-center">
                    <div class="filter-controls">
                        <div class="form-group mb-0 mr-3">
                            <label for="votedFilter" class="text-dark mr-2">Voted:</label>
                            <select class="form-control" id="votedFilter" style="width: 120px; display: inline-block;">
                                <option value="">All</option>
                                <option value="yes" <?= $votedFilter === 'yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="no" <?= $votedFilter === 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group mb-0 mr-3">
                            <label for="yearLevelFilter" class="text-dark mr-2">Year Level:</label>
                            <select class="form-control" id="yearLevelFilter" style="width: 120px; display: inline-block;">
                                <option value="">All</option>
                                <option value="1" <?= $yearLevelFilter === '1' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2" <?= $yearLevelFilter === '2' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3" <?= $yearLevelFilter === '3' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4" <?= $yearLevelFilter === '4' ? 'selected' : '' ?>>4th Year</option>
                                <option value="5" <?= $yearLevelFilter === '5' ? 'selected' : '' ?>>5th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="search-control ml-auto">
                        <div class="input-group" style="width: 250px;">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>College</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>COR Number</th>
                        <th>Email</th>
                        <th>Voted</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paginatedStudents as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['full_name']) ?></td>
                            <td><?= htmlspecialchars($student['college_name']) ?></td>
                            <td><?= htmlspecialchars($student['course_name']) ?></td>
                            <td><?= htmlspecialchars($student['year_level']) ?></td>
                            <td><?= htmlspecialchars($student['cor_number']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td class="<?= $student['has_voted'] ? 'has-voted' : 'not-voted' ?>">
                                <i class="fas <?= $student['has_voted'] ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                <?= $student['has_voted'] ? 'Yes' : 'No' ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" data-id="<?= $student['student_id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $student['student_id'] ?>" data-name="<?= htmlspecialchars($student['full_name']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination p-3 d-flex justify-content-center">
                    <nav>
                        <ul class="pagination mb-0">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $votedFilter ? '&voted='.$votedFilter : '' ?><?= $yearLevelFilter ? '&year_level='.$yearLevelFilter : '' ?>">Previous</a></li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $votedFilter ? '&voted='.$votedFilter : '' ?><?= $yearLevelFilter ? '&year_level='.$yearLevelFilter : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $votedFilter ? '&voted='.$votedFilter : '' ?><?= $yearLevelFilter ? '&year_level='.$yearLevelFilter : '' ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- JavaScript -->
        <script src="../plugins/jquery/jquery.min.js"></script>
        <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script>
            $(document).ready(function() {
                // Custom search functionality
                $('#searchInput').on('keyup', function() {
                    const searchTerm = $(this).val().toLowerCase();

                    $('table tbody tr').each(function() {
                        const rowText = $(this).text().toLowerCase();
                        $(this).toggle(rowText.includes(searchTerm));
                    });

                    // Hide pagination when searching
                    if (searchTerm.length > 0) {
                        $('.pagination').hide();
                    } else {
                        $('.pagination').show();
                    }
                });

                // Filter change handlers (server-side)
                $('#votedFilter, #yearLevelFilter').on('change', function() {
                    var votedValue = $('#votedFilter').val();
                    var yearLevelValue = $('#yearLevelFilter').val();

                    var queryParams = [];
                    if (votedValue) queryParams.push('voted=' + votedValue);
                    if (yearLevelValue) queryParams.push('year_level=' + yearLevelValue);

                    var currentPage = <?= $currentPage ?>;
                    if (currentPage > 1) queryParams.push('page=' + currentPage);

                    window.location.href = 'students.php?' + queryParams.join('&');
                });
            });
        </script>
</div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="addStudentForm" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addStudentModalLabel"><i class="fas fa-user-plus mr-2"></i>Add New Student</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id">Student ID</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" required>
                                </div>
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="college_id">College</label>
                                    <select class="form-control" id="college_id" name="college_id" required>
                                        <option value="">Select College</option>
                                        <?php
                                        $colleges = $pdo->query("SELECT id, college_name FROM colleges")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($colleges as $college): ?>
                                            <option value="<?= $college['id'] ?>"><?= htmlspecialchars($college['college_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="course_id">Course</label>
                                    <select class="form-control" id="course_id" name="course_id" required>
                                        <option value="">Select Course</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="year_level">Year Level</label>
                                    <select class="form-control" id="year_level" name="year_level" required>
                                        <option value="">Select Year Level</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                        <option value="5">5th Year</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cor_number">COR Number</label>
                                    <input type="text" class="form-control" id="cor_number" name="cor_number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="editStudentForm" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editStudentModalLabel"><i class="fas fa-user-edit mr-2"></i>Edit Student</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="original_student_id" name="original_student_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_first_name">Student ID</label>
                                    <input type="text" class="form-control" id="edit_student_id" name="student_id" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_first_name">First Name</label>
                                    <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_last_name">Last Name</label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_email">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_college_id">College</label>
                                    <select class="form-control" id="edit_college_id" name="college_id" required>
                                        <option value="">Select College</option>
                                        <?php foreach ($colleges as $college): ?>
                                            <option value="<?= $college['id'] ?>"><?= htmlspecialchars($college['college_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit_course_id">Course</label>
                                    <select class="form-control" id="edit_course_id" name="course_id" required>
                                        <option value="">Select Course</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit_year_level">Year Level</label>
                                    <select class="form-control" id="edit_year_level" name="year_level" required>
                                        <option value="">Select Year Level</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                        <option value="5">5th Year</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit_cor_number">COR Number</label>
                                    <input type="text" class="form-control" id="edit_cor_number" name="cor_number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1" role="dialog" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteStudentModalLabel"><i class="fas fa-trash-alt mr-2"></i>Confirm Delete</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete student <strong id="deleteStudentName"></strong>?</p>
                    <input type="hidden" id="delete_student_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteStudent">Delete</button>
                </div>
            </div>
        </div>
    </div>



    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load courses based on selected college
            $('#college_id, #edit_college_id').change(function() {
                const collegeId = $(this).val();
                const targetSelect = $(this).attr('id') === 'college_id' ? '#course_id' : '#edit_course_id';

                $(targetSelect).html('<option value="">Loading courses...</option>');

                if (collegeId) {
                    $.ajax({
                        url: 'get_courses.php',
                        type: 'GET',
                        data: { college_id: collegeId },
                        success: function(response) {
                            let options = '<option value="">Select Course</option>';
                            response.forEach(course => {
                                options += `<option value="${course.id}">${course.name}</option>`;
                            });
                            $(targetSelect).html(options);
                        },
                        error: function() {
                            $(targetSelect).html('<option value="">Failed to load courses</option>');
                        }
                    });
                } else {
                    $(targetSelect).html('<option value="">Select Course</option>');
                }
            });

            // Add Student Form Submission
            $('#addStudentForm').submit(function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Adding...');
                $.ajax({
                    url: 'add_student.php',
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#addStudentModal').modal('hide');
                            form[0].reset();
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to add student');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('Add Student');
                    }
                });
            });

            // Edit Student Button Click
            $('.edit-btn').click(function() {
                const studentId = $(this).data('id');

                $.ajax({
                    url: 'get_student.php',
                    type: 'GET',
                    data: { student_id: studentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const student = response.data;
                            $('#edit_student_id').val(student.student_id);
                            $('#original_student_id').val(student.student_id);
                            $('#edit_first_name').val(student.first_name);
                            $('#edit_last_name').val(student.last_name);
                            $('#edit_email').val(student.email);
                            $('#edit_college_id').val(student.college_id).trigger('change');
                            $('#edit_year_level').val(student.year_level);
                            $('#edit_cor_number').val(student.cor_number);

                            // Set course after college courses are loaded
                            setTimeout(() => {
                                $('#edit_course_id').val(student.course_id);
                            }, 500);

                            $('#editStudentModal').modal('show');
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to load student data');
                    }
                });
            });

            // Edit Student Form Submission
            $('#editStudentForm').submit(function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('[type="submit"]');

                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

                $.ajax({
                    url: 'edit_student.php',
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#editStudentModal').modal('hide');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to update student');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('Save Changes');
                    }
                });
            });

            // Delete Student Button Click
            $('.delete-btn').click(function() {
                const studentId = $(this).data('id');
                const studentName = $(this).data('name');

                $('#delete_student_id').val(studentId);
                $('#deleteStudentName').text(studentName);
                $('#deleteStudentModal').modal('show');
            });

            // Confirm Delete Student
            $('#confirmDeleteStudent').click(function() {
                const studentId = $('#delete_student_id').val();
                const btn = $(this);

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Deleting...');

                $.ajax({
                    url: 'delete_student.php',
                    type: 'POST',
                    data: { student_id: studentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#deleteStudentModal').modal('hide');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to delete student');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('Delete');
                    }
                });
            });
        });
    </script>

<!-- Scripts -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#studentsTable').DataTable({
            "paging": false, // We're using custom pagination
            "searching": false, // We're using custom search
            "ordering": true,
            "info": false,
            "autoWidth": false,
            "responsive": true
        });

        // Custom search functionality
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Filter change handlers
        $('#votedFilter, #yearLevelFilter').on('change', function() {
            var votedValue = $('#votedFilter').val();
            var yearLevelValue = $('#yearLevelFilter').val();

            // Build the query string
            var queryParams = [];
            if (votedValue) queryParams.push('voted=' + votedValue);
            if (yearLevelValue) queryParams.push('year_level=' + yearLevelValue);

            // Keep the current page if it's set
            var currentPage = <?= $currentPage ?>;
            if (currentPage > 1) queryParams.push('page=' + currentPage);

            // Redirect with new filters
            window.location.href = 'students.php?' + queryParams.join('&');
        });

        // Preserve filters when clicking pagination links
        $('.pagination a').on('click', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            var votedValue = $('#votedFilter').val();
            var yearLevelValue = $('#yearLevelFilter').val();

            if (votedValue) {
                url += (url.includes('?') ? '&' : '?') + 'voted=' + votedValue;
            }
            if (yearLevelValue) {
                url += (url.includes('?') ? '&' : '?') + 'year_level=' + yearLevelValue;
            }

            window.location.href = url;
        });
    });
</script>
    <script src="/plugins/sweet-alert/sweetalert.js"></script>
    <script src="/js/logout.js"></script>
</body>
</html>