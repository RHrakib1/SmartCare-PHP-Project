<?php
/**
 * SmartCare - Doctor Dashboard
 * 
 * Accessible only to logged-in doctors.
 * Allows doctors to manage patient bookings and update appointment status.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Strict Role-Based Access Check (BEFORE any HTML output or header.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_role'] !== 'doctor') {
    if ($_SESSION['user_role'] === 'patient') {
        header("Location: patient_dashboard.php");
    } elseif ($_SESSION['user_role'] === 'admin') {
        header("Location: admin_panel.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$user_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'];

// Fetch Doctor Record from doctors table
$doc_stmt = $conn->prepare("SELECT id, specialty, phone, fee, available_days FROM doctors WHERE user_id = ?");
$doc_stmt->bind_param("i", $user_id);
$doc_stmt->execute();
$doc_result = $doc_stmt->get_result();

if ($doc_result->num_rows === 0) {
    // Auto-create doctor row if missing
    $ins_doc = $conn->prepare("INSERT INTO doctors (user_id, specialty, phone, fee, available_days) VALUES (?, 'General Medicine', 'Not specified', 50.00, 'Monday - Friday')");
    $ins_doc->bind_param("i", $user_id);
    $ins_doc->execute();
    $doctor_id = $conn->insert_id;
    $specialty = 'General Medicine';
    $fee = 50.00;
} else {
    $doc_info = $doc_result->fetch_assoc();
    $doctor_id = $doc_info['id'];
    $specialty = $doc_info['specialty'];
    $fee = $doc_info['fee'];
}
$doc_stmt->close();

$action_msg = '';
$action_err = '';

// Handle Update Appointment Status Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $meeting_link = trim($_POST['meeting_link'] ?? '');

    $allowed_statuses = ['confirmed', 'completed', 'cancelled'];

    if ($appointment_id > 0 && in_array($new_status, $allowed_statuses)) {
        // Backend Validation: Meeting link is mandatory when confirming appointment
        if ($new_status === 'confirmed' && empty($meeting_link)) {
            $action_err = "Meeting link is required to confirm appointment.";
        } else {
            if (!empty($meeting_link) && !preg_match("~^(?:f|ht)tps?://~i", $meeting_link)) {
                $meeting_link = "https://" . $meeting_link;
            }
            $meeting_link_val = !empty($meeting_link) ? $meeting_link : null;

            if ($new_status === 'confirmed' || isset($_POST['meeting_link'])) {
                $update_stmt = $conn->prepare("UPDATE appointments SET status = ?, meeting_link = ? WHERE id = ? AND doctor_id = ?");
                $update_stmt->bind_param("ssii", $new_status, $meeting_link_val, $appointment_id, $doctor_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
                $update_stmt->bind_param("sii", $new_status, $appointment_id, $doctor_id);
            }
            
            if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                $action_msg = "Appointment #{$appointment_id} has been " . ($new_status === 'confirmed' ? 'confirmed with meeting link' : ucfirst($new_status)) . ".";

                // Send In-App Notification to Patient if confirmed
                if ($new_status === 'confirmed') {
                    $appt_info_stmt = $conn->prepare("SELECT patient_id FROM appointments WHERE id = ?");
                    $appt_info_stmt->bind_param("i", $appointment_id);
                    $appt_info_stmt->execute();
                    $appt_info_res = $appt_info_stmt->get_result()->fetch_assoc();
                    $appt_info_stmt->close();

                    if ($appt_info_res) {
                        $patient_user_id = $appt_info_res['patient_id'];
                        $notif_msg = "Your appointment (#{$appointment_id}) with Dr. {$doctor_name} has been confirmed. Meeting link has been added.";

                        @$conn->query("CREATE TABLE IF NOT EXISTS `notifications` (`id` INT AUTO_INCREMENT PRIMARY KEY, `user_id` INT NOT NULL, `message` TEXT NOT NULL, `is_read` TINYINT(1) NOT NULL DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                        $notif_stmt->bind_param("is", $patient_user_id, $notif_msg);
                        $notif_stmt->execute();
                        $notif_stmt->close();
                    }
                }
            } else {
                $action_err = "Unable to update appointment status.";
            }
            $update_stmt->close();
        }
    }
}

// Fetch assigned appointments for this doctor (Only Pending/Confirmed AND Paid)
$query = "SELECT a.id as appointment_id, a.date, a.time_slot, a.status, a.payment_status, a.trx_id, a.meeting_link, a.created_at, 
                 u.name as patient_name, u.email as patient_email,
                 pr.id as prescription_id
          FROM appointments a 
          JOIN users u ON a.patient_id = u.id 
          LEFT JOIN prescriptions pr ON a.id = pr.appointment_id
          WHERE a.doctor_id = ? 
            AND a.status IN ('pending', 'confirmed')
            AND a.payment_status = 'paid'
          ORDER BY a.date ASC, a.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments_result = $stmt->get_result();

$appointments = [];
$total_count = 0;
$pending_count = 0;
$confirmed_count = 0;
$completed_count = 0;

if ($appointments_result) {
    while ($row = $appointments_result->fetch_assoc()) {
        $appointments[] = $row;
        $total_count++;
        if ($row['status'] === 'pending') $pending_count++;
        if ($row['status'] === 'confirmed') $confirmed_count++;
        if ($row['status'] === 'completed') $completed_count++;
    }
}

// Set Page Title and Include Header AFTER all session checks & redirects
$page_title = "Doctor Dashboard";
require_once 'header.php';
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-[#1E3A8A] text-xs font-semibold uppercase tracking-wider mb-2">
                Doctor Workspace
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Doctor Portal</h1>
            <p class="text-slate-500 text-sm mt-0.5">Welcome, Dr. <?= htmlspecialchars($doctor_name) ?> (<?= htmlspecialchars($specialty) ?>)</p>
        </div>
        <div>
            <a href="edit_doctor_profile.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#1E3A8A] hover:bg-[#172e6e] text-white text-xs font-semibold rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Profile
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($action_msg)): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span><?= htmlspecialchars($action_msg) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($action_err)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= htmlspecialchars($action_err) ?></span>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-8">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Patients</div>
            <div class="text-3xl font-bold text-slate-900 mt-2"><?= $total_count ?></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Review</div>
            <div class="text-3xl font-bold text-amber-600 mt-2"><?= $pending_count ?></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Confirmed</div>
            <div class="text-3xl font-bold text-blue-600 mt-2"><?= $confirmed_count ?></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Completed</div>
            <div class="text-3xl font-bold text-emerald-600 mt-2"><?= $completed_count ?></div>
        </div>
    </div>

    <!-- Assigned Appointments Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Patient Appointments Schedule</h2>
            <span class="text-xs text-slate-400">Total assigned: <?= count($appointments) ?></span>
        </div>

        <?php if (!empty($appointments)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Patient Info</th>
                            <th class="px-6 py-3.5">Appointment Date</th>
                            <th class="px-6 py-3.5">Time Slot</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Update Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($appointments as $app): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Patient Info -->
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                                            <?= strtoupper(substr($app['patient_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div><?= htmlspecialchars($app['patient_name']) ?></div>
                                            <div class="text-xs font-normal text-slate-400"><?= htmlspecialchars($app['patient_email']) ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">
                                    <?= date('M d, Y', strtotime($app['date'])) ?>
                                </td>

                                <!-- Time Slot -->
                                <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                    <?= htmlspecialchars($app['time_slot']) ?>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Pending
                                        </span>
                                    <?php elseif ($app['status'] === 'confirmed'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            Confirmed
                                        </span>
                                    <?php elseif ($app['status'] === 'completed'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Completed
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            Cancelled
                                        </span>
                                    <?php endif; ?>
                                </td>

                                 <!-- Actions -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($app['status'] === 'pending'): ?>
                                             <!-- Confirm Form with Mandatory Telehealth Link Input -->
                                             <form action="doctor_dashboard.php" method="POST" class="inline-flex items-center gap-2">
                                                 <input type="hidden" name="action" value="update_status">
                                                 <input type="hidden" name="appointment_id" value="<?= $app['appointment_id'] ?>">
                                                 <input type="hidden" name="new_status" value="confirmed">
                                                 <input type="url" name="meeting_link" placeholder="Google Meet or Zoom URL *" required class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 w-48 sm:w-60" title="Meeting link is required to confirm appointment">
                                                 <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-[#1E3A8A] hover:bg-[#172e6e] rounded-lg transition-colors shadow-sm whitespace-nowrap">
                                                     Confirm
                                                 </button>
                                             </form>
                                            <!-- Cancel Button -->
                                            <form action="doctor_dashboard.php" method="POST" onsubmit="return confirm('Decline/cancel this appointment?');" class="inline">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?= $app['appointment_id'] ?>">
                                                <input type="hidden" name="new_status" value="cancelled">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-red-200">
                                                    Decline
                                                </button>
                                            </form>
                                         <?php elseif ($app['status'] === 'confirmed'): ?>
                                             <?php if (!empty($app['meeting_link'])): ?>
                                                 <a href="<?= htmlspecialchars($app['meeting_link']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition-colors">
                                                     <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                     </svg>
                                                     Call Link
                                                 </a>
                                             <?php endif; ?>
                                             
                                             <!-- Write Rx Button -->
                                             <a href="add_prescription.php?appointment_id=<?= $app['appointment_id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors shadow-sm">
                                                 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                 </svg>
                                                 <?= !empty($app['prescription_id']) ? 'Edit Rx' : 'Write Rx' ?>
                                             </a>
                                         <?php elseif ($app['status'] === 'completed'): ?>
                                             <?php if (!empty($app['prescription_id'])): ?>
                                                 <a href="view_prescription.php?id=<?= $app['prescription_id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-colors">
                                                     <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                     </svg>
                                                     View Rx
                                                 </a>
                                             <?php else: ?>
                                                 <a href="add_prescription.php?appointment_id=<?= $app['appointment_id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors shadow-sm">
                                                     Write Rx
                                                 </a>
                                             <?php endif; ?>
                                         <?php else: ?>
                                             <span class="text-xs text-slate-400 italic">No action</span>
                                         <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No patient bookings assigned yet</h3>
                <p class="text-xs text-slate-500 mt-1">Appointments booked by patients for your specialty will appear here.</p>
            </div>
        <?php endif; ?>
    </div>

</main>

<?php require_once 'footer.php'; ?>
