<?php
require "../config/db.php";
$pdo = $GLOBALS['pdo'];
// Sample values (replace these with DB queries)
$totalUsers = 200;
$totalAdmins = 14;
$totalColleges = 14;
$totalCourses = 30;
$totalPositions = 10;
$totalCandidates = 40;
$totalVoters = 200;     // Total eligible voters (students)
$votedVoters = 150;     // Number of voters who already voted
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


$voterTurnout = ($totalVoters > 0) ? round(($votedVoters / $totalVoters) * 100, 2) : 0;

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

// Close the database connection
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- AdminLTE + Bootstrap CSS -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <!-- Satoshi Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Satoshi:wght@400;600&display=swap">
    <link rel="stylesheet" href="../custom_css/side-bar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #searchInput{
            width: 250px;
            margin-left: auto;
            margin-top: 20px;
            margin-right: 5px;
            display: block;
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

                    <li class="nav-item"><a href="dashboard.php" class="nav-link active">
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

    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
        <h2>Super Admin Dashboard</h2>

        <!-- Cards -->
        <div class="row mt-3">
            <div class="col-md-3 col-sm-6">
                <a href="users.php" class="text-decoration-none">
                    <div class="small-box bg-info">
                        <div class="inner"><h3><?= $totalStudents ?></h3><p>Total Students</p></div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="manage_admin.php" class="text-decoration-none">
                    <div class="small-box bg-warning">
                        <div class="inner"><h3><?= $totalAdmins ?></h3><p>Total College Admins</p></div>
                        <div class="icon"><i class="fas fa-user-shield"></i></div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="colleges.php" class="text-decoration-none">
                    <div class="small-box bg-success">
                        <div class="inner"><h3><?= $totalColleges ?></h3><p>Total Colleges</p></div>
                        <div class="icon"><i class="fas fa-university"></i></div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="courses.php" class="text-decoration-none">
                    <div class="small-box bg-danger">
                        <div class="inner"><h3><?= $totalCourses ?></h3><p>Total Courses</p></div>
                        <div class="icon"><i class="fas fa-book"></i></div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Students per Course and Year -->
        <div class="card mt-4">
            <div class="card-header"><h3 class="card-title">Students per Course and Year Level</h3></div>
            <div class="card-body p-0">
                <!-- Search box -->
                <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search Courses...">

                <table class="table table-bordered table-hover">
                    <thead>
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
                <div class="pagination p-3 d-flex justify-content-center">
                    <nav>
                        <ul class="pagination mb-0">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?= $currentPage - 1 ?>">Previous</a></li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?= $currentPage + 1 ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>


        <!-- Election Data Overview - Pie Chart and Bar Chart -->
        <div class="card mt-4">
            <div class="card-header"><h3 class="card-title">Election Data Overview</h3></div>
            <div class="card-body">
                <!-- Pie Chart -->
                <canvas id="electionPieChart"></canvas>
                <br><br>
                <!-- Bar Chart -->
                <canvas id="electionBarChart"></canvas>
            </div>
        </div>

    </div>
</div>

<!-- Scripts -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script>
    // Get the input field and the table
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('table'); // Assuming you have a table element
    const rows = table.querySelectorAll('tr');

    // Add an event listener to the input to filter the table
    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase(); // Convert input to lowercase
        rows.forEach(row => {
            const cells = row.getElementsByTagName('td'); // Get table cells in each row
            if (cells.length > 0) { // Ensure we're only checking rows with cells
                const courseName = cells[0].textContent.toLowerCase(); // Assuming course name is in the first column
                // Check if the course name matches the filter
                if (courseName.indexOf(filter) > -1) {
                    row.style.display = ''; // Show row if it matches
                } else {
                    row.style.display = 'none'; // Hide row if it doesn't match
                }
            }
        });
    });
</script>

<script>
    // Pie Chart
    const ctxPie = document.getElementById('electionPieChart').getContext('2d');
    const electionPieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Total Positions', 'Total Candidates', 'Voter Turnout'],
            datasets: [{
                data: [<?= $totalPositions ?>, <?= $totalCandidates ?>, <?= $voterTurnout ?>],
                backgroundColor: ['#007bff', '#28a745', '#dc3545'],
            }]
        },
        options: {
            responsive: true,
            aspectRatio: 2
        }
    });

    // Bar Chart
    const ctxBar = document.getElementById('electionBarChart').getContext('2d');
    const electionBarChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Total Positions', 'Total Candidates', 'Voter Turnout'],
            datasets: [{
                label: 'Election Data',
                data: [<?= $totalPositions ?>, <?= $totalCandidates ?>, <?= $voterTurnout ?>],
                backgroundColor: ['#007bff', '#28a745', '#dc3545'],
                borderColor: ['#0056b3', '#218838', '#c82333'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,

            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>
