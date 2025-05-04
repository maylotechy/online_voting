<?php
global $pdo;
require '../config/db.php';
session_start();

// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}

// Fetch all elections from database
try {
    // University-wide elections (USG)
    $usg_stmt = $pdo->query("
        SELECT e.*, 
               username AS creator_name,
               COUNT(c.id) AS candidate_count
        FROM elections e
        LEFT JOIN admins a ON e.created_by = a.id
        LEFT JOIN candidates c ON e.id = c.election_id
        WHERE e.scope = 'university-wide'
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ");
    $usg_elections = $usg_stmt->fetchAll(PDO::FETCH_ASSOC);

    // College-based elections (LSG)
    $lsg_stmt = $pdo->query("
        SELECT e.*, 
               username AS creator_name,
               COUNT(c.id) AS candidate_count
        FROM elections e
        LEFT JOIN admins a ON e.created_by = a.id
        LEFT JOIN candidates c ON e.id = c.election_id
        WHERE e.scope = 'college'
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ");
    $lsg_elections = $lsg_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Status color mapping (pastel colors)
$statusColors = [
    'draft' => 'bg-pastel-blue',
    'ongoing' => 'bg-pastel-green',
    'paused' => 'bg-pastel-yellow',
    'completed' => 'bg-pastel-purple',
    'archived' => 'bg-pastel-gray'
];

// Status text colors
$statusTextColors = [
    'draft' => 'text-pastel-blue-dark',
    'ongoing' => 'text-pastel-green-dark',
    'paused' => 'text-pastel-yellow-dark',
    'completed' => 'text-pastel-purple-dark',
    'archived' => 'text-pastel-gray-dark'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- AdminLTE + Bootstrap CSS -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../custom_css/side-bar.css">
    <style>
        :root {
            --pastel-blue: #a7c7e7;
            --pastel-green: #c1e1c1;
            --pastel-yellow: #fdfd96;
            --pastel-purple: #d8bfd8;
            --pastel-gray: #d3d3d3;
            --pastel-orange: #ffd8b1;
            --pastel-pink: #ffb6c1;
            --pastel-blue-dark: #5d8fc9;
            --pastel-green-dark: #5d9d5d;
            --pastel-yellow-dark: #c9c93d;
            --pastel-purple-dark: #9d5d9d;
            --pastel-gray-dark: #6c757d;
            --pastel-orange-dark: #e68a00;
            --pastel-pink-dark: #d87093;
        }

        .bg-pastel-blue { background-color: var(--pastel-blue); }
        .bg-pastel-green { background-color: var(--pastel-green); }
        .bg-pastel-yellow { background-color: var(--pastel-yellow); }
        .bg-pastel-purple { background-color: var(--pastel-purple); }
        .bg-pastel-gray { background-color: var(--pastel-gray); }
        .bg-pastel-orange { background-color: var(--pastel-orange); }
        .bg-pastel-pink { background-color: var(--pastel-pink); }

        .bg-pastel-blue-full { background-color: var(--pastel-blue); color: var(--pastel-blue-dark); }
        .bg-pastel-green-full { background-color: var(--pastel-green); color: var(--pastel-green-dark); }
        .bg-pastel-yellow-full { background-color: var(--pastel-yellow); color: var(--pastel-yellow-dark); }
        .bg-pastel-purple-full { background-color: var(--pastel-purple); color: var(--pastel-purple-dark); }
        .bg-pastel-gray-full { background-color: var(--pastel-gray); color: var(--pastel-gray-dark); }
        .bg-pastel-orange-full { background-color: var(--pastel-orange); color: var(--pastel-orange-dark); }
        .bg-pastel-pink-full { background-color: var(--pastel-pink); color: var(--pastel-pink-dark); }

        .text-pastel-blue-dark { color: var(--pastel-blue-dark); }
        .text-pastel-green-dark { color: var(--pastel-green-dark); }
        .text-pastel-yellow-dark { color: var(--pastel-yellow-dark); }
        .text-pastel-purple-dark { color: var(--pastel-purple-dark); }
        .text-pastel-gray-dark { color: var(--pastel-gray-dark); }
        .text-pastel-orange-dark { color: var(--pastel-orange-dark); }
        .text-pastel-pink-dark { color: var(--pastel-pink-dark); }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .election-card {
            border-radius: 12px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            color: inherit;
        }

        .election-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .election-card .card-header {
            background-color: rgba(255,255,255,0.3) !important;
            border-bottom: 1px solid rgba(255,255,255,0.5) !important;
            border-radius: 12px 12px 0 0 !important;
        }

        .election-card .card-body {
            padding: 1.5rem;
        }

        .election-card .card-footer {
            background-color: rgba(255,255,255,0.3) !important;
            border-top: 1px solid rgba(255,255,255,0.5) !important;
            border-radius: 0 0 12px 12px !important;
        }

        .section-title {
            position: relative;
            padding-left: 15px;
            margin-bottom: 25px;
        }

        .section-title:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            border-radius: 2px;
        }

        .usg-title:before {
            background-color: var(--pastel-blue-dark);
        }

        .lsg-title:before {
            background-color: var(--pastel-purple-dark);
        }

        .no-data-card {
            border: 2px dashed rgba(0,0,0,0.1);
            background-color: rgba(255,255,255,0.5);
        }

        .no-data-badge {
            background-color: #ff6b6b;
            color: white;
        }

        .meta-icon {
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }

        .btn-outline-light {
            border-color: rgba(255,255,255,0.5);
            color: inherit;
        }

        .btn-outline-light:hover {
            background-color: rgba(255,255,255,0.2);
            color: inherit;
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
                        <a href="students.php" class="nav-link">
                            <i class="nav-icon fas fa-user"></i>
                            <p>Students</p>
                        </a>
                    </li>
                    <li class="nav-item"><a href="create_elections.php" class="nav-link">
                            <i class="nav-icon fas fa-rocket"></i><p>Launch Election</p></a></li>
                    <li class="nav-item"><a href="results.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i><p>Election Results</p></a></li>
                    <li class="nav-item"><a href="election_history.php" class="nav-link active">
                            <i class="nav-icon fas fa-history"></i><p>Election History</p></a></li>
                    <li class="nav-item"><a href="export_results.php" class="nav-link">
                            <i class="nav-icon fas fa-download"></i><p>Export Results</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Election Records</h2>
                        <a href="create_elections.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle mr-1"></i> Create New
                        </a>
                    </div>

                    <!-- University-wide Elections (USG) Section -->
                    <div class="mb-5">
                        <h3 class="section-title usg-title">University Student Government (USG) Elections</h3>
                        <div class="row">
                            <?php if (empty($usg_elections)): ?>
                                <div class="col-12">
                                    <div class="card no-data-card text-center py-5">
                                        <i class="fas fa-university fa-3x mb-3" style="color: var(--pastel-blue-dark);"></i>
                                        <h4>No USG Elections Found</h4>
                                        <p class="text-muted">No university-wide elections have been created yet</p>
                                        <span class="badge no-data-badge">No Data</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php
                                $usg_colors = ['bg-pastel-blue-full', 'bg-pastel-green-full', 'bg-pastel-orange-full', 'bg-pastel-pink-full'];
                                foreach ($usg_elections as $index => $election):
                                    $bgClass = $usg_colors[$index % count($usg_colors)];
                                    $statusClass = strtolower($election['status']);
                                    $statusColor = $statusColors[$statusClass] ?? 'bg-pastel-gray';
                                    $textColor = $statusTextColors[$statusClass] ?? 'text-pastel-gray-dark';
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card election-card h-100 <?= $bgClass ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0"><?= htmlspecialchars($election['title']) ?></h5>
                                                <span class="status-badge <?= $statusColor ?> <?= $textColor ?>">
                                                    <?= ucfirst($election['status']) ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><?= htmlspecialchars($election['description']) ?></p>

                                                <div class="election-meta mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-user-tie meta-icon"></i>
                                                        <span><?= $election['candidate_count'] ?> candidates</span>
                                                    </div>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-calendar-alt meta-icon"></i>
                                                        <span>
                                                            <?= date('M j, Y', strtotime($election['start_time'])) ?> -
                                                            <?= date('M j, Y', strtotime($election['end_time'])) ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user meta-icon"></i>
                                                        <span>Created by <?= htmlspecialchars($election['creator_name']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer action-btns">
                                                <a href="view_election.php?id=<?= $election['id'] ?>"
                                                   class="btn btn-sm btn-outline-light">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php if ($election['status'] === 'draft'): ?>
                                                    <a href="edit_election.php?id=<?= $election['id'] ?>"
                                                       class="btn btn-sm btn-outline-light">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                <?php elseif ($election['status'] === 'ongoing'): ?>
                                                    <a href="pause_election.php?id=<?= $election['id'] ?>"
                                                       class="btn btn-sm btn-outline-light">
                                                        <i class="fas fa-pause"></i> Pause
                                                    </a>
                                                    <a href="end_election.php?id=<?= $election['id'] ?>"
                                                       class="btn btn-sm btn-outline-light">
                                                        <i class="fas fa-stop-circle"></i> End
                                                    </a>
                                                <?php elseif ($election['status'] === 'paused'): ?>
                                                    <a href="resume_election.php?id=<?= $election['id'] ?>"
                                                       class="btn btn-sm btn-outline-light">
                                                        <i class="fas fa-play"></i> Resume
                                                    </a>
                                                <?php endif; ?>
                                                <a href="election_results.php?id=<?= $election['id'] ?>"
                                                   class="btn btn-sm btn-outline-light">
                                                    <i class="fas fa-chart-bar"></i> Results
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- College-based Elections (LSG) Section -->
                    <div class="mb-5">
                        <h3 class="section-title lsg-title">Local Student Government (LSG) Elections</h3>
                        <div class="row">
                            <?php if (empty($lsg_elections)): ?>
                                <div class="col-12">
                                    <div class="card no-data-card text-center py-5">
                                        <i class="fas fa-building fa-3x mb-3" style="color: var(--pastel-purple-dark);"></i>
                                        <h4>No LSG Elections Found</h4>
                                        <p class="text-muted">No college-based elections are currently available</p>
                                        <span class="badge no-data-badge">No Data</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($lsg_elections as $election):
                                    $statusClass = strtolower($election['status']);
                                    $statusColor = $statusColors[$statusClass] ?? 'bg-pastel-gray';
                                    $textColor = $statusTextColors[$statusClass] ?? 'text-pastel-gray-dark';
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card election-card h-100 bg-pastel-purple-full">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0"><?= htmlspecialchars($election['title']) ?></h5>
                                                <span class="status-badge <?= $statusColor ?> <?= $textColor ?>">
                                                    <?= ucfirst($election['status']) ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><?= htmlspecialchars($election['description']) ?></p>

                                                <div class="election-meta mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-user-tie meta-icon"></i>
                                                        <span><?= $election['candidate_count'] ?> candidates</span>
                                                    </div>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-calendar-alt meta-icon"></i>
                                                        <span>
                                                            <?= date('M j, Y', strtotime($election['start_time'])) ?> -
                                                            <?= date('M j, Y', strtotime($election['end_time'])) ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-user meta-icon"></i>
                                                        <span>Created by <?= htmlspecialchars($election['creator_name']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer action-btns">
                                                <a href="view_election.php?id=<?= $election['id'] ?>"
                                                   class="btn btn-sm btn-outline-light">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php if ($election['status'] === 'draft'): ?>
                                                    <a href="edit_election.php?id=<?= $election['id'] ?>"
                                                       class="btn btn-sm btn-outline-light">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                <?php elseif ($election['status'] === 'ongoing'): ?>
                                                    <a href="pause_election.php?id=<?= $election['id'] ?>"
                                                       class="btn btn-sm btn-outline-light">
                                                        <i class="fas fa-pause"></i> Pause
                                                    </a>
                                                    <a href="end_election.php?id=<?= $election['id'] ?>"
                                                       class="btn btn-sm btn-outline-light">
                                                        <i class="fas fa-stop-circle"></i> End
                                                    </a>
                                                <?php elseif ($election['status'] === 'paused'): ?>
                                                    <a href="resume_election.php?id=<?= $election['id'] ?>"
                                                       class="btn btn-sm btn-outline-light">
                                                        <i class="fas fa-play"></i> Resume
                                                    </a>
                                                <?php endif; ?>
                                                <a href="election_results.php?id=<?= $election['id'] ?>"
                                                   class="btn btn-sm btn-outline-light">
                                                    <i class="fas fa-chart-bar"></i> Results
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<script src="/plugins/sweet-alert/sweetalert.js"></script>
<script src="/js/logout.js"></script>
</body>
</html>