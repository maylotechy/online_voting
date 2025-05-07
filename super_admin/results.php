<?php
require '../auth_session/auth_check_admin.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: rgba(67, 97, 238, 0.1);
            --primary-hover: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --box-shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 1rem 2rem rgba(0, 0, 0, 0.1);
            --transition: all 0.25s ease-in-out;
            --sidebar-width: 250px;
            --sidebar-width-collapsed: 80px;
            --navbar-height: 60px;
            --border-radius: 0.75rem;
            --border-radius-sm: 0.5rem;
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

        /* Navbar */
        .main-header {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            height: var(--navbar-height);
            z-index: 1030;
            background: #fff;
            box-shadow: var(--box-shadow-sm);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            transition: var(--transition);
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            background-color: var(--gray-100);
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            box-shadow: var(--box-shadow-sm);
            gap: 10px;
        }

        .user-dropdown img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Content Wrapper */
        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: calc(var(--navbar-height) + 1rem) 1.5rem 1.5rem;
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            transition: var(--transition);
        }

        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--box-shadow-lg);
            transform: translateY(-3px);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Stats Cards */
        .stats-card {
            border-radius: var(--border-radius);
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .stats-card i {
            position: absolute;
            bottom: -15px;
            right: 10px;
            font-size: 4rem;
            opacity: 0.2;
        }

        .stats-card .count {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .stats-card .label {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .bg-voters {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
        }

        .bg-votes {
            background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
        }

        .bg-participation {
            background: linear-gradient(135deg, #7209b7 0%, #560bad 100%);
        }

        /* Chart Container */
        .chart-container {
            height: 380px;
            background: white;
            border-radius: var(--border-radius-sm);
            padding: 1rem;
            box-shadow: var(--box-shadow-sm);
            transition: var(--transition);
        }

        /* Party Badge */
        .party-badge {
            background-color: var(--primary-light);
            color: var(--primary);
            padding: 0.35em 0.65em;
            font-size: 0.85em;
            border-radius: 50px;
            font-weight: 500;
        }

        /* Progress Bar */
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: var(--gray-200);
            overflow: hidden;
        }

        .progress-bar {
            background-color: var(--primary);
            border-radius: 4px;
        }

        /* Form Elements */
        .form-control, .form-select {
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--gray-300);
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        /* Buttons */
        .btn {
            border-radius: var(--border-radius-sm);
            padding: 0.65rem 1.25rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .export-btn {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
            border: none;
            color: white;
            font-weight: 500;
            padding: 0.65rem 1.5rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #3db8df, #3784de);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }

        /* Position Section */
        .position-section {
            background-color: #fff;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow-sm);
            transition: var(--transition);
        }

        .position-section:hover {
            box-shadow: var(--box-shadow);
        }

        .position-header {
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        /* Candidate Result */
        .candidate-result {
            padding: 1rem;
            border-radius: var(--border-radius-sm);
            background-color: var(--gray-100);
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .candidate-result:hover {
            background-color: #f0f7ff;
            transform: translateX(3px);
        }

        .candidate-result .vote-count {
            font-weight: 600;
            color: var(--primary);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        /* Media Queries */
        @media (max-width: 992px) {
            :root {
                --sidebar-width: 200px;
            }
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0px;
            }

            .main-header {
                left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar-open .sidebar {
                transform: translateX(0);
                width: var(--sidebar-width-collapsed);
            }

            .sidebar-open .content-wrapper {
                margin-left: var(--sidebar-width-collapsed);
                width: calc(100% - var(--sidebar-width-collapsed));
            }

            .sidebar-open .main-header {
                left: var(--sidebar-width-collapsed);
            }

            .sidebar .nav-link span {
                display: none;
            }

            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.2rem;
            }

            .chart-container {
                height: 300px;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>

<body>
<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
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
                <a class="nav-link active" href="results.php">
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
    <div class="content-wrapper">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded-3 mb-4">
            <div class="container-fluid">
                <button class="navbar-toggler d-md-none" type="button" id="sidebar-toggle">
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

        <!-- Content -->
        <section class="content">
            <div class="container-fluid py-4">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Election Results</h5>
                            </div>
                            <div class="card-body">
                                <!-- Election Selection and Export -->
                                <div class="row mb-4 align-items-end">
                                    <div class="col-lg-8 mb-3 mb-lg-0">
                                        <label for="electionSelect" class="form-label">Select Election</label>
                                        <select class="form-select" id="electionSelect">
                                            <option value="">Loading elections...</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4">
                                        <button class="btn export-btn w-100" id="exportBtn">
                                            <i class="fas fa-file-export me-2"></i> Export Results
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Election Preview -->
                <div class="card mb-4 fade-in" id="lastElectionPreview">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Last Election Preview</h5>
                        <small class="text-muted">Loading...</small>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Loading last election data...
                        </div>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="row mb-4 fade-in" id="electionStats" style="display: none;">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="stats-card bg-voters">
                            <i class="fas fa-users"></i>
                            <div class="count" id="total-voters">0</div>
                            <div class="label">Total Voters</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="stats-card bg-votes">
                            <i class="fas fa-vote-yea"></i>
                            <div class="count" id="votes-cast">0</div>
                            <div class="label">Votes Cast</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card bg-participation">
                            <i class="fas fa-percentage"></i>
                            <div class="count" id="participation-rate">0%</div>
                            <div class="label">Participation Rate</div>
                        </div>
                    </div>
                </div>

                <!-- Results Display -->
                <div class="row">
                    <div class="col-lg-8 mb-4 mb-lg-0">
                        <div class="card fade-in h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Election Results</h5>
                            </div>
                            <div class="card-body" id="electionResultsBody">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Select an election to view results
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card fade-in h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Vote Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="electionChart"></canvas>
                                    <div class="text-center text-muted mt-3">
                                        <small><i class="fas fa-info-circle me-1"></i> Select a position to view details</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $('#exportBtn').click(function() {
        const electionId = $('#electionSelect').val();
        if (electionId) {
            // Create a form dynamically
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'export_results.php';
            // Add election ID input
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'election_id';
            input.value = electionId;
            form.appendChild(input);
            // Append form to body and submit
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        } else {
            showToast('Please select an election first', 'warning');
        }
    });
    $(document).ready(function () {
        let electionChart = null;
        let lastElectionId = null;

        // Mobile sidebar toggle
        $('#sidebar-toggle').click(function(e) {
            e.preventDefault();
            $('body').toggleClass('sidebar-open');
        });

        // Load elections dropdown and last election preview
        loadLastElectionPreview();
        loadElections();

        // Election selection handler
        $('#electionSelect').on('change', function() {
            const electionId = $(this).val();
            if (electionId) {
                loadElectionResults(electionId);
                $('#electionStats').fadeIn(300);
            } else {
                $('#electionResultsBody').html('<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i> Select an election to view results</div>');
                $('#electionStats').fadeOut(300);
            }
        });

        // Export button handler
        $('#exportBtn').click(function() {
            const electionId = $('#electionSelect').val();
            if (electionId) {
                window.location.href = `export_results.php?election_id=${electionId}`;
            } else {
                showToast('Please select an election first', 'warning');
            }
        });

        // Simple toast notification
        function showToast(message, type = 'info') {
            const toastId = 'toast-' + Date.now();
            const toast = `
            <div id="${toastId}" class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        `;

            $('body').append(toast);
            const toastEl = document.getElementById(toastId).querySelector('.toast');
            const bsToast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
            bsToast.show();

            // Remove the toast element after it's hidden
            toastEl.addEventListener('hidden.bs.toast', function () {
                $('#' + toastId).remove();
            });
        }

        // Load elections dropdown
        function loadElections() {
            $.ajax({
                url: '../election_results/get_all_elections.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        const $select = $('#electionSelect');
                        $select.empty();
                        $select.append('<option value="">Select an election</option>');

                        response.data.forEach(election => {
                            $select.append(`<option value="${election.id}">${election.title} (${formatDate(election.end_time)})</option>`);
                        });
                    } else {
                        $('#electionSelect').html('<option value="">No elections available</option>');
                    }
                },
                error: function() {
                    showToast('Failed to load elections', 'warning');
                    $('#electionSelect').html('<option value="">Error loading elections</option>');
                }
            });
        }

        // Load last election preview
        function loadLastElectionPreview() {
            $.ajax({
                url: '../election_results/get_last_election.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        lastElectionId = response.data.id;
                        const election = response.data;

                        // Update preview header
                        $('#lastElectionPreview .card-header small').text(
                            `${election.title} - ${formatDate(election.end_time)}`
                        );

                        // Load basic info
                        const totalVoters = election.total_voters || 0;
                        const votesCast = election.total_votes_cast || 0;
                        const participationRate = totalVoters > 0 ? Math.round((votesCast / totalVoters) * 100) : 0;

                        let html = `
                        <div class="row mb-3">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="stats-card bg-voters">
                                    <i class="fas fa-users"></i>
                                    <div class="count">${totalVoters.toLocaleString()}</div>
                                    <div class="label">Total Voters</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="stats-card bg-votes">
                                    <i class="fas fa-vote-yea"></i>
                                    <div class="count">${votesCast.toLocaleString()}</div>
                                    <div class="label">Votes Cast</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card bg-participation">
                                    <i class="fas fa-percentage"></i>
                                    <div class="count">${participationRate}%</div>
                                    <div class="label">Participation</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-primary" id="viewFullResults">
                                <i class="fas fa-chart-bar me-2"></i> View Full Results
                            </button>
                        </div>
                    `;

                        $('#lastElectionPreview .card-body').html(html);

                        // Add click handler for view full results
                        $('#viewFullResults').click(function() {
                            $('#electionSelect').val(lastElectionId).trigger('change');
                            $('html, body').animate({
                                scrollTop: $('#electionStats').offset().top - 20
                            }, 500);
                        });
                    } else {
                        $('#lastElectionPreview .card-body').html(
                            '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i> No previous elections found</div>'
                        );
                    }
                },
                error: function() {
                    $('#lastElectionPreview .card-body').html(
                        '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Failed to load last election data</div>'
                    );
                }
            });
        }

        // Update stats cards with animation
        function updateStats(data) {
            const totalVoters = data.total_voters || 0;
            const votesCast = data.total_votes_cast || 0;
            const participationRate = totalVoters > 0 ? Math.round((votesCast / totalVoters) * 100) : 0;

            // Animate the numbers counting up
            animateCounter('total-voters', totalVoters);
            animateCounter('votes-cast', votesCast);
            animateCounter('participation-rate', participationRate, '%');
        }

        // Animate counter for statistics
        function animateCounter(elementId, targetValue, suffix = '') {
            const $element = $(`#${elementId}`);
            const startValue = parseInt($element.text().replace(/[^0-9]/g, '')) || 0;
            const duration = 1000; // Animation duration in milliseconds
            const frameRate = 60; // Frames per second
            const increment = (targetValue - startValue) / (duration / 1000 * frameRate);
            let currentValue = startValue;

            // Clear any existing animation
            if ($element.data('intervalId')) {
                clearInterval($element.data('intervalId'));
            }

            // Start animation
            const intervalId = setInterval(() => {
                currentValue += increment;

                if ((increment > 0 && currentValue >= targetValue) ||
                    (increment < 0 && currentValue <= targetValue)) {
                    clearInterval(intervalId);
                    currentValue = targetValue;
                }

                $element.text(Math.round(currentValue).toLocaleString() + suffix);
            }, 1000 / frameRate);

            // Store interval ID to clear it later if needed
            $element.data('intervalId', intervalId);
        }

        // Display election results
        function displayElectionResults(data) {
            let html = '';

            if (data.positions && data.positions.length > 0) {
                data.positions.forEach(position => {
                    const totalVotes = position.candidates.reduce((sum, candidate) => sum + candidate.votes, 0);

                    html += `
                    <div class="position-section mb-4 fade-in">
                        <h5 class="position-header d-flex justify-content-between align-items-center">
                            <span>${position.position_name}</span>
                            <small class="badge bg-light text-primary">${totalVotes.toLocaleString()} total votes</small>
                        </h5>
                `;

                    // Sort candidates by votes (descending)
                    position.candidates.sort((a, b) => b.votes - a.votes).forEach((candidate, index) => {
                        const percentage = totalVotes > 0 ? Math.round((candidate.votes / totalVotes) * 100) : 0;
                        // Determine if this is the leading candidate
                        const isLeading = index === 0 && position.candidates.length > 1;

                        html += `
                        <div class="candidate-result mb-3 ${isLeading ? 'border-start border-4 border-primary' : ''}" onclick="focusChart('${position.position_name}')">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>${candidate.student_name}</strong>
                                    ${candidate.party_list ? `<span class="party-badge ms-2">${candidate.party_list}</span>` : ''}
                                    ${isLeading ? '<span class="badge bg-primary ms-2">Leading</span>' : ''}
                                </div>
                                <div class="vote-count">${candidate.votes.toLocaleString()}</div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-3">
                                    <div class="progress-bar" role="progressbar" style="width: ${percentage}%"
                                         aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="text-muted">${percentage}%</span>
                            </div>
                        </div>
                    `;
                    });

                    html += `</div>`;
                });

                // Update chart with first position
                setTimeout(() => updateChart(data.positions[0]), 300);
            } else {
                html = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i> No results available for this election</div>';
            }

            $('#electionResultsBody').html(html);
        }

        // Load election results
        function loadElectionResults(electionId) {
            // Show loading indicator
            $('#electionResultsBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            $.ajax({
                url: '../election_results/get_election_result.php',
                method: 'GET',
                data: { election_id: electionId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update stats with animation
                        updateStats(response.data);

                        // Update results display
                        displayElectionResults(response.data);
                    } else {
                        $('#electionResultsBody').html(
                            '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Failed to load election results</div>'
                        );
                    }
                },
                error: function() {
                    $('#electionResultsBody').html(
                        '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Error loading election results</div>'
                    );
                }
            });
        }

        // Update chart
        function updateChart(positionData) {
            if (!positionData) {
                return;
            }

            const ctx = document.getElementById('electionChart').getContext('2d');

            // Sort candidates by votes (descending)
            const sortedCandidates = [...positionData.candidates].sort((a, b) => b.votes - a.votes);

            const labels = sortedCandidates.map(c => formatCandidateName(c.student_name));
            const votes = sortedCandidates.map(c => c.votes);
            const parties = sortedCandidates.map(c => c.party_list || 'Independent');
            const colors = generateColors(parties);

            if (electionChart) {
                electionChart.destroy();
            }

            electionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes',
                        data: votes,
                        backgroundColor: colors,
                        borderColor: colors.map(c => darkenColor(c, 20)),
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: positionData.position_name,
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: {
                                bottom: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#333',
                            borderColor: '#ddd',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const value = context.raw;
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return [
                                        `Votes: ${value.toLocaleString()}`,
                                        `Percentage: ${percentage}%`,
                                        `Party: ${parties[context.dataIndex]}`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                font: {
                                    size: 12
                                }
                            },
                            title: {
                                display: true,
                                text: 'Votes',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                padding: {
                                    bottom: 10
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 45,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Helper function to format candidate name (truncate if too long)
        function formatCandidateName(name) {
            if (name.length > 15) {
                return name.substring(0, 15) + '...';
            }
            return name;
        }

        // Helper functions
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function generateColors(parties) {
            const partyColors = {
                'Kilos': 'rgba(37, 99, 235, 0.7)',
                'Progressive IT': 'rgba(16, 185, 129, 0.7)',
                'Future Tech': 'rgba(245, 158, 11, 0.7)',
                'Independent': 'rgba(107, 114, 128, 0.7)'
            };

            return parties.map(party => partyColors[party] ||
                `rgba(${Math.floor(Math.random() * 155 + 100)}, ${Math.floor(Math.random() * 155 + 100)}, ${Math.floor(Math.random() * 155 + 100)}, 0.7)`);
        }

        function darkenColor(color, amount) {
            const rgba = color.match(/[\d.]+/g);
            if (rgba && rgba.length >= 3) {
                const r = Math.max(0, parseInt(rgba[0]) - amount);
                const g = Math.max(0, parseInt(rgba[1]) - amount);
                const b = Math.max(0, parseInt(rgba[2]) - amount);
                const a = rgba.length > 3 ? rgba[3] : 1;
                return `rgba(${r}, ${g}, ${b}, ${a})`;
            }
            return color;
        }

        // Make focusChart available globally
        window.focusChart = function(positionName) {
            const electionId = $('#electionSelect').val();
            if (electionId) {
                // Show loading indicator in chart
                const ctx = document.getElementById('electionChart').getContext('2d');
                if (electionChart) {
                    electionChart.destroy();
                }

                // Display a loading message
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = '16px Inter';
                ctx.fillStyle = '#6c757d';
                ctx.fillText('Loading data...', ctx.canvas.width / 2, ctx.canvas.height / 2);
                ctx.restore();

            }
        };
    });
</script>
</body>
</html>