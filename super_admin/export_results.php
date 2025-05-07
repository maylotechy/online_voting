<?php
// export_results.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../vendor/autoload.php');
ob_start();

require_once '../config/db.php'; // Ensure this is included first

function exportElectionResults($electionId) {
    global $pdo;  // Explicitly reference the global $pdo object

    // Get election details
    $electionQuery = "SELECT * FROM elections WHERE id = :electionId";
    $electionStmt = $pdo->prepare($electionQuery);
    $electionStmt->bindParam(':electionId', $electionId, PDO::PARAM_INT);
    $electionStmt->execute();
    $election = $electionStmt->fetch();

    if (!$election) {
        echo "Election not found";
        return;
    }

    // Get total voters count
    $votersQuery = "SELECT COUNT(*) as total_voters FROM students WHERE is_enrolled = 1";
    $votersResult = $pdo->query($votersQuery);
    $votersData = $votersResult->fetch();
    $totalVoters = $votersData['total_voters'];

    // Get votes cast count
    $votesCastQuery = "SELECT COUNT(DISTINCT student_id) as votes_cast FROM votes WHERE election_id = :electionId";
    $votesCastStmt = $pdo->prepare($votesCastQuery);
    $votesCastStmt->bindParam(':electionId', $electionId, PDO::PARAM_INT);
    $votesCastStmt->execute();
    $votesCastData = $votesCastStmt->fetch();
    $votesCast = $votesCastData['votes_cast'];

    // Calculate participation rate
    $participationRate = $totalVoters > 0 ? round(($votesCast / $totalVoters) * 100, 2) : 0;

    // Get all positions in this election
    $positionsQuery = "SELECT DISTINCT p.id, p.name, p.max_winners, p.order
                   FROM positions p
                   JOIN candidates c ON p.id = c.position_id
                   WHERE c.election_id = :electionId
                   ORDER BY p.order ASC";
    $positionsStmt = $pdo->prepare($positionsQuery);
    $positionsStmt->bindParam(':electionId', $electionId, PDO::PARAM_INT);
    $positionsStmt->execute();
    $positionsResult = $positionsStmt->fetchAll();

    // Create the PDF object with modern settings
    if (class_exists('MYPDF')) {
        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    } else {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    }

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set document properties
    $pdf->SetCreator('USM Online Voting System');
    $pdf->SetAuthor('University of Southern Mindanao');
    $pdf->SetTitle($election['title'] . ' - Election Results');
    $pdf->SetSubject('Election Results');

    // Set margins
    $pdf->SetMargins(15, 15, 15);

    // Add a page
    $pdf->AddPage();

    // Add left logo
    $leftLogoPath = '../asssets/super_admin/osa_profile.png'; // Adjust path as needed
    if (file_exists($leftLogoPath)) {
        // Add logo to upper left
        $pdf->Image($leftLogoPath, 15, 15, 25, 0, 'PNG', '', 'T', false, 300, 'L');
    } else {
        // Create a placeholder logo with styled text
        $pdf->SetFont('times', 'B', 14);
        $pdf->SetFillColor(0, 51, 102); // Dark blue background
        $pdf->SetTextColor(255, 255, 255); // White text
        $pdf->Cell(25, 15, 'USM', 0, 0, 'L', 1);
        $pdf->SetTextColor(0, 0, 0); // Reset text color to black
    }

    // Add right logo (USG logo)
    $rightLogoPath = '../asssets/super_admin/usm_comelec.png'; // Adjust path as needed
    if (file_exists($rightLogoPath)) {
        // Add logo to upper right
        $pdf->Image($rightLogoPath, 170, 15, 25, 0, 'PNG', '', 'T', false, 300, 'R');
    } else {
        // Create a placeholder logo with styled text if image doesn't exist
        $pdf->SetXY(170, 15);
        $pdf->SetFont('times', 'B', 14);
        $pdf->SetFillColor(0, 102, 51); // Dark green background
        $pdf->SetTextColor(255, 255, 255); // White text
        $pdf->Cell(25, 15, 'USG', 0, 0, 'R', 1);
        $pdf->SetTextColor(0, 0, 0); // Reset text color to black
    }

    // Set document title with modern styling - centered between logos
    $pdf->SetXY(40, 15);
    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(130, 7, 'UNIVERSITY OF SOUTHERN MINDANAO', 0, 1, 'C');
    $pdf->SetXY(40, 22);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(130, 6, 'UNIVERSITY STUDENT GOVERNMENT (USG)', 0, 1, 'C');
    $pdf->SetXY(40, 28);
    $pdf->SetFont('times', 'B', 16);
    $pdf->SetTextColor(0, 51, 102); // Dark blue text for emphasis
    $pdf->Cell(130, 8, '2027 USG ELECTION', 0, 1, 'C');
    $pdf->SetXY(40, 36);
    $pdf->Cell(130, 8, 'ELECTION RESULT', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0); // Reset text color

    // Add a decorative line
    $pdf->SetDrawColor(0, 51, 102); // Dark blue
    $pdf->SetLineWidth(1);
    $pdf->Line(15, 48, 195, 48);

    // Add date
    $pdf->SetFont('times', 'I', 10);
    $pdf->SetXY(15, 50);
    $pdf->Cell(180, 6, 'Generated on: ' . date('F j, Y'), 0, 1, 'R');
    $pdf->Ln(5);

    // Process each position with modern styling
    foreach ($positionsResult as $position) {
        // Add styled position header
        $pdf->SetFont('times', 'B', 14);
        $pdf->SetFillColor(0, 51, 102); // Dark blue background
        $pdf->SetTextColor(255, 255, 255); // White text
        $pdf->Cell(0, 10, strtoupper($position['name']), 0, 1, 'L', 1);
        $pdf->SetTextColor(0, 0, 0); // Reset text color
        $pdf->Ln(5);

        // Create table header with modern styling
        $pdf->SetFont('times', 'B', 10);
        $pdf->SetFillColor(240, 240, 240); // Light grey background
        $pdf->Cell(60, 8, 'CANDIDATE NAME', 1, 0, 'C', 1);
        $pdf->Cell(30, 8, 'VOTES', 1, 0, 'C', 1);
        $pdf->Cell(40, 8, 'PERCENTAGE', 1, 0, 'C', 1);
        $pdf->Cell(40, 8, 'FINAL PERCENT', 1, 0, 'C', 1);
        $pdf->Ln();

        // Get candidates for this position
        $candidatesQuery = "SELECT c.id, s.student_id, CONCAT(s.last_name, ', ', s.first_name) as student_name, 
                           COUNT(v.id) as vote_count, c.party_list
                           FROM candidates c
                           JOIN students s ON c.student_id = s.student_id
                           LEFT JOIN votes v ON c.id = v.candidate_id AND v.election_id = :electionId1
                           WHERE c.position_id = :positionId AND c.election_id = :electionId2
                           GROUP BY c.id, s.student_id, s.first_name, s.last_name, c.party_list
                           ORDER BY vote_count DESC";
        $candidatesStmt = $pdo->prepare($candidatesQuery);
        $candidatesStmt->bindParam(':electionId1', $electionId, PDO::PARAM_INT);
        $candidatesStmt->bindParam(':positionId', $position['id'], PDO::PARAM_INT);
        $candidatesStmt->bindParam(':electionId2', $electionId, PDO::PARAM_INT);

        if (!$candidatesStmt->execute()) {
            print_r($candidatesStmt->errorInfo());
            exit;
        }

        $candidatesResult = $candidatesStmt->fetchAll();

        $candidates = [];
        $positionTotalVotes = 0;

        foreach ($candidatesResult as $candidate) {
            $candidates[] = $candidate;
            $positionTotalVotes += $candidate['vote_count'];
        }

        // Output each candidate
        foreach ($candidates as $index => $candidate) {
            $percentage = $positionTotalVotes > 0 ? round(($candidate['vote_count'] / $positionTotalVotes) * 100, 2) : 0;
            $finalPercentage = $totalVoters > 0 ? round(($candidate['vote_count'] / $totalVoters) * 100, 2) : 0;

            // Handle winners and ties
            $candidateStatus = '';

            if ($index === 0) {
                // First candidate is always either winner or tied
                $candidateStatus = "(Winner)";
                // Check if there's a tie with the next candidate
                if (isset($candidates[$index+1]) && $candidates[$index+1]['vote_count'] === $candidate['vote_count']) {
                    $candidateStatus = "(Tie)";
                }
            } else if ($index < $position['max_winners']) {
                // Check if this candidate is tied with the previous one
                if ($candidates[$index-1]['vote_count'] === $candidate['vote_count']) {
                    $candidateStatus = "(Tie)";
                } else {
                    $candidateStatus = "(Winner)";
                }
            } else {
                // Not within max_winners, but check if tied with last winner
                if ($index >= $position['max_winners'] &&
                    isset($candidates[$position['max_winners']-1]) &&
                    $candidates[$position['max_winners']-1]['vote_count'] === $candidate['vote_count']) {
                    $candidateStatus = "(Tie)";
                } else {
                    $candidateStatus = ($index + 1);
                }
            }

            // Alternate row colors for better readability
            $rowColor = $index % 2 === 0 ? array(255, 255, 255) : array(245, 245, 245);
            $pdf->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]);

            // Highlight winners/ties with special styling
            $isWinnerOrTie = (strpos($candidateStatus, 'Winner') !== false || strpos($candidateStatus, 'Tie') !== false);
            if ($isWinnerOrTie) {
                $pdf->SetFont('times', 'B', 10); // Bold for winners/ties
                $pdf->SetTextColor(0, 102, 0); // Dark green for winners/ties
            } else {
                $pdf->SetFont('times', '', 10); // Regular font
                $pdf->SetTextColor(0, 0, 0); // Black text
            }

            // Draw the candidate row with styling
            $pdf->Cell(60, 8, $candidateStatus . ' ' . $candidate['student_name'], 1, 0, 'L', 1);
            $pdf->Cell(30, 8, $candidate['vote_count'], 1, 0, 'C', 1);
            $pdf->Cell(40, 8, $percentage . ' %', 1, 0, 'C', 1);
            $pdf->Cell(40, 8, $finalPercentage . ' %', 1, 0, 'C', 1);
            $pdf->Ln();

            // Reset text color after each row
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->Ln();
    }

    // Add summary statistics with modern styling
    $pdf->Ln(5);
    $pdf->SetDrawColor(0, 51, 102); // Dark blue
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(0, 10, 'ELECTION SUMMARY', 0, 1, 'L');

    // Create modern statistics box
    $pdf->SetFillColor(245, 245, 245); // Light grey background
    $pdf->RoundedRect(15, $pdf->GetY(), 180, 40, 3.5, '1111', 'DF');
    $pdf->Ln(5);

    // Add summary content
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(100, 10, 'REGISTERED VOTERS:', 0, 0, 'R');
    $pdf->SetFont('times', '', 12);
    $pdf->Cell(80, 10, $totalVoters, 0, 1, 'L');

    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(100, 10, 'VOTES CAST:', 0, 0, 'R');
    $pdf->SetFont('times', '', 12);
    $pdf->Cell(80, 10, $votesCast, 0, 1, 'L');

    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(100, 10, 'PARTICIPATION RATE:', 0, 0, 'R');
    $pdf->SetFont('times', '', 12);
    $pdf->Cell(80, 10, $participationRate . ' %', 0, 1, 'L');

    // Add signatures
    $pdf->Ln(15);

    // Add signature lines
    $pdf->SetDrawColor(0, 0, 0); // Black lines for signatures
    $pdf->SetLineWidth(0.2);

    // Calculate positions for 3 evenly spaced signatures
    $signatureWidth = 50;
    $gap = (180 - (3 * $signatureWidth)) / 2;
    $startX = 15;

    // First signature
    $pdf->Line($startX, $pdf->GetY(), $startX + $signatureWidth, $pdf->GetY());
    $pdf->SetFont('times', '', 8);
    $pdf->SetXY($startX, $pdf->GetY() + 1);
    $pdf->Cell($signatureWidth, 5, 'COMELEC CHAIRPERSON', 0, 1, 'C');
    $pdf->SetXY($startX, $pdf->GetY());
    $pdf->SetFont('times', 'B', 10);
    $pdf->Cell($signatureWidth, 5, 'Niki', 0, 0, 'C');

    // Second signature
    $secondX = $startX + $signatureWidth + $gap;
    $pdf->SetXY($secondX, $pdf->GetY() - 6);
    $pdf->Line($secondX, $pdf->GetY(), $secondX + $signatureWidth, $pdf->GetY());
    $pdf->SetFont('times', '', 8);
    $pdf->SetXY($secondX, $pdf->GetY() + 1);
    $pdf->Cell($signatureWidth, 5, 'UNIVERSITY PRESIDENT', 0, 1, 'C');
    $pdf->SetXY($secondX, $pdf->GetY());
    $pdf->SetFont('times', 'B', 10);
    $pdf->Cell($signatureWidth, 5, 'Lany', 0, 0, 'C');

    // Third signature
    $thirdX = $secondX + $signatureWidth + $gap;
    $pdf->SetXY($thirdX, $pdf->GetY() - 6);
    $pdf->Line($thirdX, $pdf->GetY(), $thirdX + $signatureWidth, $pdf->GetY());
    $pdf->SetFont('times', '', 8);
    $pdf->SetXY($thirdX, $pdf->GetY() + 1);
    $pdf->Cell($signatureWidth, 5, 'Systems Developer', 0, 1, 'C');
    $pdf->SetXY($thirdX, $pdf->GetY());
    $pdf->SetFont('times', 'B', 10);
    $pdf->Cell($signatureWidth, 5, 'Maylo', 0, 0, 'C');

    // Add footer
    $pdf->Ln(15);
    $pdf->SetFont('times', 'I', 8);
    $pdf->Cell(0, 10, 'This is an official document of the University of Southern Mindanao University Student Government.', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated by the USM Online Voting System on ' . date('F j, Y, g:i a'), 0, 1, 'C');

    // Output the PDF
    $pdf->Output('election_results_' . $electionId . '.pdf', 'D');
}

// Main execution logic
if (isset($_GET['election_id'])) {
    // Handle GET request
    $electionId = $_GET['election_id'];
    exportElectionResults($electionId);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_id'])) {
    // Handle POST request
    exportElectionResults($_POST['election_id']);
} else {
    require_once '../components/not_found.php';
    showNotFoundPage([
            'title' => 'Invalid Request',
            'message' => 'The requested election results could not be found.',
            'primary_action' => [
                'text' => 'Go Back',
                'url' => 'javascript:history.back()',
                'icon' => 'arrow-left'
            ],
            'secondary_action' => [
                'text' => 'Return Home',
                'url' => 'results.php',
                'icon' => 'home'
            ]]
    );
    exit;
}
?>