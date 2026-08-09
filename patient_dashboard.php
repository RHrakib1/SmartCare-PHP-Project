<?php
/**
 * SmartCare - Patient Dashboard
 * 
 * Accessible only to logged-in patients.
 * Displays booked appointments and allows cancelling pending bookings.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Access Control: Must be logged in as Patient
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_role'] !== 'patient') {
    // If logged in as Doctor or Admin, redirect to their respective dashboard
    if ($_SESSION['user_role'] === 'doctor') {
        header("Location: doctor_dashboard.php");
    } elseif ($_SESSION['user_role'] === 'admin') {
        header("Location: admin_panel.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$patient_id = $_SESSION['user_id'];
$patient_name = $_SESSION['user_name'];
$patient_email = $_SESSION['user_email'] ?? '';

$action_message = '';
$action_error = '';

// Handle Cancel Appointment Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_appointment') {
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);

    if ($appointment_id > 0) {
        $cancel_stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND patient_id = ? AND status = 'pending'");
        $cancel_stmt->bind_param("ii", $appointment_id, $patient_id);
        
        if ($cancel_stmt->execute() && $cancel_stmt->affected_rows > 0) {
            $action_message = "Appointment #{$appointment_id} has been cancelled successfully.";
        } else {
            $action_error = "Unable to cancel appointment. Only pending appointments can be cancelled.";
        }
        $cancel_stmt->close();
    }
}

// Fetch all appointments for current patient
$query = "SELECT a.id as appointment_id, a.date, a.time_slot, a.status, a.created_at, 
                 u.name as doctor_name, d.specialty, d.fee, d.phone as doctor_phone
          FROM appointments a 
          JOIN doctors d ON a.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          WHERE a.patient_id = ? 
          ORDER BY a.date DESC, a.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments_result = $stmt->get_result();

// Count stats
$total_count = 0;
$pending_count = 0;
$confirmed_count = 0;

$appointments = [];
if ($appointments_result) {
    while ($row = $appointments_result->fetch_assoc()) {
        $appointments[] = $row;
        $total_count++;
        if ($row['status'] === 'pending') $pending_count++;
        if ($row['status'] === 'confirmed') $confirmed_count++;
    }
}

// Set Page Title and Include Header AFTER all session checks & redirects
$page_title = "Patient Dashboard";
require_once 'header.php';
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-[#1E3A8A] text-xs font-semibold uppercase tracking-wider mb-2">
                Patient Portal
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">My Appointments</h1>
            <p class="text-slate-500 text-sm mt-0.5">Welcome back, <?= htmlspecialchars($patient_name) ?>. Manage your upcoming consultations.</p>
        </div>
        <div>
            <a href="doctors.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1E3A8A] hover:bg-[#172e6e] text-white text-sm font-medium rounded-xl shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Book New Appointment
            </a>
        </div>
    </div>

    <!-- Feedback Messages -->
    <?php if (!empty($action_message)): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span><?= htmlspecialchars($action_message) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($action_error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= htmlspecialchars($action_error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Bookings</div>
            <div class="text-3xl font-bold text-slate-900 mt-2"><?= $total_count ?></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Review</div>
            <div class="text-3xl font-bold text-amber-600 mt-2"><?= $pending_count ?></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Confirmed Slots</div>
            <div class="text-3xl font-bold text-emerald-600 mt-2"><?= $confirmed_count ?></div>
        </div>
    </div>

    <!-- Structured Appointments Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Appointment History</h2>
            <span class="text-xs text-slate-400">Showing <?= count($appointments) ?> record(s)</span>
        </div>

        <?php if (!empty($appointments)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Doctor</th>
                            <th class="px-6 py-3.5">Specialty</th>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5">Time Slot</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($appointments as $app): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Doctor Name & Phone -->
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-50 text-[#1E3A8A] flex items-center justify-center font-bold text-xs">
                                            Dr
                                        </div>
                                        <div>
                                            <div><?= htmlspecialchars($app['doctor_name']) ?></div>
                                            <div class="text-xs font-normal text-slate-400"><?= htmlspecialchars($app['doctor_phone']) ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Specialty -->
                                <td class="px-6 py-4 text-slate-600">
                                    <span class="inline-block px-2.5 py-1 bg-slate-100 text-slate-700 rounded-md text-xs font-medium">
                                        <?= htmlspecialchars($app['specialty']) ?>
                                    </span>
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
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Confirmed
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            Cancelled
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Action (Cancel) -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <form action="patient_dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this pending appointment?');" class="inline">
                                            <input type="hidden" name="action" value="cancel_appointment">
                                            <input type="hidden" name="appointment_id" value="<?= $app['appointment_id'] ?>">
                                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">No action</span>
                                    <?php endif; ?>
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
                <h3 class="text-sm font-semibold text-slate-800">No appointments booked yet</h3>
                <p class="text-xs text-slate-500 mt-1">Book your first doctor consultation to see your schedule here.</p>
                <div class="mt-4">
                    <a href="doctors.php" class="px-4 py-2 bg-[#1E3A8A] text-white text-xs font-medium rounded-xl shadow-sm">
                        Find a Doctor Now
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</main>

<?php require_once 'footer.php'; ?>
