<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USM Election Results</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            color: #333;
        }

        .navbar {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .content-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
            font-weight: 600;
        }

        /* Stats Cards - Updated with more spacing */
        .stats-card {
            border-radius: 12px;
            color: white;
            padding: 1.8rem;  /* Increased from 1.5rem */
            margin-bottom: 1.5rem;
            height: 100%;
            transition: transform 0.3s, box-shadow 0.3s;  /* Added transition for hover effect */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);  /* Added shadow for depth */
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .stats-card i {
            font-size: 2.2rem;  /* Increased from 2rem */
            margin-bottom: 1.2rem;  /* Increased from 1rem */
            opacity: 0.8;
        }

        .stats-card .count {
            font-size: 2rem;  /* Increased from 1.8rem */
            font-weight: 700;
            margin-bottom: 0.8rem;  /* Increased from 0.5rem */
        }

        .stats-card .label {
            font-size: 1rem;  /* Increased from 0.95rem */
            opacity: 0.9;
        }

        /* Add more vertical spacing between the stats cards */
        .col-md-6.col-sm-6 {
            margin-bottom: 1.2rem;  /* Added explicit margin between cards */
        }

        /* Ensure consistent height and better mobile spacing */
        @media (max-width: 768px) {
            .stats-card {
                height: auto;
                min-height: 160px;
                margin-bottom: 1.2rem;
            }
        }
        .bg-voters { background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%); }
        .bg-votes { background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%); }
        .bg-participation { background: linear-gradient(135deg, #7209b7 0%, #560bad 100%); }
        .bg-positions { background: linear-gradient(135deg, #f72585 0%, #e63946 100%); }

        /* Candidate Cards */
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .vote-count {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
        }

        .progress-bar {
            background-color: var(--primary);
        }

        .party-badge {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 0.35em 0.65em;
            font-size: 0.85em;
            border-radius: 50px;
            font-weight: 500;
        }

        /* Chart Container */
        .chart-container {
            height: 400px;
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-block;
        }

        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .status-upcoming {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .status-ended {
            background-color: rgba(6, 182, 212, 0.1);
            color: #06b6d4;
        }

        /* Time Boxes */
        .time-box {
            background-color: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
            height: 100%;
        }

        .time-label {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .time-value {
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }

        /* Election Tabs */
        .election-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 2rem;
        }

        .election-tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            margin-right: 1rem;
        }

        .election-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: 600;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }

            .election-tabs {
                overflow-x: auto;
                white-space: nowrap;
                display: flex;
                flex-wrap: nowrap;
            }
        }
    </style>
</head>
<body>
<!-- Simple Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-vote-yea me-2"></i>USM Election Results
        </a>
        <div class="d-flex align-items-center text-white">
            <span id="current-time-display" class="me-3"></span>
            <span class="status-badge status-active" id="election-status-display">
                    <i class="fas fa-circle me-1"></i> Loading...
                </span>
        </div>
    </div>
</nav>

<div class="content-container">
    <!-- Election Type Tabs -->
    <div class="election-tabs">
        <div class="election-tab active" data-type="usg">USG Elections</div>
        <div class="election-tab" data-type="lsg">LSG Elections</div>
    </div>

    <!-- No election message -->
    <div id="no-election" class="card" style="display:none;">
        <div class="card-body text-center py-5">
            <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No active elections found</h4>
            <p class="text-muted">There are currently no elections available for viewing.</p>
        </div>
    </div>

    <!-- Election results display -->
    <div id="election-results">
        <!-- Election info and stats -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0" id="election-title">Election Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="time-box">
                                    <div class="time-label">Start Date</div>
                                    <div class="time-value" id="start-date">Loading...</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="time-box">
                                    <div class="time-label">End Date</div>
                                    <div class="time-value" id="end-date">Loading...</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="time-box">
                                    <div class="time-label">Time Remaining</div>
                                    <div class="time-value" id="time-remaining">Loading...</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="time-box">
                                    <div class="time-label">Current Status</div>
                                    <div class="time-value">
                                            <span class="status-badge" id="election-status">
                                                <i class="fas fa-circle me-1"></i> Loading...
                                            </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-6 col-sm-6">
                        <div class="stats-card bg-voters">
                            <i class="fas fa-users"></i>
                            <div class="count" id="total-voters">0</div>
                            <div class="label">Total Voters</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="stats-card bg-votes">
                            <i class="fas fa-vote-yea"></i>
                            <div class="count" id="votes-cast">0</div>
                            <div class="label">Votes Cast</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="stats-card bg-participation">
                            <i class="fas fa-percentage"></i>
                            <div class="count" id="participation-rate">0%</div>
                            <div class="label">Participation</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="stats-card bg-positions">
                            <i class="fas fa-user-tie"></i>
                            <div class="count" id="total-positions">0</div>
                            <div class="label">Positions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Position results and chart -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Results by Position</h5>
                    </div>
                    <div class="card-body">
                        <div id="positions-container">
                            <!-- Position sections will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0" id="chart-title">Vote Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="electionChart"></canvas>
                            <div class="text-center text-muted mt-3">
                                <small><i class="fas fa-sync-alt me-1"></i> Live results updating</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let electionChart = null;
        let currentElectionType = 'usg';
        let currentElectionData = null;
        let currentActiveElection = null;

        // Initialize the page
        fetchActiveElections();

        // Set up election type tabs
        document.querySelectorAll('.election-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.election-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentElectionType = this.getAttribute('data-type');
                fetchActiveElections();
            });
        });

        // Fetch active elections from the server
        function fetchActiveElections() {
            $.ajax({
                url: '../election_results/get_active_elections.php',
                method: 'GET',
                data: { type: currentElectionType },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        const ongoingElections = response.data.filter(e => e.status === 'ongoing');

                        if (ongoingElections.length === 0) {
                            showNoElectionMessage();
                            return;
                        }

                        const election = ongoingElections.find(e => e.scope === currentElectionType) ||
                            ongoingElections[0];

                        if (election) {
                            currentActiveElection = election;
                            loadElectionResults(currentActiveElection);
                            document.getElementById('no-election').style.display = 'none';
                            document.getElementById('election-results').style.display = 'block';
                        } else {
                            showNoElectionMessage();
                        }
                    } else {
                        showNoElectionMessage();
                    }
                },
                error: function() {
                    console.error('Failed to fetch active elections');
                    showNoElectionMessage();
                }
            });
        }

        function showNoElectionMessage() {
            document.getElementById('no-election').style.display = 'block';
            document.getElementById('election-results').style.display = 'none';
        }

        // Load election results for a specific election
        function loadElectionResults(election) {
            if (!election) return;

            // Update election info
            document.getElementById('election-title').textContent = election.title;
            const startDate = new Date(election.start_time);
            const endDate = new Date(election.end_time);

            document.getElementById('start-date').textContent = formatDate(startDate);
            document.getElementById('end-date').textContent = formatDate(endDate);

            // Update current time immediately
            updateCurrentTime();

            // Fetch election results
            $.ajax({
                url: '../election_results/get_election_result.php',
                method: 'GET',
                data: { election_id: election.id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        currentElectionData = response.data;
                        updateStats(currentElectionData, election);
                        displayElectionResults(currentElectionData, election);

                        if (currentElectionData.positions && currentElectionData.positions.length > 0) {
                            createChart(currentElectionData.positions[0]);
                            document.getElementById('chart-title').textContent =
                                `${currentElectionData.positions[0].position_name} Results`;
                        }
                    } else {
                        console.error('Failed to load election results:', response.message);
                    }
                },
                error: function() {
                    console.error('Failed to fetch election results');
                }
            });
        }

        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('current-time-display').textContent = formatTime(now);

            if (currentActiveElection) {
                const startDate = new Date(currentActiveElection.start_time);
                const endDate = new Date(currentActiveElection.end_time);

                // Update time remaining
                if (now < startDate) {
                    document.getElementById('time-remaining').textContent = 'Not started';
                    updateStatus('Upcoming Election', 'status-upcoming');
                } else if (now > endDate) {
                    document.getElementById('time-remaining').textContent = 'Election ended';
                    updateStatus('Election Ended', 'status-ended');
                } else {
                    const remainingTime = Math.max(0, endDate - now);
                    document.getElementById('time-remaining').textContent = formatTimeRemaining(remainingTime);
                    updateStatus('Election in Progress', 'status-active');
                }
            }
        }

        function updateStatus(text, className) {
            const statusElement = document.getElementById('election-status');
            const statusDisplay = document.getElementById('election-status-display');

            statusElement.innerHTML = `<i class="fas fa-circle me-1"></i> ${text}`;
            statusElement.className = `status-badge ${className}`;

            statusDisplay.innerHTML = `<i class="fas fa-circle me-1"></i> ${text}`;
            statusDisplay.className = `status-badge ${className}`;
        }

        // Format date
        function formatDate(date) {
            return date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }

        // Format time only
        function formatTime(date) {
            return date.toLocaleString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        }

        // Format time remaining
        function formatTimeRemaining(ms) {
            const days = Math.floor(ms / (1000 * 60 * 60 * 24));
            const hours = Math.floor((ms % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60));

            let result = '';
            if (days > 0) result += `${days}d `;
            if (hours > 0 || days > 0) result += `${hours}h `;
            result += `${minutes}m`;

            return result;
        }

        // Update stats
        function updateStats(data, election) {
            if (!data || !election) return;

            const totalVoters = data.total_voters || 0;
            const votesCast = data.total_votes_cast || 0;
            const participationRate = totalVoters > 0 ? Math.round((votesCast / totalVoters) * 100) : 0;
            const totalPositions = data.positions ? data.positions.length : 0;

            document.getElementById('total-voters').textContent = totalVoters.toLocaleString();
            document.getElementById('votes-cast').textContent = votesCast.toLocaleString();
            document.getElementById('participation-rate').textContent = participationRate + '%';
            document.getElementById('total-positions').textContent = totalPositions;
        }

        // Function to display election results
        function displayElectionResults(data, election) {
            const positionsContainer = document.getElementById('positions-container');
            positionsContainer.innerHTML = '';

            if (!data.positions || data.positions.length === 0) {
                positionsContainer.innerHTML = `
                <div class="alert alert-info">
                    No positions found for this election
                </div>
            `;
                return;
            }

            // Define a color palette for positions
            const positionColors = {
                'president': '#f72585',
                'vice president': '#4cc9f0',
                'secretary': '#7209b7',
                'treasurer': '#4361ee',
                'public information officer' : '#3f37c9',
                'senator': '#b5ead7',

            };

            data.positions.forEach((position, index) => {
                const totalVotes = position.candidates.reduce((sum, candidate) => sum + candidate.votes, 0);

                // Get color for this position (default to #4895ef if not specified)
                const positionColor = positionColors[position.position_name.toLowerCase()] || '#4895ef';

                let positionHTML = `
                <div class="position-section mb-4">
                    <h6 class="mb-3">${position.position_name}</h6>
            `;

                // Sort candidates by votes (descending)
                position.candidates.sort((a, b) => b.votes - a.votes);

                position.candidates.forEach(candidate => {
                    const percentage = totalVotes > 0 ? Math.round((candidate.votes / totalVotes) * 100) : 0;

                    positionHTML += `
                    <div class="candidate-card mb-3" onclick="focusChart(${index})">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>${candidate.student_name}</strong>
                                ${candidate.party_list ? `<span class="party-badge ms-2">${candidate.party_list}</span>` : ''}
                            </div>
                            <div class="vote-count">${candidate.votes.toLocaleString()}</div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 me-3">
                                <div class="progress-bar" role="progressbar"
                                     style="width: ${percentage}%; background-color: ${positionColor}"
                                     aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="text-muted">${percentage}%</span>
                        </div>
                    </div>
                `;
                });

                positionHTML += `</div>`;
                positionsContainer.innerHTML += positionHTML;
            });
        }

        // Create chart for a position
        function createChart(positionData) {
            if (!positionData) return;

            const labels = positionData.candidates.map(candidate => candidate.student_name);
            const votes = positionData.candidates.map(candidate => candidate.votes);
            const parties = positionData.candidates.map(candidate => candidate.party_list || 'Independent');
            const colors = generateColors(parties);

            const ctx = document.getElementById('electionChart').getContext('2d');

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
                        borderColor: colors.map(color => darkenColor(color, 20)),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
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
                            ticks: { precision: 0 },
                            title: { display: true, text: 'Number of Votes' }
                        },
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }

        // Make chart focus function available globally
        window.focusChart = function(index) {
            if (currentElectionData && currentElectionData.positions && currentElectionData.positions[index]) {
                createChart(currentElectionData.positions[index]);
                document.getElementById('chart-title').textContent =
                    `${currentElectionData.positions[index].position_name} Results`;
            }
        };

        // Generate colors based on party names
        function generateColors(parties) {
            const partyColors = {
                'Tech Alliance': 'rgba(37, 99, 235, 0.7)',
                'Progressive IT': 'rgba(16, 185, 129, 0.7)',
                'Future Tech': 'rgba(245, 158, 11, 0.7)',
                'Independent': 'rgba(107, 114, 128, 0.7)'
            };

            return parties.map(party => partyColors[party] ||
                `rgba(${Math.floor(Math.random() * 155 + 100)}, ${Math.floor(Math.random() * 155 + 100)}, ${Math.floor(Math.random() * 155 + 100)}, 0.7)`);
        }

        // Darken a color
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

        // Update current time immediately and then every second
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);

        // Refresh election data every 10 seconds
        setInterval(function() {
            if (currentActiveElection) {
                loadElectionResults(currentActiveElection);
            }
        }, 10000);
    });
</script>
</body>
</html>