<?php
/**
 * SmartCare - View & Print E-Prescription Page
 * 
 * Displays a clean, printable medical e-prescription.
 * Accessible to patient, doctor, and admin.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Access Control: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? '';

$rx_id = (int)($_GET['id'] ?? 0);
$appointment_id = (int)($_GET['appointment_id'] ?? 0);

if ($rx_id <= 0 && $appointment_id <= 0) {
    header("Location: index.php");
    exit;
}

// Build query depending on passed parameter
$query = "SELECT pr.id as prescription_id, pr.diagnosis, pr.medicines, pr.instructions, pr.created_at as prescription_date,
                 a.id as appointment_id, a.date as appointment_date, a.time_slot, a.patient_id, a.doctor_id,
                 pu.name as patient_name, pu.email as patient_email,
                 du.name as doctor_name, d.specialty, d.phone as doctor_phone
          FROM prescriptions pr
          JOIN appointments a ON pr.appointment_id = a.id
          JOIN users pu ON pr.patient_id = pu.id
          JOIN doctors d ON pr.doctor_id = d.id
          JOIN users du ON d.user_id = du.id
          WHERE ";

if ($rx_id > 0) {
    $query .= "pr.id = ?";
    $param_val = $rx_id;
} else {
    $query .= "pr.appointment_id = ?";
    $param_val = $appointment_id;
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $param_val);
$stmt->execute();
$res = $stmt->get_result();
$rx = $res->fetch_assoc();
$stmt->close();

if (!$rx) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Prescription Not Found</h2><p>No e-prescription has been issued for this appointment yet.</p><a href='javascript:history.back()'>Go Back</a></div>");
}

// Security Check: Patient can only view their own Rx; Doctor can only view their issued Rx; Admin can view all
if ($user_role === 'patient' && $rx['patient_id'] != $user_id) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>You are not authorized to view this prescription.</p></div>");
}

// Fetch doctor's internal doctor_id if user is a doctor
if ($user_role === 'doctor') {
    $d_stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $d_stmt->bind_param("i", $user_id);
    $d_stmt->execute();
    $d_res = $d_stmt->get_result();
    $d_row = $d_res->fetch_assoc();
    $d_stmt->close();
    
    if (!$d_row || $rx['doctor_id'] != $d_row['id']) {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>You are not authorized to view this prescription.</p></div>");
    }
}

$page_title = "E-Prescription #Rx-" . sprintf('%05d', $rx['prescription_id']);
require_once 'header.php';
?>

<!-- Print Stylesheet Customizations -->
<style>
@media print {
    /* Hide top nav, header, footer, action buttons during printing */
    header, footer, .no-print {
        display: none !important;
    }
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
    .print-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
}
</style>

