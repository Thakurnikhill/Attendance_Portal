<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role('parent');
require_once __DIR__ . '/../config/db.php';
require('../fpdf/fpdf.php');

$parent_id = $_SESSION['user_id'];

// Get linked student
$stmt = $conn->prepare("
    SELECT u.user_id, u.full_name
    FROM users u
    JOIN parent_student ps ON ps.student_id = u.user_id
    WHERE ps.parent_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $parent_id);
$stmt->execute();
$stmt->bind_result($student_id, $student_name);
$stmt->fetch();
$stmt->close();

if (!$student_id) die("No student linked to this parent account.");

// Get attendance
$stmt = $conn->prepare("SELECT attendance_date, status FROM attendance WHERE student_id = ? ORDER BY attendance_date ASC");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="attendance_report.pdf"');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, "Attendance Report", 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, "Student: $student_name", 0, 1);

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Date', 1);
$pdf->Cell(40, 10, 'Status', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(50, 10, $row['attendance_date'], 1);
    $pdf->Cell(40, 10, $row['status'], 1);
    $pdf->Ln();
}
$pdf->Output();
exit;
?>
