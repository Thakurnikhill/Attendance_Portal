<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role('student');
require_once __DIR__ . '/../config/db.php';
require('../fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',16);
        $this->SetTextColor(229, 57, 53);
        $this->Cell(0,10,'Attendance Report',0,1,'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
    }
}

try {
    $student_id = $_SESSION['user_id'];
    $student_name = htmlspecialchars($_SESSION['full_name']);

    // Fetch data
    $stmt = $conn->prepare("
        SELECT attendance_date, status 
        FROM attendance 
        WHERE student_id = ? 
        ORDER BY attendance_date ASC
    ");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Create PDF
    $pdf = new PDF();
    $pdf->AddPage();

    // Student Info
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,"Student Name: $student_name",0,1);
    $pdf->Ln(5);

    // Table header
    $pdf->SetFillColor(229, 57, 53);
    $pdf->SetTextColor(255);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,10,'Date',1,0,'C',true);
    $pdf->Cell(40,10,'Status',1,1,'C',true);

    // Table data
    $pdf->SetTextColor(0);
    $pdf->SetFont('Arial','',12);
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(50,10,$row['attendance_date'],1,0,'C');
        $pdf->SetTextColor($row['status'] === 'Present' ? 0 : 229, 57, 53);
        $pdf->Cell(40,10,$row['status'],1,1,'C');
        $pdf->SetTextColor(0);
    }

    // Output PDF
    $pdf->Output('D', 'attendance_report.pdf');

} catch (Exception $e) {
    error_log("PDF generation error: " . $e->getMessage());
    header("Location: dashboard.php?error=report_failed");
    exit();
}
?>
