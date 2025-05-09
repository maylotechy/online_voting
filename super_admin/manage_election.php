<?php
require '../config/db.php';
include "../middleware/auth_admin.php";
$pdo = $GLOBALS['pdo'];

$election_id = $_GET['id'] ?? null;
if (!$election_id) {
    header("Location: ongoing_elections.php");
    exit();
}

try {
    // Fetch election details
    $stmt = $pdo->prepare("
        SELECT e.*, COUNT(c.id) as candidate_count
        FROM elections e
        LEFT JOIN candidates c ON e.id = c.election_id
        WHERE e.id = ?
        GROUP BY e.id
    ");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        header("Location: ongoing_elections.php");
        exit();
    }

    // Fetch status logs
    $logs_stmt = $pdo->prepare("
        SELECT l.*, a.username 
        FROM election_status_logs l
        LEFT JOIN admins a ON l.admin_id = a.id
        WHERE l.election_id = ?
        ORDER BY l.changed_at DESC
        LIMIT 10
    ");
    $logs_stmt->execute([$election_id]);
    $status_logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pause']) && $election['status'] === 'ongoing') {
        $new_status = 'paused';
    } elseif (isset($_POST['resume']) && $election['status'] === 'paused') {
        $new_status = 'ongoing';
    } else {
        $new_status = null;
    }

    if ($new_status) {
        try {
            $pdo->beginTransaction();

            // Update status
            $update_stmt = $pdo->prepare("
                UPDATE elections 
                SET status = ?, status_changed_at = NOW() 
                WHERE id = ?
            ");
            $update_stmt->execute([$new_status, $election_id]);

            // Log the change
            $log_stmt = $pdo->prepare("
                INSERT INTO election_status_logs 
                (election_id, admin_id, old_status, new_status) 
                VALUES (?, ?, ?, ?)
            ");
            $log_stmt->execute([
                $election_id,
                $_SESSION['admin_id'],
                $election['status'],
                $new_status
            ]);

            $pdo->commit();

            $_SESSION['toastr'] = [
                'type' => 'success',
                'message' => "Election status updated to {$new_status}"
            ];
            header("Location: manage_election.php?id={$election_id}");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['toastr'] = [
                'type' => 'error',
                'message' => 'Failed to update status: ' . $e->getMessage()
            ];
        }
    }
}

// [Keep your HTML head and navbar/sidebar from create_elections.php]
?>

    <div class="content-wrapper p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Election: <?= htmlspecialchars($election['title']) ?></h2>
            <span class="badge badge-<?=
            $election['status'] === 'ongoing' ? 'success' :
                ($election['status'] === 'paused' ? 'warning' : 'danger')
            ?>">
            <?= ucfirst($election['status']) ?>
        </span>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Election Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5><i class="far fa-calendar-alt mr-2"></i>Time Period</h5>
                                <p>
                                    <strong>Start:</strong> <?= date('M j, Y g:i A', strtotime($election['start_time'])) ?><br>
                                    <strong>End:</strong> <?= date('M j, Y g:i A', strtotime($election['end_time'])) ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle mr-2"></i>Details</h5>
                                <p>
                                    <strong>Candidates:</strong> <?= $election['candidate_count'] ?><br>
                                    <strong>Scope:</strong> <?= ucfirst(str_replace('-', ' ', $election['scope'])) ?>
                                </p>
                            </div>
                        </div>

                        <h5><i class="fas fa-align-left mr-2"></i>Description</h5>
                        <p><?= nl2br(htmlspecialchars($election['description'])) ?></p>

                        <?php if ($election['rules_file']): ?>
                            <h5><i class="fas fa-file-pdf mr-2"></i>Election Rules</h5>
                            <a href="../<?= htmlspecialchars($election['rules_file']) ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-download mr-2"></i>Download Rules PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h3 class="card-title">Election Controls</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($election['status'] === 'ongoing'): ?>
                                <button type="submit" name="pause" class="btn btn-warning btn-block mb-3">
                                    <i class="fas fa-pause mr-2"></i> Pause Election
                                </button>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Pausing will temporarily stop voting.
                                </div>
                            <?php elseif ($election['status'] === 'paused'): ?>
                                <button type="submit" name="resume" class="btn btn-success btn-block mb-3">
                                    <i class="fas fa-play mr-2"></i> Resume Election
                                </button>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Resuming will re-enable voting.
                                </div>
                            <?php endif; ?>
                        </form>

                        <hr>

                        <h5><i class="fas fa-history mr-2"></i>Recent Activity</h5>
                        <div class="list-group">
                            <?php foreach ($status_logs as $log): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= ucfirst($log['new_status']) ?></strong>
                                        <small><?= date('M j, g:i A', strtotime($log['changed_at'])) ?></small>
                                    </div>
                                    <small class="text-muted">
                                        Changed from <?= $log['old_status'] ?>
                                        <?php if ($log['username']): ?>
                                            by <?= htmlspecialchars($log['username']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Confirmation for status changes
            $('button[name="pause"], button[name="resume"]').click(function(e) {
                e.preventDefault();
                const action = $(this).attr('name');
                const actionText = action === 'pause' ? 'pause' : 'resume';

                Swal.fire({
                    title: `Confirm ${actionText} election?`,
                    text: action === 'pause'
                        ? "This will temporarily stop voting. You can resume later."
                        : "This will re-enable voting for participants.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: `Yes, ${actionText}`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');
                        $(this).prop('disabled', true);
                        $(this).closest('form').submit();
                    }
                });
            });
        });
    </script><?php
