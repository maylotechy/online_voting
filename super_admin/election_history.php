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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            padding: 0;
            margin: 0;
            overflow-x: hidden;
        }

        .navbar {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        /* Fixed sidebar */
        .sidebar {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
            z-index: 100;
            width: 250px;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 8px 16px;
            transition: all 0.3s;
            font-size: 0.95rem;
            padding: 0.75rem 1rem;
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

        /* Content area */
        .content-wrapper {
            margin-left: 250px; /* Same as sidebar width */
            padding: 2rem;
            width: calc(100% - 250px); /* Remaining width */
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            height: 100%;
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

        .card-footer {
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }

        .btn-outline-primary {
            border: 1px solid #4361ee;
            color: #4361ee;
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-outline-primary:hover {
            background-color: #4361ee;
            color: white;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-draft {
            background-color: rgba(74, 222, 128, 0.15);
            color: #16a34a;
        }

        .status-ongoing {
            background-color: rgba(59, 130, 246, 0.15);
            color: #2563eb;
        }


        .status-completed {
            background-color: rgba(139, 92, 246, 0.15);
            color: #7c3aed;
        }



        .meta-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: #64748b;
        }

        .meta-icon {
            width: 18px;
            margin-right: 10px;
            color: #94a3b8;
        }

        .section-title {
            font-weight: 600;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
            position: relative;
            color: #1e293b;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            border-radius: 3px;
        }

        .usg-title::after {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
        }

        .lsg-title::after {
            background: linear-gradient(135deg, #7209b7 0%, #560bad 100%);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background-color: rgba(255, 255, 255, 0.5);
            border: 2px dashed rgba(203, 213, 225, 0.5);
            border-radius: 16px;
        }

        .empty-icon {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1.5rem;
        }

        .btn-create {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }

        .btn-result {
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            background-color: #f1f5f9;
            color: #4361ee;
            transition: all 0.3s;
        }

        .btn-result:hover {
            background-color: #4361ee;
            color: white;
            transform: translateY(-2px);
        }

        .election-card {
            height: 100%;
        }

        .usg-card {
            border-top: 4px solid #4361ee;
        }

        .lsg-card {
            border-top: 4px solid #7209b7;
        }

        .user-dropdown {
            background-color: #f8f9fa;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .user-dropdown img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 10px;
        }

        /* Mobile responsiveness */
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
        }

        /* For mobile phones */
        @media (max-width: 576px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .content-wrapper {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }

            .navbar {
                padding: 0.5rem 1rem;
            }

            .mobile-menu-toggle {
                display: block;
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
                <a class="nav-link active" href="election_history.php">
                    <i class="fas fa-history"></i>
                    <span>Election History</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded-3 mb-4">
            <div class="container-fluid">
                <button class="navbar-toggler d-md-none" type="button" id="sidebarToggle">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn user-dropdown dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="../asssets/super_admin/usm_comelec.jpg" alt="Admin">
                            <span class="d-none d-lg-inline">USM Comelec</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="Logout()"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Election Records</h2>
                <a href="create_elections.php" class="btn btn-create">
                    <i class="fas fa-plus-circle me-2"></i> Create New Election
                </a>
            </div>

            <!-- University-wide Elections (USG) Section -->
            <div class="mb-5">
                <h3 class="section-title usg-title">University Student Government (USG) Elections</h3>
                <div class="row">
                    <?php if (empty($usg_elections)): ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-university empty-icon"></i>
                                <h4>No USG Elections Found</h4>
                                <p class="text-muted">No university-wide elections have been created yet</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($usg_elections as $election):
                            $statusClass = 'status-' . strtolower($election['status']);
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card election-card usg-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title"><?= htmlspecialchars($election['title']) ?></h5>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= ucfirst($election['status']) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-4"><?= htmlspecialchars($election['description']) ?></p>

                                        <div class="meta-info">
                                            <div class="meta-item">
                                                <i class="fas fa-user-tie meta-icon"></i>
                                                <span><?= $election['candidate_count'] ?> candidates</span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-calendar-alt meta-icon"></i>
                                                <span>
                                                    <?= date('M j, Y', strtotime($election['start_time'])) ?> -
                                                    <?= date('M j, Y', strtotime($election['end_time'])) ?>
                                                </span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-user meta-icon"></i>
                                                <span>Created by <?= htmlspecialchars($election['creator_name']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-center">
                                            <i class="fas fa-chart-bar me-2"></i> View results in result page
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
                            <div class="empty-state">
                                <i class="fas fa-building empty-icon"></i>
                                <h4>No LSG Elections Found</h4>
                                <p class="text-muted">No college-based elections are currently available</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($lsg_elections as $election):
                            $statusClass = 'status-' . strtolower($election['status']);
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card election-card lsg-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title"><?= htmlspecialchars($election['title']) ?></h5>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= ucfirst($election['status']) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-4"><?= htmlspecialchars($election['description']) ?></p>

                                        <div class="meta-info">
                                            <div class="meta-item">
                                                <i class="fas fa-user-tie meta-icon"></i>
                                                <span><?= $election['candidate_count'] ?> candidates</span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-calendar-alt meta-icon"></i>
                                                <span>
                                                    <?= date('M j, Y', strtotime($election['start_time'])) ?> -
                                                    <?= date('M j, Y', strtotime($election['end_time'])) ?>
                                                </span>
                                            </div>
                                            <div class="meta-item">
                                                <i class="fas fa-user meta-icon"></i>
                                                <span>Created by <?= htmlspecialchars($election['creator_name']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-center">
                                            <i class="fas fa-chart-bar me-2"></i> View results in result page
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (if needed) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/plugins/sweet-alert/sweetalert.js"></script>
<script src="/js/logout.js"></script>
<script>
    // Toggle sidebar on mobile
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                sidebar.classList.toggle('d-none');
                sidebar.classList.toggle('d-block');

                // If sidebar is now visible, make it take up the full screen width on mobile
                if (sidebar.classList.contains('d-block')) {
                    sidebar.style.width = '100%';
                    document.body.style.overflow = 'hidden'; // Prevent scrolling behind sidebar
                } else {
                    sidebar.style.width = '';
                    document.body.style.overflow = '';
                }
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');

            if (window.innerWidth <= 576 &&
                sidebar &&
                sidebar.classList.contains('d-block') &&
                !sidebar.contains(event.target) &&
                event.target !== sidebarToggle) {
                sidebar.classList.remove('d-block');
                sidebar.classList.add('d-none');
                document.body.style.overflow = '';
            }
        });
    });
</script>
</body>
</html>