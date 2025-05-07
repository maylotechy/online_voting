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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .has-voted {
            color: #28a745;
        }

        .not-voted {
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
                <a class="nav-link" href="manage_candidates.php">
                    <i class="fas fa-users"></i>
                    <span>Candidates</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="students.php">
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
            <h2 class="mb-4">Student Management</h2>

            <!-- Add Student Button -->
            <div class="mb-4">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-user-plus me-2"></i>Add Student
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card bg-students">
                        <i class="fas fa-users"></i>
                        <div class="count"><?= $totalStudents ?></div>
                        <div class="label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card bg-voted">
                        <i class="fas fa-vote-yea"></i>
                        <div class="count"><?= $votedStudents ?></div>
                        <div class="label">Voted Students</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card bg-not-voted">
                        <i class="fas fa-user-times"></i>
                        <div class="count"><?= $totalStudents - $votedStudents ?></div>
                        <div class="label">Not Voted</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card bg-turnout">
                        <i class="fas fa-percentage"></i>
                        <div class="count"><?= $voterTurnout ?>%</div>
                        <div class="label">Voter Turnout</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-controls mb-4">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label for="votedFilter" class="form-label">Voted:</label>
                            <select class="form-select" id="votedFilter">
                                <option value="">All</option>
                                <option value="yes" <?= $votedFilter === 'yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="no" <?= $votedFilter === 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label for="yearLevelFilter" class="form-label">Year Level:</label>
                            <select class="form-select" id="yearLevelFilter">
                                <option value="">All</option>
                                <option value="1" <?= $yearLevelFilter === '1' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2" <?= $yearLevelFilter === '2' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3" <?= $yearLevelFilter === '3' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4" <?= $yearLevelFilter === '4' ? 'selected' : '' ?>>4th Year</option>
                                <option value="5" <?= $yearLevelFilter === '5' ? 'selected' : '' ?>>5th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label for="searchInput" class="form-label">Search:</label>
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="Search students...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Student Records</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
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
                    </div>

                    <!-- Pagination -->
                    <div class="pagination p-3 d-flex justify-content-center">
                        <nav>
                            <ul class="pagination mb-0">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $votedFilter ? '&voted='.$votedFilter : '' ?><?= $yearLevelFilter ? '&year_level='.$yearLevelFilter : '' ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $votedFilter ? '&voted='.$votedFilter : '' ?><?= $yearLevelFilter ? '&year_level='.$yearLevelFilter : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $votedFilter ? '&voted='.$votedFilter : '' ?><?= $yearLevelFilter ? '&year_level='.$yearLevelFilter : '' ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addStudentForm" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addStudentModalLabel"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
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
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="college_id" class="form-label">College</label>
                                <select class="form-select" id="college_id" name="college_id" required>
                                    <option value="">Select College</option>
                                    <?php
                                    $colleges = $pdo->query("SELECT id, college_name FROM colleges")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($colleges as $college): ?>
                                        <option value="<?= $college['id'] ?>"><?= htmlspecialchars($college['college_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Course</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select class="form-select" id="year_level" name="year_level" required>
                                    <option value="">Select Year Level</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                    <option value="5">5th Year</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="cor_number" class="form-label">COR Number</label>
                                <input type="text" class="form-control" id="cor_number" name="cor_number">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editStudentForm" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editStudentModalLabel"><i class="fas fa-user-edit me-2"></i>Edit Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="original_student_id" name="original_student_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_student_id" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="edit_student_id" name="student_id" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_college_id" class="form-label">College</label>
                                <select class="form-select" id="edit_college_id" name="college_id" required>
                                    <option value="">Select College</option>
                                    <?php foreach ($colleges as $college): ?>
                                        <option value="<?= $college['id'] ?>"><?= htmlspecialchars($college['college_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_course_id" class="form-label">Course</label>
                                <select class="form-select" id="edit_course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_year_level" class="form-label">Year Level</label>
                                <select class="form-select" id="edit_year_level" name="year_level" required>
                                    <option value="">Select Year Level</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                    <option value="5">5th Year</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_cor_number" class="form-label">COR Number</label>
                                <input type="text" class="form-control" id="edit_cor_number" name="cor_number">
                            </div>
                        </div>
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
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteStudentModalLabel"><i class="fas fa-trash-alt me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete student <strong id="deleteStudentName"></strong>?</p>
                <input type="hidden" id="delete_student_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteStudent">Delete</button>
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
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Adding...');
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
                success: function(response){
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
