<?php
global $pdo;
require '../config/db.php';
session_start();

// Check student authentication
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
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
            SELECT DISTINCT p.id, p.name, p.`order`, p.number_of_winners
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
        $number_of_winners = $position_info[$pos_id]['number_of_winners'] ?? 1;

        $grouped_candidates[$candidate['election_id']][$pos_id] = [
            'position_name' => $candidate['position_name'],
            'position_order' => $candidate['position_order'],
            'number_of_winners' => $number_of_winners,
            'candidates' => []
        ];
    }

    // Now populate the candidates
    foreach ($candidates as $candidate) {
        $grouped_candidates[$candidate['election_id']][$candidate['position_id']]['candidates'][] = $candidate;
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
    <!-- AdminLTE + Bootstrap CSS -->
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --danger: #f72585;
            --gray: #6c757d;
            --light-gray: #e9ecef;
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

        body {
            background-color: #f5f7fa;
            font-family: 'Satoshi', Tahoma, Geneva, Verdana, sans-serif;
        }

        .election-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .election-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 40px;
            transition: transform 0.3s ease;
            background: white;
        }

        .election-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }

        .election-header {
            padding: 25px 30px;
            color: white;
            position: relative;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
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

        .position-section {
            margin-bottom: 30px;
        }

        .position-header {
            padding: 15px 25px;
            background-color: var(--light);
            border-bottom: 1px solid rgba(0,0,0,0.05);
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

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            padding: 25px;
        }

        .candidate-card {
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.08);
            background: white;
            transition: all 0.3s ease;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.03);
            position: relative;
            padding: 20px;
            cursor: pointer;
        }

        .candidate-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border-color: rgba(67, 97, 238, 0.2);
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
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
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

        .vote-radio {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 22px;
            height: 22px;
            cursor: pointer;
        }

        .no-election {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
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
        }

        .btn-vote:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }

        /* Navbar Styles */
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .nav-profile {
            display: flex;
            align-items: center;
        }

        .nav-profile-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.2);
        }

        /* Sidebar Styles */
        .sidebar {
            background: white;
            box-shadow: 2px 0 20px rgba(0,0,0,0.05);
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            border-radius: 8px;
            color: var(--dark);
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white !important;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }

        .nav-link:hover:not(.active) {
            background-color: rgba(67, 97, 238, 0.1);
        }

        /* Content Header */
        .content-header h2 {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .content-header p {
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
            max-height: 120px; /* Fixed height */
            overflow-y: auto;  /* Make it scrollable */
        }

        /* Custom scrollbar for platform content */
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
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item nav-profile">
                <img src="../asssets/super_admin/profile.png" class="nav-profile-img">
                <span class="font-weight-bold"><?= htmlspecialchars($student_name) ?></span>
            </li>
            <li class="nav-item ml-3">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="elections.php" class="brand-link">
            <i class="fas fa-vote-yea ml-3"></i>
            <span class="brand-text font-weight-light">Student Portal</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item"><a href="elections.php" class="nav-link active">
                            <i class="nav-icon fas fa-vote-yea"></i><p>Elections</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
        <div class="container-fluid election-container">
            <div class="content-header text-center mb-5">
                <h2>Current Elections</h2>
                <p>Cast your vote for the ongoing elections</p>
            </div>

            <form id="votingForm" action="process_vote.php" method="POST">
                <!-- University-wide Elections (USG) Section -->
                <div class="election-card">
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
                                                    <span class="badge badge-info ml-2">
                                                    Select up to <?= $position_data['number_of_winners'] ?> candidates
                                                </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="candidates-grid">
                                                <?php foreach ($position_data['candidates'] as $candidate): ?>
                                                    <div class="candidate-card">
                                                        <?php if ($position_data['number_of_winners'] > 1): ?>
                                                            <input type="checkbox"
                                                                   name="vote[<?= $election['id'] ?>][<?= $position_id ?>][]"
                                                                   value="<?= $candidate['id'] ?>"
                                                                   class="vote-checkbox"
                                                                   data-max-selected="<?= $position_data['number_of_winners'] ?>">
                                                        <?php else: ?>
                                                            <input type="radio"
                                                                   name="vote[<?= $election['id'] ?>][<?= $position_id ?>]"
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
                <div class="election-card">
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
                                            </div>

                                            <div class="candidates-grid">
                                                <?php if ($position_data['number_of_winners'] > 1): ?>
                                                    <div class="col-12">
                                                        <div class="max-selected-warning" id="warning-<?= $election['id'] ?>-<?= $position_id ?>">
                                                            You can only select up to <?= $position_data['number_of_winners'] ?> candidates for this position
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php foreach ($position_data['candidates'] as $candidate): ?>
                                                    <div class="candidate-card" data-position="<?= $position_id ?>" data-election="<?= $election['id'] ?>">
                                                        <?php if ($position_data['number_of_winners'] > 1): ?>
                                                            <input type="checkbox"
                                                                   name="vote[<?= $election['id'] ?>][<?= $position_id ?>][]"
                                                                   value="<?= $candidate['id'] ?>"
                                                                   class="vote-checkbox"
                                                                   data-max-selected="<?= $position_data['number_of_winners'] ?>">
                                                        <?php else: ?>
                                                            <input type="radio"
                                                                   name="vote[<?= $election['id'] ?>][<?= $position_id ?>]"
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
                        <button type="submit" class="btn btn-primary btn-vote">
                            <i class="fas fa-check-circle mr-2"></i> Submit Votes
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<script>
    $(document).ready(function() {
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
                const selector = `input[name="vote[${electionId}][${positionId}][]"]:checked`;
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
                const selector = `input[name="vote[${electionId}][${positionId}][]"]:checked`;
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

        // Form submission handling
        $('#votingForm').on('submit', function(e) {
            let formValid = true;

            // Check radio button selections
            $('.position-section').each(function() {
                // Skip multi-select positions (checkboxes)
                const inputs = $(this).find('input[type="radio"]');
                if (inputs.length > 0) {
                    const name = inputs.first().attr('name');
                    if (!$('input[name="' + name + '"]:checked').length) {
                        formValid = false;
                    }
                }
            });

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
                    }
                }
            });

            if (!formValid) {
                e.preventDefault();
                alert('Please make selections for all positions before submitting.');
            }
        });
    });
</script>
</body>
</html>