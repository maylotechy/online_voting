<?php
require "../config/db.php";
// Check if user is logged in and is super admin (role_id = 1)
include "../auth_session/auth_check_admin.php";
$pdo = $GLOBALS['pdo'];

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

// Query to get students per course per year level
$sql = "
    SELECT 
        c.name AS course_name,
        s.year_level,
        COUNT(s.id) AS total_students
    FROM 
        courses c
    LEFT JOIN 
        students s ON s.course_id = c.id
    GROUP BY 
        c.name, s.year_level
    ORDER BY 
        c.name, s.year_level;
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

// Fetch the data
$studentCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizing data into a more usable format
$organizedData = [];
foreach ($studentCounts as $row) {
    $course = $row['course_name'];
    $year_level = $row['year_level'];
    $total_students = $row['total_students'];

    // Initialize array for the course if it doesn't exist
    if (!isset($organizedData[$course])) {
        $organizedData[$course] = [
            '1' => 0,  // 1st Year
            '2' => 0,  // 2nd Year
            '3' => 0,  // 3rd Year
            '4' => 0   // 4th Year
        ];
    }

    // Add the student count to the appropriate year level
    $organizedData[$course][$year_level] = $total_students;
}

// 1. Get total positions
$totalPositions = $pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn();

// 2. Get total candidates
$totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();

// 3. Get total students (eligible voters)
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

// 4. Get number of students who have voted
$votedVoters = $pdo->query("SELECT COUNT(*) FROM students WHERE has_voted = 1")->fetchColumn();

// 5. Calculate voter turnout percentage
$voterTurnout = $totalStudents > 0 ? round(($votedVoters / $totalStudents) * 100, 2) : 0;

// Pagination variables
$itemsPerPage = 10;
$totalItems = count($organizedData);
$totalPages = ceil($totalItems / $itemsPerPage);

// Get current page from query string or default to 1
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, min($currentPage, $totalPages));

// Slice the array to get only items for the current page
$startIndex = ($currentPage - 1) * $itemsPerPage;
$paginatedData = array_slice($organizedData, $startIndex, $itemsPerPage, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../custom_css/modern_sidebar.css" rel="stylesheet">
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

    .navbar .container-fluid {
        display: flex;
        justify-content: flex-end;
        align-items: center;
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

    .bg-admins {
        background: linear-gradient(135deg, #7209b7 0%, #560bad 100%);
    }

    .bg-colleges {
        background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
    }

    .bg-courses {
        background: linear-gradient(135deg, #f72585 0%, #e63946 100%);
    }

    #searchInput {
        width: 250px;
        margin-left: auto;
        margin-bottom: 20px;
        display: block;
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
                <a class="nav-link active" href="dashboard.php">
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
            <h2 class="mb-4">Super Admin Dashboard</h2>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <a href="students.php" class="text-decoration-none">
                        <div class="stats-card bg-students">
                            <i class="fas fa-users"></i>
                            <div class="count"><?= $totalStudents ?></div>
                            <div class="label">Total Students</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 col-sm-6">
                    <a href="manage_admin.php" class="text-decoration-none">
                        <div class="stats-card bg-admins">
                            <i class="fas fa-user-shield"></i>
                            <div class="count"><?= $totalAdmins ?></div>
                            <div class="label">College Admins</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 col-sm-6">
                    <a href="colleges.php" class="text-decoration-none">
                        <div class="stats-card bg-colleges">
                            <i class="fas fa-university"></i>
                            <div class="count"><?= $totalColleges ?></div>
                            <div class="label">Colleges</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 col-sm-6">
                    <a href="courses.php" class="text-decoration-none">
                        <div class="stats-card bg-courses">
                            <i class="fas fa-book"></i>
                            <div class="count"><?= $totalCourses ?></div>
                            <div class="label">Courses</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Students per Course and Year -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Students per Course and Year Level</h3>
                </div>
                <div class="card-body p-0">
                    <!-- Search box -->
                    <input type="text" id="searchInput" class="form-control" placeholder="Search Courses...">

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th>1st Year</th>
                                <th>2nd Year</th>
                                <th>3rd Year</th>
                                <th>4th Year</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($paginatedData as $course => $counts): ?>
                                <tr>
                                    <td><?= $course ?></td>
                                    <td><?= $counts['1'] ?></td>
                                    <td><?= $counts['2'] ?></td>
                                    <td><?= $counts['3'] ?></td>
                                    <td><?= $counts['4'] ?></td>
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
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Election Data Overview - Pie Chart and Bar Chart -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Election Data Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="electionPieChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="electionBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (if needed) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('table');
    const rows = table.querySelectorAll('tbody tr');

    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        rows.forEach(row => {
            const cells = row.getElementsByTagName('td');
            if (cells.length > 0) {
                const courseName = cells[0].textContent.toLowerCase();
                if (courseName.indexOf(filter) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });

    // Charts
    document.addEventListener('DOMContentLoaded', function() {
        // Pie Chart
        const ctxPie = document.getElementById('electionPieChart').getContext('2d');
        const electionPieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Total Positions', 'Total Candidates', 'Voter Turnout (%)'],
                datasets: [{
                    data: [<?= $totalPositions ?>, <?= $totalCandidates ?>, <?= $voterTurnout ?>],
                    backgroundColor: ['#4361ee', '#7209b7', '#4cc9f0'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Election Statistics (Pie Chart)'
                    }
                }
            }
        });

        // Bar Chart
        const ctxBar = document.getElementById('electionBarChart').getContext('2d');
        const electionBarChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Total Positions', 'Total Candidates', 'Voter Turnout (%)'],
                datasets: [{
                    label: 'Count',
                    data: [<?= $totalPositions ?>, <?= $totalCandidates ?>, <?= $voterTurnout ?>],
                    backgroundColor: ['#4361ee', '#7209b7', '#4cc9f0'],
                    borderColor: ['#3a0ca3', '#560bad', '#4895ef'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Election Statistics (Bar Chart)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
<script src="/plugins/sweet-alert/sweetalert.js"></script>
<script src="/js/logout.js"></script>
</body>
</html>