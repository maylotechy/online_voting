<?php
// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['admin_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../custom_css/side-bar.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="manage_admin.php" class="nav-link"><i class="nav-icon fas fa-user-shield"></i><p>College Admins</p></a></li>
                    <li class="nav-item">
                        <a href="manage_candidates.php" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Candidates</p>
                        </a>
                    </li>
                    <li class="nav-item"><a href="templates.php" class="nav-link"><i class="nav-icon fas fa-file-alt"></i><p>Election Templates</p></a></li>
                    <li class="nav-item"><a href="create_elections.php" class="nav-link"><i class="nav-icon fas fa-rocket"></i><p>Launch Univ. Election</p></a></li>
                    <li class="nav-item"><a href="results.php" class="nav-link active"><i class="nav-icon fas fa-chart-bar"></i><p>Election Results</p></a></li>
                    <li class="nav-item"><a href="election_history.php" class="nav-link"><i class="nav-icon fas fa-history"></i><p>Election History</p></a></li>
                    <li class="nav-item"><a href="export_results.php" class="nav-link"><i class="nav-icon fas fa-download"></i><p>Export Results</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid pt-3">

                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="scopeSelect">Election Scope</label>
                        <select class="form-control" id="scopeSelect">
                            <option value="university">University-wide</option>
                            <option value="college">College-based</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="collegeSelectContainer" style="display: none;">
                        <label for="collegeSelect">Select College</label>
                        <select class="form-control" id="collegeSelect">
                            <option value="ceit">CEIT</option>
                            <option value="ca">CA</option>
                            <option value="cbdem">CBDEM</option>
                            <option value="cas">CASS</option>
                            <!-- Add more colleges here -->
                        </select>
                    </div>
                </div>

                <!-- University-wide Results -->
                <div id="universityResults">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">University-Wide Election Results</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Candidate</th>
                                    <th>Votes</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr><td>Governor</td><td>John Doe</td><td>500</td></tr>
                                <tr><td>Governor</td><td>Jane Smith</td><td>300</td></tr>
                                <tr><td>Vice Governor</td><td>Chris Lee</td><td>200</td></tr>
                                <tr><td>Vice Governor</td><td>Alice Walker</td><td>350</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- College Results -->
                <div id="collegeResults" style="display: none;">
                    <!-- CEIT -->
                    <div class="card college-card" data-college="ceit">
                        <div class="card-header"><h3 class="card-title">College of Engineering and IT (CEIT)</h3></div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead><tr><th>Position</th><th>Candidate</th><th>Votes</th></tr></thead>
                                <tbody>
                                <tr><td>Governor</td><td>John Doe</td><td>500</td></tr>
                                <tr><td>Vice Governor</td><td>Jane Smith</td><td>300</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CA -->
                    <div class="card college-card" data-college="ca" style="display: none;">
                        <div class="card-header"><h3 class="card-title">College of Agriculture (CA)</h3></div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead><tr><th>Position</th><th>Candidate</th><th>Votes</th></tr></thead>
                                <tbody></tbody> <!-- Empty tbody -->
                            </table>
                        </div>
                    </div>
                    <!-- CA -->
                    <div class="card college-card" data-college="cbdem" style="display: none;">
                        <div class="card-header"><h3 class="card-title">College of Business and Development Management (CBDEM)</h3></div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead><tr><th>Position</th><th>Candidate</th><th>Votes</th></tr></thead>
                                <tbody></tbody> <!-- Empty tbody -->
                            </table>
                        </div>
                    </div>


                    <!-- Add more college cards below with different data-college values -->
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Scripts -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>

<script>
    $(document).ready(function () {
        $('#scopeSelect').on('change', function () {
            const scope = $(this).val();
            if (scope === 'college') {
                $('#universityResults').hide();
                $('#collegeResults').show();
                $('#collegeSelectContainer').show();
                $('#collegeSelect').trigger('change');
            } else {
                $('#universityResults').show();
                $('#collegeResults').hide();
                $('#collegeSelectContainer').hide();
            }
        });

        $('#collegeSelect').on('change', function () {
            const selectedCollege = $(this).val();
            $('.college-card').hide();

            const $selectedCard = $(`.college-card[data-college="${selectedCollege}"]`);
            $selectedCard.show();

            // Check for data in the selected college only
            $selectedCard.find('tbody').each(function () {
                const $tbody = $(this);
                const rowCount = $tbody.find('tr').length;

                // Remove existing "no data" row if any
                $tbody.find('.no-data-row').remove();

                if (rowCount === 0) {
                    $tbody.append(`
                    <tr class="no-data-row bg-danger text-white text-center">
                        <td colspan="3">NO DATA AVAILABLE</td>
                    </tr>
                `);
                }
            });
        });

        // Optional: Check for university-wide table (on load)
        $('#universityResults tbody').each(function () {
            const $tbody = $(this);
            if ($tbody.find('tr').length === 0) {
                $tbody.append(`
                <tr class="bg-danger text-white text-center">
                    <td colspan="3">NO DATA AVAILABLE</td>
                </tr>
            `);
            }
        });
    });
</script>


</body>
</html>
