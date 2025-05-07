<?php
require '../config/db.php';
include '../auth_session/auth_check_admin.php';

$pdo = $GLOBALS['pdo'];
ob_start();
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

// Display toastr notification if set
if (!empty($_SESSION['toastr']) && is_array($_SESSION['toastr'])) {
    $toastr = $_SESSION['toastr'];
    $alert = '<script>
        $(document).ready(function() {
            toastr.'.json_encode($toastr['type']).'('.json_encode($toastr['message']).');
        });
    </script>';
    unset($_SESSION['toastr']);
} else {
    $alert = '';
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Launch University Election</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Toastr CSS -->
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

        /* Position and Candidate Cards */
        .position-card {
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            background-color: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.03);
        }

        .position-header {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8f9fa;
        }

        .candidate-card {
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            background-color: white;
            transition: all 0.3s;
        }

        .candidate-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .party-badge {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 0.35em 0.65em;
            font-size: 0.85em;
            border-radius: 50px;
        }

        .college-badge {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
            padding: 0.35em 0.65em;
            font-size: 0.85em;
            border-radius: 50px;
        }

        /* Form Elements */
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        /* Launch Button */
        .btn-launch {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
            transition: all 0.3s;
        }

        .btn-launch:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }

        /* Confirmation Modal */
        .confirmation-modal .modal-content {
            border-radius: 16px;
            overflow: hidden;
        }

        .confirmation-modal .modal-header {
            background-color: var(--primary);
            color: white;
            border-bottom: none;
        }

        .confirmation-modal .modal-body {
            padding: 2rem;
            text-align: center;
        }

        .confirmation-modal .modal-footer {
            border-top: none;
            padding: 0 2rem 2rem;
            justify-content: center;
        }

        /* Responsive adjustments */
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
                <a class="nav-link" href="students.php">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="create_elections.php">
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
            <h2 class="mb-4">Launch University-Wide Election</h2>

            <form id="electionForm" action="create_election_backend.php" method="POST" enctype="multipart/form-data">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Create New Election</h3>
                    </div>
                    <div class="card-body">
                        <!-- Election Title -->
                        <div class="mb-4">
                            <label for="electionTitle" class="form-label fw-bold">Election Title</label>
                            <input type="text" class="form-control" id="electionTitle" name="title" placeholder="Enter election title" required>
                        </div>

                        <!-- Election Description -->
                        <div class="mb-4">
                            <label for="electionDescription" class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="electionDescription" name="description" rows="3" placeholder="Briefly describe the purpose of this election" required></textarea>
                        </div>

                        <!-- Positions and Candidates Section -->
                        <div id="positionsContainer" class="mb-4">
                            <?php foreach ($groupedCandidates as $position_name => $group): ?>
                                <div class="position-card">
                                    <div class="position-header">
                                        <h4 class="mb-0">
                                            <i class="fas fa-user-tie me-2"></i>
                                            <?= htmlspecialchars($group['position_name']) ?>
                                        </h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-4">
                                            <?php foreach ($group['candidates'] as $index => $candidate): ?>
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="candidate-card">
                                                        <input type="hidden" name="candidates[<?= $candidate['candidate_id'] ?>][id]" value="<?= $candidate['candidate_id'] ?>">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                            <h5 class="mb-0">Candidate Profile</h5>
                                                        </div>

                                                        <!-- Candidate Name -->
                                                        <div class="mb-3">
                                                            <label class="form-label small text-muted">Candidate Name</label>
                                                            <input type="text" class="form-control-plaintext font-weight-bold"
                                                                   name="candidates[<?= $candidate['candidate_id'] ?>][name]"
                                                                   value="<?= htmlspecialchars($candidate['name']) ?>" readonly>
                                                        </div>

                                                        <!-- Platform -->
                                                        <div class="mb-3">
                                                            <label class="form-label small text-muted">Platform</label>
                                                            <div class="p-2 bg-light rounded">
                                                                <?= nl2br(htmlspecialchars($candidate['platform'])) ?>
                                                            </div>
                                                        </div>

                                                        <!-- Party List -->
                                                        <div class="mb-3">
                                                            <label class="form-label small text-muted">Party List</label>
                                                            <div class="d-flex align-items-center">
                                                                <span class="party-badge">
                                                                    <?= htmlspecialchars($candidate['party_list']) ?>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <!-- College Information -->
                                                        <div class="mb-0">
                                                            <label class="form-label small text-muted">College</label>
                                                            <div class="d-flex align-items-center">
                                                                <span class="college-badge">
                                                                    <?= htmlspecialchars($candidate['college_name']) ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Date Range Picker -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="startDate" class="form-label fw-bold">Start Date & Time</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        <input type="datetime-local" class="form-control" id="startDate" name="start_date" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="endDate" class="form-label fw-bold">End Date & Time</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        <input type="datetime-local" class="form-control" id="endDate" name="end_date" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="electionRules" class="form-label fw-bold">Election Rules Document</label>
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-pdf text-primary me-3 fs-4"></i>
                                    <div>
                                        <p class="mb-1 small">Upload PDF file with election rules</p>
                                        <input type="file" class="form-control" id="electionRules" name="election_rules" accept=".pdf" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification -->
                        <div class="alert alert-primary d-flex align-items-center">
                            <i class="fas fa-info-circle me-3 fs-5"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Notification will be sent</h6>
                                <p class="mb-0 small">All eligible students will receive a notification with a 6-digit verification code.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="card-footer d-flex justify-content-end">
                        <button type="button" id="launchElectionBtn" class="btn btn-primary btn-launch">
                            <i class="fas fa-rocket me-2"></i>
                            Launch Election
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Election Launch</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-circle fa-4x text-warning mb-3"></i>
                        <p>You are about to launch a university-wide election. This action will:</p>
                        <ul class="text-start mb-4">
                            <li>Send verification codes to all eligible students</li>
                            <li>Make the election immediately active</li>
                            <li>Cannot be undone once started</li>
                        </ul>
                        <p class="fw-bold">Are you sure you want to proceed?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmLaunchBtn" class="btn btn-primary">
                        <i class="fas fa-check-circle me-2"></i>Confirm Launch
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    $(document).ready(function() {
        <?= $alert ?>

        // Toastr configuration
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000
        };

        // Show confirmation modal when launch button is clicked
        $('#launchElectionBtn').click(function(e) {
            e.preventDefault();

            if (!$('#electionForm')[0].checkValidity()) {
                $('#electionForm')[0].reportValidity();
                return;
            }

            $('#confirmationModal').modal('show');
        });

        // Handle confirmed launch
        $('#confirmLaunchBtn').click(function() {
            $('#confirmationModal').modal('hide');
            submitElectionForm();
        });

        function submitElectionForm() {
            var form = $('#electionForm');
            var formData = new FormData(form[0]);
            var launchBtn = $('#launchElectionBtn');

            // Show loading state
            var originalBtnHtml = launchBtn.html();
            launchBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span> Launching...');
            launchBtn.prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        toastr.success(data.message || 'Election launched successfully!');
                        setTimeout(function() {
                            window.location.href = data.redirect || 'create_elections.php';
                        }, 2000);
                    } else {
                        toastr.error(data.message || 'Failed to launch election');
                    }
                },
                error: function(xhr) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        toastr.error(response.message || 'An error occurred');
                    } catch (e) {
                        toastr.error('An error occurred: ' + xhr.statusText);
                    }
                },
                complete: function() {
                    launchBtn.html(originalBtnHtml);
                    launchBtn.prop('disabled', false);
                }
            });
        }

        // Validate end date is after start date
        $('#endDate').on('change', function() {
            var startDate = new Date($('#startDate').val());
            var endDate = new Date($(this).val());

            if (endDate <= startDate) {
                toastr.warning('End date must be after the start date');
                $(this).val('');
            }
        });
    });
</script>
<script src="../js/logout.js"></script>
</body>
</html>