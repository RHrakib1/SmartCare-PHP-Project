<?php
/**
 * SmartCare - Admin Panel
 * 
 * Accessible only to admin users.
 * Displays overall platform metrics, user management, and system appointments.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Strict Role-Based Access Check for Admin (BEFORE any HTML output or header.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    if ($_SESSION['user_role'] === 'patient') {
        header("Location: patient_dashboard.php");
    } elseif ($_SESSION['user_role'] === 'doctor') {
        header("Location: doctor_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$admin_name = $_SESSION['user_name'];
$action_msg = '';
$action_err = '';

// Handle Delete User Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);
    
    // Prevent deleting self
    if ($target_user_id === (int)$_SESSION['user_id']) {
        $action_err = "You cannot delete your own active admin account.";
    } elseif ($target_user_id > 0) {
        $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del_stmt->bind_param("i", $target_user_id);
        if ($del_stmt->execute()) {
            $action_msg = "User account #{$target_user_id} deleted successfully.";
        } else {
            $action_err = "Failed to delete user account.";
        }
        $del_stmt->close();
    }
}

// 1. Calculate Metric Cards
$total_patients = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetch_row()[0] ?? 0;
$total_doctors = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetch_row()[0] ?? 0;
$total_appointments = $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0] ?? 0;
$pending_appointments = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetch_row()[0] ?? 0;

// 2. Fetch User Management List
$users_query = "SELECT id, name, email, role, created_at FROM users ORDER BY id DESC";
$users_result = $conn->query($users_query);

// 3. Fetch System Appointments Overview
$app_query = "SELECT a.id as appointment_id, a.date, a.time_slot, a.status, a.created_at, 
                     p.name as patient_name, doc_u.name as doctor_name, d.specialty 
              FROM appointments a 
              JOIN users p ON a.patient_id = p.id 
              JOIN doctors d ON a.doctor_id = d.id 
              JOIN users doc_u ON d.user_id = doc_u.id 
              ORDER BY a.id DESC LIMIT 10";
$apps_result = $conn->query($app_query);

// Set Page Title and Include Header AFTER all session checks & redirects
$page_title = "Admin Control Panel";
require_once 'header.php';
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">

    <!-- Top Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-[#1E3A8A] text-xs font-semibold uppercase tracking-wider mb-2">
                Administrative Control
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">System Admin Panel</h1>
            <p class="text-slate-500 text-sm mt-0.5">Overview of platform metrics, user management, and appointment bookings.</p>
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

    <!-- 4 Key Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-10">
        <!-- Patients Metric -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Patients</div>
                <div class="text-3xl font-bold text-slate-900 mt-2"><?= number_format($total_patients) ?></div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#1E3A8A] border border-blue-100 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        </div>

        <!-- Doctors Metric -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Doctors</div>
                <div class="text-3xl font-bold text-slate-900 mt-2"><?= number_format($total_doctors) ?></div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- Appointments Metric -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Appointments</div>
                <div class="text-3xl font-bold text-slate-900 mt-2"><?= number_format($total_appointments) ?></div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        <!-- Pending Review Metric -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Bookings</div>
                <div class="text-3xl font-bold text-amber-600 mt-2"><?= number_format($pending_appointments) ?></div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 border border-amber-100 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Section 1: User Management Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-10 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">User Management</h2>
                <p class="text-xs text-slate-500 mt-0.5">List of all registered patients, doctors, and administrators.</p>
            </div>
            <span class="text-xs font-semibold text-[#1E3A8A] bg-blue-50 px-3 py-1 rounded-full border border-blue-100">
                <?= $users_result->num_rows ?> Registered Users
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500 tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">ID</th>
                        <th class="px-6 py-3.5">User Name</th>
                        <th class="px-6 py-3.5">Email Address</th>
                        <th class="px-6 py-3.5">Role</th>
                        <th class="px-6 py-3.5">Registered On</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php while ($u = $users_result->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-400 text-xs">#<?= $u['id'] ?></td>
                            <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($u['name']) ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="px-6 py-4">
                                <?php if ($u['role'] === 'patient'): ?>
                                    <span class="inline-block px-2.5 py-0.5 bg-blue-50 text-[#1E3A8A] border border-blue-100 rounded-md text-xs font-semibold">Patient</span>
                                <?php elseif ($u['role'] === 'doctor'): ?>
                                    <span class="inline-block px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md text-xs font-semibold">Doctor</span>
                                <?php else: ?>
                                    <span class="inline-block px-2.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-md text-xs font-semibold">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                                <?= date('M d, Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <form action="admin_panel.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 border border-red-200 rounded-lg transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">Current Session</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Recent Appointments Overview -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Recent Platform Appointments</h2>
            <p class="text-xs text-slate-500 mt-0.5">Overview of latest appointment bookings across all specialties.</p>
        </div>

        <?php if ($apps_result && $apps_result->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">ID</th>
                            <th class="px-6 py-3.5">Patient</th>
                            <th class="px-6 py-3.5">Doctor & Specialty</th>
                            <th class="px-6 py-3.5">Schedule</th>
                            <th class="px-6 py-3.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php while ($app = $apps_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-400 text-xs">#<?= $app['appointment_id'] ?></td>
                                <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($app['patient_name']) ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">Dr. <?= htmlspecialchars($app['doctor_name']) ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($app['specialty']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 whitespace-nowrap">
                                    <div class="font-medium text-slate-800"><?= date('M d, Y', strtotime($app['date'])) ?></div>
                                    <div><?= htmlspecialchars($app['time_slot']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Pending</span>
                                    <?php elseif ($app['status'] === 'confirmed'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Confirmed</span>
                                    <?php elseif ($app['status'] === 'completed'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-8 text-center text-slate-500 text-sm">
                No appointments registered in system yet.
            </div>
        <?php endif; ?>
    </div>

</main>

<?php require_once 'footer.php'; ?>
