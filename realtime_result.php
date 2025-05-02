<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEIT Election Real-Time Results</title>

    <!-- Importing Satoshi font -->
    <link href="https://fonts.googleapis.com/css2?family=Satoshi:wght@400;600&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS for layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js library for chart visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Custom styles */
        body {
            font-family: 'Satoshi', sans-serif;
        }

        .header {
            background-color: teal;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .card {
            margin-bottom: 20px;
        }

        .candidate-name {
            font-weight: bold;
        }

        .no-election-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 20px;
            text-align: center;
            font-size: 18px;
            border-radius: 5px;
        }

        .chart-container {
            max-width: 600px;
            margin: 20px auto;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <h1>CEIT Election 2025 - Real-Time Results</h1>
</div>

<!-- No election message -->
<div id="no-election" class="no-election-message" style="display:none;">
    No election is happening at the moment.
</div>

<!-- Election results display -->
<div id="election-results" class="container mt-5">
    <!-- Chart container -->
    <div class="chart-container">
        <canvas id="electionChart"></canvas>
    </div>

    <!-- Election candidates results -->
    <div id="candidates" class="row">
        <!-- Dynamically generated results will go here -->
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Simulate fetching election data
        let electionData = [
            {
                position: "President",
                candidates: [
                    { name: "John Doe", votes: 150 },
                    { name: "Jane Smith", votes: 120 },
                    { name: "Mark Lee", votes: 80 }
                ]
            },
            {
                position: "Vice President",
                candidates: [
                    { name: "Alice Brown", votes: 180 },
                    { name: "Bob Green", votes: 110 },
                    { name: "Eve White", votes: 90 }
                ]
            }
        ];

        // Check if there is election data
        if (electionData.length === 0) {
            document.getElementById('no-election').style.display = 'block';
            document.getElementById('election-results').style.display = 'none';
        } else {
            document.getElementById('no-election').style.display = 'none';
            document.getElementById('election-results').style.display = 'block';

            // Display election results as charts and tables
            displayElectionResults(electionData);
        }

        // Simulate real-time updates (this would normally be done via AJAX/WebSockets)
        setInterval(function () {
            // Simulating vote updates
            electionData.forEach(function (election) {
                election.candidates.forEach(function (candidate) {
                    candidate.votes += Math.floor(Math.random() * 5); // Random increment to simulate votes
                });
            });

            // Re-render the results (this is a simple example, you can refine with dynamic chart updates)
            updateElectionResults(electionData);
        }, 1000); // Update every 1 second

        // Function to display election results and generate chart
        function displayElectionResults(electionData) {
            electionData.forEach(function (election) {
                let positionCard = `
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">${election.position}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Total Votes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                // Prepare data for chart
                let chartLabels = [];
                let chartVotes = [];
                election.candidates.forEach(function (candidate) {
                    chartLabels.push(candidate.name);
                    chartVotes.push(candidate.votes);
                    positionCard += `
                            <tr>
                                <td class="candidate-name">${candidate.name}</td>
                                <td>${candidate.votes}</td>
                            </tr>
                        `;
                });

                positionCard += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;

                document.getElementById('candidates').innerHTML += positionCard;

                // Generate chart for this position
                let ctx = document.getElementById('electionChart').getContext('2d');
                window.electionChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: election.position,
                            data: chartVotes,
                            backgroundColor: 'teal',
                            borderColor: 'darkcyan',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            });
        }

        // Function to update election results (including chart)
        function updateElectionResults(electionData) {
            // Update the vote count in the table
            let tableRows = '';
            electionData.forEach(function (election) {
                election.candidates.forEach(function (candidate) {
                    let row = document.querySelector(`td:contains('${candidate.name}')`).parentElement;
                    row.querySelector('td:nth-child(2)').textContent = candidate.votes;
                });
            });

            // Update chart with new data
            electionData.forEach(function (election, index) {
                let chartData = election.candidates.map(candidate => candidate.votes);
                window.electionChart.data.datasets[index].data = chartData;
                window.electionChart.update();
            });
        }
    });
</script>

</body>
</html>