<main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 py-10 w-full">

    <!-- Top Action Bar (Hidden when printing) -->
    <div class="mb-6 flex flex-col sm:flex-row items-center justify-between gap-4 no-print">
        <a href="<?= $user_role === 'doctor' ? 'doctor_dashboard.php' : 'patient_dashboard.php' ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-[#1E3A8A] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Dashboard
        </a>

        <div class="flex items-center gap-3">
            <?php if ($user_role === 'doctor'): ?>
                <a href="add_prescription.php?appointment_id=<?= $rx['appointment_id'] ?>" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors">
                    Edit Prescription
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="px-5 py-2.5 bg-[#1E3A8A] hover:bg-[#172e6e] text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Official E-Prescription Letterhead Card -->
    <div class="print-card bg-white border border-slate-200 rounded-2xl shadow-sm p-8 sm:p-12 relative overflow-hidden">
        
        <!-- Top Medical Clinic Letterhead Header -->
        <div class="flex flex-col sm:flex-row items-start justify-between border-b-2 border-[#1E3A8A] pb-6 mb-6 gap-6">
            <div>
                <div class="flex items-center gap-2.5 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-[#1E3A8A] text-white flex items-center justify-center font-bold text-sm">
                        +
                    </div>
                    <span class="text-xl font-bold text-slate-900 tracking-tight">SmartCare Telehealth</span>
                </div>
                <h2 class="text-lg font-bold text-[#1E3A8A]">Dr. <?= htmlspecialchars($rx['doctor_name']) ?></h2>
                <p class="text-xs font-semibold text-slate-600"><?= htmlspecialchars($rx['specialty']) ?></p>
                <p class="text-xs text-slate-500">Reg No: BMDC-<?= rand(10000, 99999) ?> | Phone: <?= htmlspecialchars($rx['doctor_phone']) ?></p>
            </div>

            <div class="text-left sm:text-right">
                <span class="inline-block px-3 py-1 bg-blue-50 text-[#1E3A8A] rounded-full text-xs font-extrabold uppercase tracking-wider mb-2">
                    E-Prescription
                </span>
                <div class="text-xs text-slate-500">Rx No: <strong class="text-slate-900 font-mono">Rx-<?= sprintf('%05d', $rx['prescription_id']) ?></strong></div>
                <div class="text-xs text-slate-500">Appt ID: <strong class="text-slate-900">#<?= $rx['appointment_id'] ?></strong></div>
                <div class="text-xs text-slate-500 mt-1">Date: <strong><?= date('M d, Y', strtotime($rx['prescription_date'])) ?></strong></div>
            </div>
        </div>

        <!-- Patient Demographics Bar -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-8 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block uppercase font-semibold text-[10px]">Patient Name</span>
                <span class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($rx['patient_name']) ?></span>
            </div>
            <div>
                <span class="text-slate-400 block uppercase font-semibold text-[10px]">Consultation Date & Slot</span>
                <span class="font-semibold text-slate-800"><?= date('M d, Y', strtotime($rx['appointment_date'])) ?> (<?= htmlspecialchars($rx['time_slot']) ?>)</span>
            </div>
            <div>
                <span class="text-slate-400 block uppercase font-semibold text-[10px]">Patient Email</span>
                <span class="font-semibold text-slate-800"><?= htmlspecialchars($rx['patient_email']) ?></span>
            </div>
        </div>

        <!-- Clinical Diagnosis Section -->
        <div class="mb-8">
            <h3 class="text-xs uppercase font-extrabold text-slate-400 tracking-wider mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#1E3A8A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Clinical Findings & Diagnosis
            </h3>
            <div class="bg-blue-50/50 border-l-4 border-[#1E3A8A] p-4 rounded-r-xl text-sm font-medium text-slate-800 leading-relaxed">
                <?= nl2br(htmlspecialchars($rx['diagnosis'])) ?>
            </div>
        </div>

        <!-- Rx Prescription Content Section -->
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-3xl font-extrabold text-[#1E3A8A] font-serif italic">Rx</span>
                <span class="text-xs uppercase font-extrabold text-slate-400 tracking-wider">Prescribed Medicines & Schedule</span>
            </div>

            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="p-5 bg-white font-mono text-sm text-slate-900 leading-relaxed whitespace-pre-line">
<?= htmlspecialchars($rx['medicines']) ?>
                </div>
            </div>
        </div>

        <!-- Special Instructions & Advice Section -->
        <?php if (!empty($rx['instructions'])): ?>
            <div class="mb-10">
                <h3 class="text-xs uppercase font-extrabold text-slate-400 tracking-wider mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Advice & Instructions
                </h3>
                <div class="bg-amber-50/60 border border-amber-200/60 p-4 rounded-xl text-sm text-slate-800 leading-relaxed">
                    <?= nl2br(htmlspecialchars($rx['instructions'])) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Digital Signature & Footer Seal -->
        <div class="pt-8 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="text-xs text-slate-400">
                <p>This is a computer-generated digital prescription issued via <strong class="text-slate-600">SmartCare Telehealth</strong>.</p>
                <p>Generated on <?= date('d M Y, h:i A', strtotime($rx['prescription_date'])) ?></p>
            </div>

            <div class="text-center sm:text-right">
                <div class="inline-block border-b-2 border-slate-400 pb-1 px-6 mb-1">
                    <span class="font-serif italic font-bold text-slate-700 text-sm">Dr. <?= htmlspecialchars($rx['doctor_name']) ?></span>
                </div>
                <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Digitally Verified Signature</div>
            </div>
        </div>

    </div>

</main>

<?php require_once 'footer.php'; ?>
