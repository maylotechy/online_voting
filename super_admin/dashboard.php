<?php
// Sample values (replace these with DB queries)
$totalUsers = 200;
$totalAdmins = 14;
$totalColleges = 14;
$totalCourses = 30;

$studentCounts = [
    ['course' => 'BSIT', 'y1' => 120, 'y2' => 100, 'y3' => 90, 'y4' => 80],
    ['course' => 'BSCpE', 'y1' => 110, 'y2' => 95, 'y3' => 85, 'y4' => 75],
    ['course' => 'BSCE', 'y1' => 130, 'y2' => 115, 'y3' => 100, 'y4' => 90],
];
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
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item d-flex align-items-center mr-3">
                <img src="../asssets/super_admin/osa_profile.jpg" class="img-circle elevation-2" style="width:30px; height:30px;">
                <span class="ml-2 font-weight-bold">OSA (Super Admin)</span>
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

                    <li class="nav-item"><a href="admins.php" class="nav-link">
                            <i class="nav-icon fas fa-user-shield"></i><p>College Admins</p></a></li>

                    <li class="nav-item"><a href="templates.php" class="nav-link">
                            <i class="nav-icon fas fa-file-alt"></i><p>Election Templates</p></a></li>

                    <li class="nav-item"><a href="create_election.php" class="nav-link">
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
                        <div class="inner"><h3><?= $totalUsers ?></h3><p>Total Users</p></div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="admins.php" class="text-decoration-none">
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
                <table class="table table-bordered table-hover">
                    <thead><tr>
                        <th>Course</th><th>1st Year</th><th>2nd Year</th><th>3rd Year</th><th>4th Year</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($studentCounts as $c): ?>
                        <tr>
                            <td><a href="students.php?course=<?= $c['course'] ?>"><?= $c['course'] ?></a></td>
                            <td><?= $c['y1'] ?></td>
                            <td><?= $c['y2'] ?></td>
                            <td><?= $c['y3'] ?></td>
                            <td><?= $c['y4'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Scripts -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
</body>
</html>
