<?php
require '../config/db.php';
$pdo = $GLOBALS['pdo'];

// Define the ordered positions
$orderedPositions = [
    'President',
    'Vice President',
    'Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer',
    'Business Manager',
    'Senator'
];

// Fetch positions and candidates from the database
try {
    // Fetch candidates based on the query
    $query = "
    SELECT 
        c.id AS candidate_id,
        CONCAT(s.first_name, ' ', s.last_name) AS name,
        c.position_id,
        p.name AS position_name, 
        c.party_list,
        c.platform,
        col.college_name AS college_name
    FROM candidates c
    JOIN students s ON c.student_id = s.student_id
    JOIN positions p ON c.position_id = p.id
    JOIN colleges col ON s.college_id = col.id
    WHERE c.status = 'active'
    ORDER BY 
        CASE p.name
            WHEN 'President' THEN 1
            WHEN 'Vice President' THEN 2
            WHEN 'Secretary' THEN 3
            WHEN 'Treasurer' THEN 4
            WHEN 'Auditor' THEN 5
            WHEN 'Public Information Officer' THEN 6
            WHEN 'Business Manager' THEN 7
            WHEN 'Senator' THEN 8
            ELSE 9
        END
";
    $candidates_stmt = $pdo->query($query);
    $candidates = $candidates_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize grouped candidates array with all positions in order
    $groupedCandidates = [];
    foreach ($orderedPositions as $position) {
        $groupedCandidates[$position] = ['position_name' => $position, 'candidates' => []];
    }

    // Populate with actual candidates
    foreach ($candidates as $candidate) {
        if (isset($groupedCandidates[$candidate['position_name']])) {
            $groupedCandidates[$candidate['position_name']]['candidates'][] = $candidate;
        }
    }

    // Filter out positions with no candidates
    $groupedCandidates = array_filter($groupedCandidates, function($group) {
        return !empty($group['candidates']);
    });

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Launch University Election</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- AdminLTE + Bootstrap CSS -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../custom_css/side-bar.css">
    <style>
        .position-card {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .candidate-card {
            margin-top: 15px;
            padding: 15px;
            border: 1px dashed #ddd;
            background-color: #f4f4f4;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .party-badge {
            padding: 0.35em 0.65em;
            font-size: 0.85em;
        }

        .file-upload-wrapper {
            transition: all 0.3s ease;
        }

        .file-upload-wrapper:hover {
            background-color: rgba(13, 110, 253, 0.05) !important;
        }

        .rounded-pill {
            padding: 0.5rem 1.5rem;
        }
        .card-footer .btn {
            margin-right: 1rem;
        }
        .college-badge i {
            margin-right: 6px;
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
                    <li class="nav-item"><a href="create_elections.php" class="nav-link active">
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
        <h2>Launch University-Wide Election</h2>

        <form action="process_create_election.php" method="POST" enctype="multipart/form-data">
            <div class="card shadow-lg">
                <div class="card-header bg-gradient-primary text-white">
                    <h3 class="card-title mb-0">Create New Election</h3>
                </div>

                <div class="card-body">
                    <!-- Election Title -->
                    <div class="form-group mb-4">
                        <label for="electionTitle" class="form-label fw-bold">Election Title</label>
                        <input type="text" class="form-control form-control-lg border-2" id="electionTitle" name="title" placeholder="Enter election title" required>
                    </div>

                    <!-- Election Description -->
                    <div class="form-group mb-4">
                        <label for="electionDescription" class="form-label fw-bold">Description</label>
                        <textarea class="form-control border-2" id="electionDescription" name="description" rows="3" placeholder="Briefly describe the purpose of this election" required></textarea>
                    </div>

                    <!-- Positions and Candidates Section -->
                    <div id="positionsContainer" class="mb-4">
                        <?php foreach ($groupedCandidates as $position_name => $group): ?>
                            <div class="position-card mb-4 p-4 border rounded-3 bg-light-subtle shadow-sm">
                                <h4 class="mb-3 text-primary">
                                    <i class="fas fa-user-tie me-2"></i>
                                    <?= htmlspecialchars($group['position_name']) ?>
                                </h4>

                                <div class="candidatesContainer row g-4 mb-4">
                                    <?php foreach ($group['candidates'] as $index => $candidate): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="candidate-card p-3 border rounded-3 bg-white shadow-sm h-100">
                                                <!-- Candidate Header -->
                                                <div class="d-flex align-items-center mb-3">
                                                    <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                    <h5 class="mb-0">Candidate Profile</h5>
                                                </div>

                                                <!-- Candidate Name -->
                                                <div class="form-group mb-3">
                                                    <label class="form-label small text-muted">Candidate Name</label>
                                                    <input type="text" class="form-control-plaintext font-weight-bold"
                                                           name="candidates[<?= $candidate['candidate_id'] ?>][name]"
                                                           value="<?= htmlspecialchars($candidate['name']) ?>" readonly>
                                                </div>

                                                <!-- Platform -->
                                                <div class="form-group mb-3">
                                                    <label class="form-label small text-muted">Platform</label>
                                                    <div class="p-2 bg-light rounded border">
                                                        <?= nl2br(htmlspecialchars($candidate['platform'])) ?>
                                                    </div>
                                                </div>

                                                <!-- Party List -->
                                                <div class="form-group mb-3">
                                                    <label class="form-label small text-muted">Party List</label>
                                                    <div class="d-flex align-items-center">
                                                        <span class="party-badge badge bg-info bg-opacity-10 text-info">
                                                            <?= htmlspecialchars($candidate['party_list']) ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- College Information -->
                                                <div class="form-group">
                                                    <label class="form-label small text-muted">College</label>
                                                    <div class="d-flex align-items-center">
                                                        <span class="college-badge badge bg-teal bg-opacity-10 text-teal">
                                                            <?= htmlspecialchars($candidate['college_name']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Date Range Picker -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="startDate" class="form-label fw-bold">Start Date & Time</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    <input type="datetime-local" class="form-control border-start-0" id="startDate" name="start_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endDate" class="form-label fw-bold">End Date & Time</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    <input type="datetime-local" class="form-control border-start-0" id="endDate" name="end_date" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="form-group mb-4">
                        <label for="electionRules" class="form-label fw-bold">Election Rules Document</label>
                        <div class="file-upload-wrapper border-2 rounded-3 p-3 bg-light-subtle">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-pdf text-primary me-3 fs-4 mt-1" style="transform: translateY(2px);"></i>
                                <div>
                                    <p class="mb-1 small">Upload PDF file with election rules</p>
                                    <input type="file" class="form-control" id="electionRules" name="election_rules" accept=".pdf" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification -->
                    <div class="alert alert-primary d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-3 fs-5"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Notification will be sent</h6>
                            <p class="mb-0 small">All eligible students will receive a notification with a 6-digit verification code.</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card-footer bg-light d-flex justify-content-end gap-3">
                    <button type="submit" name="draft" class="btn btn-outline-secondary px-4 rounded-pill">
                        <i class="far fa-save me-2"></i>Save Draft
                    </button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">
                        <i class="fas fa-check-circle me-2"></i>Create Election
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</body>
</html>