<?php
/**
 * SmartCare - Add / Issue E-Prescription Page
 * 
 * Allows doctors to issue a digital prescription for a specific patient appointment.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Access Control: Must be logged in as Doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get Doctor ID
$doc_stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = ?");
$doc_stmt->bind_param("i", $user_id);
$doc_stmt->execute();
$doc_res = $doc_stmt->get_result();
$doc_row = $doc_res->fetch_assoc();
$doc_stmt->close();

if (!$doc_row) {
    header("Location: doctor_dashboard.php");
    exit;
}
$doctor_id = $doc_row['id'];

$appointment_id = (int)($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);

if ($appointment_id <= 0) {
    $_SESSION['action_err'] = "Invalid appointment ID.";
    header("Location: doctor_dashboard.php");
    exit;
}

// Fetch appointment & patient info for this doctor
$query = "SELECT a.id as appointment_id, a.patient_id, a.doctor_id, a.date, a.time_slot, a.status,
                 pu.name as patient_name, pu.email as patient_email,
                 du.name as doctor_name, d.specialty, d.phone as doctor_phone
          FROM appointments a
          JOIN users pu ON a.patient_id = pu.id
          JOIN doctors d ON a.doctor_id = d.id
          JOIN users du ON d.user_id = du.id
          WHERE a.id = ? AND a.doctor_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$res = $stmt->get_result();
$appointment = $res->fetch_assoc();
$stmt->close();

if (!$appointment) {
    $_SESSION['action_err'] = "Appointment not found or unauthorized.";
    header("Location: doctor_dashboard.php");
    exit;
}

// Check if prescriptions table exists, auto-create if missing
@$conn->query("CREATE TABLE IF NOT EXISTS `prescriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `appointment_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `diagnosis` TEXT NOT NULL,
    `medicines` TEXT NOT NULL,
    `instructions` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_prescriptions_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_prescriptions_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_prescriptions_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Check if prescription already exists for this appointment
$existing_rx = null;
$check_rx = $conn->prepare("SELECT id, diagnosis, medicines, instructions FROM prescriptions WHERE appointment_id = ?");
$check_rx->bind_param("i", $appointment_id);
$check_rx->execute();
$rx_res = $check_rx->get_result();
if ($rx_res && $rx_res->num_rows > 0) {
    $existing_rx = $rx_res->fetch_assoc();
}
$check_rx->close();

$error_msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $medicines = trim($_POST['medicines'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    if (empty($diagnosis)) {
        $error_msg = "Please enter the patient's diagnosis or clinical findings.";
    } elseif (empty($medicines)) {
        $error_msg = "Please prescribe at least one medicine/dosage.";
    } else {
        $patient_id = $appointment['patient_id'];

        if ($existing_rx) {
            // Update existing prescription
            $update_stmt = $conn->prepare("UPDATE prescriptions SET diagnosis = ?, medicines = ?, instructions = ? WHERE appointment_id = ?");
            $update_stmt->bind_param("sssi", $diagnosis, $medicines, $instructions, $appointment_id);
            $success = $update_stmt->execute();
            $rx_id = $existing_rx['id'];
            $update_stmt->close();
        } else {
            // Insert new prescription
            $ins_stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, diagnosis, medicines, instructions) VALUES (?, ?, ?, ?, ?, ?)");
            $ins_stmt->bind_param("iiisss", $appointment_id, $doctor_id, $patient_id, $diagnosis, $medicines, $instructions);
            $success = $ins_stmt->execute();
            $rx_id = $conn->insert_id;
            $ins_stmt->close();
        }

        if ($success) {
            // Automatically mark appointment as completed when prescription is issued
            $status_stmt = $conn->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
            $status_stmt->bind_param("i", $appointment_id);
            $status_stmt->execute();
            $status_stmt->close();

            $_SESSION['action_msg'] = "Prescription successfully issued for Appointment #{$appointment_id}.";
            header("Location: view_prescription.php?id=" . $rx_id);
            exit;
        } else {
            $error_msg = "Failed to save prescription. Please try again.";
        }
    }
}

$page_title = "Issue Prescription - SmartCare";
require_once 'header.php';
?>

<main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 py-10 w-full">

    <!-- Top Navigation Link -->
    <div class="mb-6">
        <a href="doctor_dashboard.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-[#1E3A8A] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Doctor Portal
        </a>
    </div>

    <!-- Page Title Card -->
    <div class="bg-gradient-to-r from-[#1E3A8A] to-[#172e6e] rounded-2xl p-6 text-white mb-8 shadow-md">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="px-3 py-1 bg-blue-500/30 text-blue-100 rounded-full text-xs font-semibold uppercase tracking-wider">
                    Telehealth E-Prescription
                </span>
                <h1 class="text-2xl font-bold mt-2">
                    <?= $existing_rx ? 'Edit E-Prescription' : 'Issue New E-Prescription' ?>
                </h1>
                <p class="text-blue-100 text-sm mt-1">
                    Patient: <strong class="text-white"><?= htmlspecialchars($appointment['patient_name']) ?></strong> | 
                    Date: <?= date('M d, Y', strtotime($appointment['date'])) ?> (<?= htmlspecialchars($appointment['time_slot']) ?>)
                </p>
            </div>
            <div class="bg-white/10 backdrop-blur-md px-4 py-3 rounded-xl border border-white/20 text-xs">
                <div class="text-blue-200 uppercase font-semibold">Appointment ID</div>
                <div class="text-lg font-bold text-white mt-0.5">#<?= $appointment['appointment_id'] ?></div>
            </div>
        </div>
    </div>

    <!-- Error Alert -->
    <?php if (!empty($error_msg)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Prescription Form Card -->
    <form action="add_prescription.php?appointment_id=<?= $appointment_id ?>" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm">
        
        <!-- Header Info Bar -->
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-[#1E3A8A] flex items-center justify-center font-bold text-sm">
                    Rx
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></h3>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($appointment['specialty']) ?> | Phone: <?= htmlspecialchars($appointment['doctor_phone']) ?></p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block">Patient Email</span>
                <span class="text-xs font-semibold text-slate-700"><?= htmlspecialchars($appointment['patient_email']) ?></span>
            </div>
        </div>

        <!-- 1. Diagnosis -->
        <div class="mb-6">
            <label for="diagnosis" class="block text-sm font-bold text-slate-900 mb-2">
                1. Clinical Findings & Diagnosis <span class="text-red-500">*</span>
            </label>
            <textarea id="diagnosis" name="diagnosis" rows="3" required
                placeholder="e.g., Acute Viral Fever with Mild Dehydration. Blood pressure 120/80 mmHg."
                class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm transition-all"><?= htmlspecialchars($_POST['diagnosis'] ?? $existing_rx['diagnosis'] ?? '') ?></textarea>
            <p class="text-xs text-slate-400 mt-1">Summarize chief complaints, physical findings, or medical diagnosis.</p>
        </div>

        <!-- 2. Prescribed Medicines -->
        <div class="mb-6">
            <label for="medicines" class="block text-sm font-bold text-slate-900 mb-2">
                2. Prescribed Medicines & Dosage <span class="text-red-500">*</span>
            </label>
            <textarea id="medicines" name="medicines" rows="5" required
                placeholder="List medicines with dosage and duration. For example:&#10;1. Tab. Napa Extra (500mg) - 1 + 0 + 1 (After meal) for 5 days&#10;2. Cap. Seclo (20mg) - 1 + 0 + 1 (Before meal) for 7 days&#10;3. ORS Saline - 1 packet in 500ml water after each loose motion"
                class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm font-mono transition-all"><?= htmlspecialchars($_POST['medicines'] ?? $existing_rx['medicines'] ?? '') ?></textarea>
            <p class="text-xs text-slate-400 mt-1">Enter prescribed drugs, strength, dosage schedule, and duration.</p>
        </div>

        <!-- 3. Additional Instructions -->
        <div class="mb-8">
            <label for="instructions" class="block text-sm font-bold text-slate-900 mb-2">
                3. Special Advice & Instructions
            </label>
            <textarea id="instructions" name="instructions" rows="3"
                placeholder="e.g., Drink plenty of fluids, rest for 3 days, and follow up if fever persists beyond 48 hours."
                class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm transition-all"><?= htmlspecialchars($_POST['instructions'] ?? $existing_rx['instructions'] ?? '') ?></textarea>
            <p class="text-xs text-slate-400 mt-1">Dietary guidelines, precautions, tests to do, or follow-up schedule.</p>
        </div>

        <!-- Form Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="doctor_dashboard.php" class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-[#1E3A8A] hover:bg-[#172e6e] rounded-xl shadow-md transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                <?= $existing_rx ? 'Update Prescription' : 'Save & Issue Prescription' ?>
            </button>
        </div>

    </form>

</main>

<?php require_once 'footer.php'; ?>
