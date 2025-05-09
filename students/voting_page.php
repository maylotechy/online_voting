<?php
global $pdo;
require '../config/db.php';
require_once "../middleware/auth_student.php";
session_start();

// Check student authentication
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

// Check if student has agreed to terms (new session variable)
if (!isset($_SESSION['terms_accepted'])) {
    header('Location: terms.php');
    exit();
}

if ($_SESSION['voting_allowed'] !== true and $_SESSION['otp_verified'] !== true) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];
$college_id = $_SESSION['college_id'];

// Fetch student name
try {
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as student_name FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_name = $student['student_name'] ?? 'Student';
} catch (PDOException $e) {
    $student_name = 'Student';
}

// Fetch active elections
try {
    // Get USG (university-wide) elections
    $usg_elections = $pdo->query("
        SELECT * FROM elections 
        WHERE scope = 'university-wide' 
        AND status = 'ongoing'
        AND NOW() BETWEEN start_time AND end_time
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get LSG (college-specific) elections
    $lsg_elections = $pdo->query("
        SELECT e.* FROM elections e
        JOIN students s ON e.college_id = s.college_id
        WHERE e.scope = 'college' 
        AND e.status = 'ongoing'
        AND NOW() BETWEEN e.start_time AND e.end_time
        AND s.student_id = '$student_id'
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Get all candidates for elections
    $all_elections = array_merge($usg_elections, $lsg_elections);
    $election_ids = array_column($all_elections, 'id');
    $placeholders = implode(',', array_fill(0, count($election_ids), '?'));

    $candidates = [];
    $position_info = []; // Store position details including number of winners

    if (!empty($election_ids)) {
        // Get position information including number_of_winners
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.id, p.name, p.`order`, p.max_winners
            FROM positions p
            JOIN candidates c ON p.id = c.position_id
            WHERE c.election_id IN ($placeholders)
            ORDER BY p.`order`
        ");
        $stmt->execute($election_ids);
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($positions as $position) {
            $position_info[$position['id']] = $position;
        }

        // Get candidates
        $stmt = $pdo->prepare("
            SELECT c.*, p.name as position_name, p.`order` as position_order,
                   CONCAT(s.first_name, ' ', s.last_name) as student_name,
                   col.college_name
            FROM candidates c
            JOIN positions p ON c.position_id = p.id
            JOIN students s ON c.student_id = s.student_id
            JOIN colleges col ON s.college_id = col.id
            WHERE c.election_id IN ($placeholders)
            ORDER BY 
                FIELD(c.election_id, " . implode(',', $election_ids) . "),
                p.`order`,
                p.name
                 ");
        $stmt->execute($election_ids);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Group candidates by election and position (ordered by position.order)
    $grouped_candidates = [];
    foreach ($candidates as $candidate) {
        $pos_id = $candidate['position_id'];
        $election_id = $candidate['election_id'];

        if (!isset($grouped_candidates[$election_id][$pos_id])) {
            $number_of_winners = $position_info[$pos_id]['max_winners'] ?? 1;

            $grouped_candidates[$election_id][$pos_id] = [
                'position_name' => $candidate['position_name'],
                'position_order' => $candidate['position_order'],
                'number_of_winners' => $number_of_winners,
                'candidates' => []
            ];
        }

        $grouped_candidates[$election_id][$pos_id]['candidates'][] = $candidate;
    }

    // Sort each election's positions by their order
    foreach ($grouped_candidates as $election_id => $positions) {
        uasort($positions, function($a, $b) {
            return $a['position_order'] - $b['position_order'];
        });
        $grouped_candidates[$election_id] = $positions;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Voting</title>
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

        /* Checkbox styles */
        .vote-checkbox {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 22px;
            height: 22px;
            cursor: pointer;
        }

        /* Counter for multi-select positions */
        .selection-counter {
            position: absolute;
            top: 15px;
            right: 50px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .max-selected-warning {
            display: none;
            color: var(--danger);
            font-weight: 500;
            margin-top: 10px;
            font-size: 0.9em;
            text-align: center;
            padding: 8px;
            background-color: rgba(247, 37, 133, 0.1);
            border-radius: 8px;
        }

        /* Candidate cards */
        .candidate-card {
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            background-color: white;
            transition: all 0.3s;
            position: relative;
        }

        .candidate-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .candidate-selected {
            border: 2px solid var(--primary);
            background-color: rgba(67, 97, 238, 0.03);
        }

        .candidate-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .candidate-details {
            flex: 1;
        }

        .candidate-name {
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .party-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.8em;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .candidate-college {
            font-size: 0.85em;
            color: var(--gray);
        }

        /* Platform section */
        .platform-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
            font-size: 0.9em;
            line-height: 1.5;
            color: #495057;
            border-left: 3px solid var(--primary);
        }

        .platform-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary);
            font-size: 0.9em;
            display: flex;
            align-items: center;
        }

        .platform-title i {
            margin-right: 5px;
        }

        .platform-content {
            max-height: 120px;
            overflow-y: auto;
        }

        /* Custom scrollbar */
        .platform-content::-webkit-scrollbar {
            width: 5px;
        }

        .platform-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .platform-content::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 5px;
        }

        /* Election header styles */
        .election-header {
            padding: 25px 30px;
            color: white;
            position: relative;
        }

        .usg-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }

        .lsg-header {
            background: linear-gradient(135deg, #7209b7, #3a0ca3);
        }

        .election-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .election-subtitle {
            opacity: 0.9;
            font-weight: 400;
        }

        /* Position header */
        .position-header {
            padding: 15px 25px;
            background-color: var(--light);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            color: var(--dark);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .position-icon {
            margin-right: 10px;
            color: var(--primary);
        }

        /* Candidates grid */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            padding: 25px;
        }

        /* No election message */
        .no-election {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .no-election i {
            font-size: 4em;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .no-election h4 {
            color: var(--gray);
            font-weight: 600;
        }

        /* Vote button */
        .btn-vote {
            margin-top: 30px;
            padding: 12px 35px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
            font-size: 1.1rem;
            transition: all 0.3s;
            color: white;
        }

        .btn-vote:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
            color: white;
        }

        /* Rules download section */
        .rules-section {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
        }

        .rules-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
            display: flex;
            align-items: center;
        }

        .rules-title i {
            margin-right: 10px;
        }

        .rules-content {
            margin-bottom: 15px;
        }

        .btn-download {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
            border: none;
            color: white;
            font-weight: 500;
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

            .candidates-grid {
                grid-template-columns: 1fr;
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
                <a class="nav-link active" href="elections.php">
                    <i class="fas fa-vote-yea"></i>
                    <span>Voting</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../students/realtime_results.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Results</span>
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
                            <img src="../asssets/super_admin/usm_comelec.jpg" alt="Student" class="rounded-circle" width="32">
                            <span><?= htmlspecialchars($student_name) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="Logout()"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid">
            <div class="content-header text-center mb-5">
                <h2>Current Elections</h2>
                <p>Cast your vote for the ongoing elections</p>
            </div>

            <!-- Election Rules Section -->
            <div class="rules-section mb-4">
                <h5 class="rules-title"><i class="fas fa-file-alt"></i> Election Rules and Guidelines</h5>
                <div class="rules-content">
                    <p>Please review the election rules before voting. You can download the complete guidelines document below.</p>
                </div>
                <?php if (!empty($usg_elections) || !empty($lsg_elections)): ?>
                    <?php
                    // Get the first election that has rules (prioritize USG)
                    $electionWithRules = null;
                    foreach (array_merge($usg_elections, $lsg_elections) as $election) {
                        if (!empty($election['rules_file'])) {
                            $electionWithRules = $election;
                            break;
                        }
                    }
                    ?>
                    <?php if ($electionWithRules): ?>
                        <a href="../students/donwload_rules.php?election_id=<?= $electionWithRules['id'] ?>" class="btn btn-download">
                            <i class="fas fa-download me-2"></i> Download Election Rules (PDF)
                        </a>
                    <?php else: ?>
                        <button class="btn btn-download" disabled>
                            <i class="fas fa-download me-2"></i> No Rules Available
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <form id="votingForm" action="process_vote.php" method="POST" enctype="multipart/form-data">
                <!-- University-wide Elections (USG) Section -->
                <div class="card mb-4">
                    <?php if (!empty($usg_elections)): ?>
                        <?php foreach ($usg_elections as $election): ?>
                            <div class="election-header usg-header">
                                <div class="election-title">
                                    <i class="fas fa-university mr-2"></i> University Student Government (USG) Election
                                </div>
                                <div class="election-subtitle"><?= htmlspecialchars($election['title']) ?></div>
                            </div>

                            <div class="card-body">
                                <?php if (isset($grouped_candidates[$election['id']])): ?>
                                    <?php foreach ($grouped_candidates[$election['id']] as $position_id => $position_data): ?>
                                        <div class="position-section">
                                            <div class="position-header">
                                                <i class="fas fa-user-tie position-icon"></i>
                                                <?= htmlspecialchars($position_data['position_name']) ?>
                                                <?php if ($position_data['number_of_winners'] > 1): ?>
                                                    <span class="badge bg-info ms-2">
                                                        Select up to <?= $position_data['number_of_winners'] ?> candidates
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($position_data['number_of_winners'] > 1): ?>
                                                <div class="warning-container px-4 pt-3">
                                                    <div class="max-selected-warning" id="warning-<?= $election['id'] ?>-<?= $position_id ?>">
                                                        You can only select up to <?= $position_data['number_of_winners'] ?> candidates for this position
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="candidates-grid">
                                                <?php foreach ($position_data['candidates'] as $candidate): ?>
                                                    <div class="candidate-card" data-position="<?= $position_id ?>" data-election="<?= $election['id'] ?>">
                                                        <?php if ($position_data['number_of_winners'] > 1): ?>
                                                            <input type="checkbox"
                                                                   name="votes[<?= $election['id'] ?>][<?= $position_id ?>][]"
                                                                   value="<?= $candidate['id'] ?>"
                                                                   class="vote-checkbox"
                                                                   data-max-selected="<?= $position_data['number_of_winners'] ?>">
                                                        <?php else: ?>
                                                            <input type="radio"
                                                                   name="votes[<?= $election['id'] ?>][<?= $position_id ?>]"
                                                                   value="<?= $candidate['id'] ?>"
                                                                   required
                                                                   class="vote-radio">
                                                        <?php endif; ?>

                                                        <div class="candidate-info">
                                                            <img src="../asssets/super_admin/osa_profile.jpg"
                                                                 class="candidate-photo"
                                                                 alt="Candidate Photo">
                                                            <div class="candidate-details">
                                                                <div class="candidate-name"><?= htmlspecialchars($candidate['student_name']) ?></div>
                                                                <div>
                                                                    <span class="party-badge">
                                                                        <?= htmlspecialchars($candidate['party_list']) ?>
                                                                    </span>
                                                                </div>
                                                                <div class="candidate-college">
                                                                    <?= htmlspecialchars($candidate['college_name']) ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($candidate['platform'])): ?>
                                                            <div class="platform-section">
                                                                <div class="platform-title">
                                                                    <i class="fas fa-bullhorn"></i> Platform
                                                                </div>
                                                                <div class="platform-content">
                                                                    <?= nl2br(htmlspecialchars($candidate['platform'])) ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No candidates available for this election.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-election">
                            <i class="fas fa-university"></i>
                            <h4>No USG Elections Available</h4>
                            <p class="text-muted">There are currently no university-wide elections.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- College-based Elections (LSG) Section -->
                <div class="card mb-4">
                    <?php if (!empty($lsg_elections)): ?>
                        <?php foreach ($lsg_elections as $election): ?>
                            <div class="election-header lsg-header">
                                <div class="election-title">
                                    <i class="fas fa-building mr-2"></i> Local Student Government (LSG) Election
                                </div>
                                <div class="election-subtitle"><?= htmlspecialchars($election['title']) ?></div>
                            </div>

                            <div class="card-body">
                                <?php if (isset($grouped_candidates[$election['id']])): ?>
                                    <?php foreach ($grouped_candidates[$election['id']] as $position_id => $position_data): ?>
                                        <div class="position-section">
                                            <div class="position-header">
                                                <i class="fas fa-user-tie position-icon"></i>
                                                <?= htmlspecialchars($position_data['position_name']) ?>
                                                <?php if ($position_data['number_of_winners'] > 1): ?>
                                                    <span class="badge bg-info ms-2">
                                                        Select up to <?= $position_data['number_of_winners'] ?> candidates
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($position_data['number_of_winners'] > 1): ?>
                                                <div class="warning-container px-4 pt-3">
                                                    <div class="max-selected-warning" id="warning-<?= $election['id'] ?>-<?= $position_id ?>">
                                                        You can only select up to <?= $position_data['number_of_winners'] ?> candidates for this position
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="candidates-grid">
                                                <?php foreach ($position_data['candidates'] as $candidate): ?>
                                                    <div class="candidate-card" data-position="<?= $position_id ?>" data-election="<?= $election['id'] ?>">
                                                        <?php if ($position_data['number_of_winners'] > 1): ?>
                                                            <input type="checkbox"
                                                                   name="votes[<?= $election['id'] ?>][<?= $position_id ?>][]"
                                                                   value="<?= $candidate['id'] ?>"
                                                                   class="vote-checkbox"
                                                                   data-max-selected="<?= $position_data['number_of_winners'] ?>">
                                                        <?php else: ?>
                                                            <input type="radio"
                                                                   name="votes[<?= $election['id'] ?>][<?= $position_id ?>]"
                                                                   value="<?= $candidate['id'] ?>"
                                                                   required
                                                                   class="vote-radio">
                                                        <?php endif; ?>

                                                        <div class="candidate-info">
                                                            <img src="../assets/candidates/default.jpg"
                                                                 class="candidate-photo"
                                                                 alt="Candidate Photo">
                                                            <div class="candidate-details">
                                                                <div class="candidate-name"><?= htmlspecialchars($candidate['student_name']) ?></div>
                                                                <div>
                                                                    <span class="party-badge">
                                                                        <?= htmlspecialchars($candidate['party_list']) ?>
                                                                    </span>
                                                                </div>
                                                                <div class="candidate-college">
                                                                    <?= htmlspecialchars($candidate['college_name']) ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($candidate['platform'])): ?>
                                                            <div class="platform-section">
                                                                <div class="platform-title">
                                                                    <i class="fas fa-bullhorn"></i> Platform
                                                                </div>
                                                                <div class="platform-content">
                                                                    <?= nl2br(htmlspecialchars($candidate['platform'])) ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No candidates available for this election.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-election">
                            <i class="fas fa-building"></i>
                            <h4>No LSG Elections Available</h4>
                            <p class="text-muted">There are currently no college-based elections.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($usg_elections) || !empty($lsg_elections)): ?>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-vote" id="submitVoteBtn">
                            <i class="fas fa-check-circle mr-2"></i> Submit Votes
                        </button>
                    </div>
                <?php endif; ?>
            </form>
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
        // Initialize Toastr
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // Highlight selected candidate for radio buttons
        $('input[type="radio"]').change(function() {
            $(this).closest('.candidate-card').addClass('candidate-selected')
                .siblings().removeClass('candidate-selected');
        });

        // Highlight selected candidates for checkboxes
        $('input[type="checkbox"]').change(function() {
            const card = $(this).closest('.candidate-card');
            if ($(this).is(':checked')) {
                card.addClass('candidate-selected');

                // Check if we've reached the maximum selections
                const maxSelected = parseInt($(this).data('max-selected'));
                const electionId = card.data('election');
                const positionId = card.data('position');
                const selector = `input[name="votes[${electionId}][${positionId}][]"]:checked`;
                const currentlySelected = $(selector).length;

                if (currentlySelected > maxSelected) {
                    $(this).prop('checked', false);
                    card.removeClass('candidate-selected');
                    $(`#warning-${electionId}-${positionId}`).slideDown();
                    setTimeout(function() {
                        $(`#warning-${electionId}-${positionId}`).slideUp();
                    }, 3000);
                    return;
                }

                // Add counter for multi-select positions if it doesn't exist
                if (currentlySelected <= maxSelected && currentlySelected > 0) {
                    // Remove existing counter if any
                    card.find('.selection-counter').remove();

                    // Add counter
                    const counter = $('<div class="selection-counter">' + currentlySelected + '</div>');
                    card.append(counter);
                }
            } else {
                card.removeClass('candidate-selected');
                card.find('.selection-counter').remove();

                // Update counters for remaining selected cards
                const electionId = card.data('election');
                const positionId = card.data('position');
                const selector = `input[name="votes[${electionId}][${positionId}][]"]:checked`;
                const selectedCards = $(selector).closest('.candidate-card');

                selectedCards.each(function(index) {
                    $(this).find('.selection-counter').remove();
                    $(this).append('<div class="selection-counter">' + (index + 1) + '</div>');
                });
            }
        });

        // Make whole card clickable for input selection
        $('.candidate-card').click(function(e) {
            if (!$(e.target).is('input[type="radio"]') && !$(e.target).is('input[type="checkbox"]')) {
                const input = $(this).find('input[type="radio"], input[type="checkbox"]');

                if (input.is('[type="radio"]')) {
                    input.prop('checked', true).trigger('change');
                } else {
                    // Toggle checkbox
                    input.prop('checked', !input.prop('checked')).trigger('change');
                }
            }
        });

        // Form submission handling with AJAX
        $('#votingForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = $('#submitVoteBtn');
            let formValid = true;

            // Check radio button selections
            $('.position-section').each(function() {
                // Skip multi-select positions (checkboxes)
                const inputs = $(this).find('input[type="radio"]');
                if (inputs.length > 0) {
                    const name = inputs.first().attr('name');
                    if (!$('input[name="' + name + '"]:checked').length) {
                        formValid = false;
                        toastr.error('Please make a selection for all positions before submitting.');
                        return false; // exit the each loop
                    }
                }
            });

            if (!formValid) return;

            // Check checkbox selections
            $('.position-section').each(function() {
                const checkboxGroups = $(this).find('input[type="checkbox"]');
                if (checkboxGroups.length > 0) {
                    // Find the first checkbox to get the name pattern
                    const firstCheckbox = checkboxGroups.first();
                    const name = firstCheckbox.attr('name');
                    const minRequired = 1; // At least one selection required

                    if ($('input[name="' + name + '"]:checked').length < minRequired) {
                        formValid = false;
                        toastr.error('Please make a selection for all positions before submitting.');
                        return false; // exit the each loop
                    }
                }
            });

            if (!formValid) return;

            // Disable submit button to prevent multiple submissions
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            // Send AJAX request
            $.ajax({
                url: 'process_vote.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            window.location.href = response.redirect || 'elections.php';
                        }, 1500);
                    } else {
                        toastr.error(response.message || 'An error occurred while processing your vote.');
                        submitBtn.prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i> Submit Votes');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    toastr.error('Vote submission failed. Check console for details.');
                    submitBtn.prop('disabled', false).html('<i class="fas fa-check-circle mr-2"></i> Submit Votes');
                }
            });
        });
    });
</script>
<script src="../js/logout.js"></script>
</body>
</html>