<?php
/**
 * SmartCare - Enhanced Administrative Control Panel
 * 
 * Accessible only to system admins.
 * Features:
 * 1. Overview Dashboard & Financial Breakdown (BDT ৳ Revenue & Doctor-wise Earnings)
 * 2. Doctor Account Verification System (Approve / Verify pending doctors)
 * 3. Appointment Status Override & Management
 * 4. User Details & History Log View
 * 5. Role Filtering & Real-time Search Toolbar
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

// Ensure columns exist safely
try {
    $conn->query("ALTER TABLE doctors ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 1;");
} catch (Throwable $e) {
    // Column already exists
}

$admin_name = $_SESSION['user_name'];
$action_msg = '';
$action_err = '';

// Handle Admin Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Action 1: Delete User
    if ($_POST['action'] === 'delete_user') {
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);
        
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

    // Action 2: Toggle Doctor Account Verification Status
    if ($_POST['action'] === 'toggle_doctor_verification') {
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);
        $new_status = (int)($_POST['is_verified'] ?? 1);

        if ($target_user_id > 0) {
            $v_stmt = $conn->prepare("UPDATE doctors SET is_verified = ? WHERE user_id = ?");
            $v_stmt->bind_param("ii", $new_status, $target_user_id);
            if ($v_stmt->execute()) {
                $status_label = ($new_status === 1) ? 'Verified/Approved' : 'Pending Verification';
                $action_msg = "Doctor account verification status set to '{$status_label}'.";

                // Notify Doctor
                $notif_text = ($new_status === 1) 
                    ? "Great news! Your doctor account has been approved and verified by the system admin." 
                    : "Your doctor account verification status has been set to pending.";
                $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $n_stmt->bind_param("is", $target_user_id, $notif_text);
                $n_stmt->execute();
                $n_stmt->close();
            } else {
                $action_err = "Failed to update doctor verification status.";
            }
            $v_stmt->close();
        }
    }

    // Action 3: Admin Appointment Status Override
    if ($_POST['action'] === 'override_appointment_status') {
        $appointment_id = (int)($_POST['appointment_id'] ?? 0);
        $new_status = trim($_POST['new_status'] ?? '');
        $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];

        if ($appointment_id > 0 && in_array($new_status, $allowed)) {
            $o_stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $o_stmt->bind_param("si", $new_status, $appointment_id);
            if ($o_stmt->execute()) {
                $action_msg = "Appointment #{$appointment_id} status overridden to '" . ucfirst($new_status) . "'.";
            } else {
                $action_err = "Failed to update appointment status.";
            }
            $o_stmt->close();
        }
    }
}

// 1. Calculate Metric Cards & Revenue Totals
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0;
$total_patients = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetch_row()[0] ?? 0;
$total_doctors = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetch_row()[0] ?? 0;
$pending_doctors = $conn->query("SELECT COUNT(*) FROM doctors WHERE COALESCE(is_verified, 1) = 0")->fetch_row()[0] ?? 0;
$total_appointments = $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0] ?? 0;
$paid_appts_cnt = $conn->query("SELECT COUNT(*) FROM appointments WHERE payment_status = 'paid'")->fetch_row()[0] ?? 0;
$unpaid_appts_cnt = $conn->query("SELECT COUNT(*) FROM appointments WHERE payment_status != 'paid' OR payment_status IS NULL")->fetch_row()[0] ?? 0;

// Total Revenue Calculation (Sum of fees for paid appointments)
$rev_query = "SELECT COALESCE(SUM(d.fee), 0) FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.payment_status = 'paid'";
$total_revenue = (float)($conn->query($rev_query)->fetch_row()[0] ?? 0.00);

// 2. Doctor Earnings & Performance Breakdown
$doc_earnings_sql = "SELECT d.id as doctor_id, d.user_id, u.name as doctor_name, u.email, d.specialty, d.fee, COALESCE(d.is_verified, 1) as is_verified,
                            COUNT(a.id) as total_appts,
                            SUM(CASE WHEN a.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_appts,
                            SUM(CASE WHEN a.payment_status = 'paid' THEN d.fee ELSE 0 END) as total_earned
                     FROM doctors d
                     JOIN users u ON d.user_id = u.id
                     LEFT JOIN appointments a ON d.id = a.doctor_id
                     GROUP BY d.id, d.user_id, u.name, u.email, d.specialty, d.fee, d.is_verified
                     ORDER BY total_earned DESC, u.name ASC";
$doc_earnings_result = $conn->query($doc_earnings_sql);

// 3. Selected User Details & Log (if view_user_id is passed)
$view_user_id = (int)($_GET['view_user_id'] ?? 0);
$user_details = null;
$user_history = [];

if ($view_user_id > 0) {
    $u_stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.role, u.created_at, 
                                    d.id as doctor_id, d.specialty, d.phone, d.fee, d.available_days, COALESCE(d.is_verified, 1) as is_verified
                             FROM users u
                             LEFT JOIN doctors d ON u.id = d.user_id
                             WHERE u.id = ?");
    $u_stmt->bind_param("i", $view_user_id);
    $u_stmt->execute();
    $user_details = $u_stmt->get_result()->fetch_assoc();
    $u_stmt->close();

    if ($user_details) {
        if ($user_details['role'] === 'patient') {
            $h_stmt = $conn->prepare("SELECT a.id as appointment_id, a.date, a.time_slot, a.status, a.payment_status, a.trx_id, a.meeting_link, a.created_at,
                                             du.name as doctor_name, d.specialty, d.fee
                                      FROM appointments a
                                      JOIN doctors d ON a.doctor_id = d.id
                                      JOIN users du ON d.user_id = du.id
                                      WHERE a.patient_id = ?
                                      ORDER BY a.date DESC, a.created_at DESC");
            $h_stmt->bind_param("i", $view_user_id);
            $h_stmt->execute();
            $h_res = $h_stmt->get_result();
            if ($h_res) {
                while ($row = $h_res->fetch_assoc()) {
                    $user_history[] = $row;
                }
            }
            $h_stmt->close();
        } elseif ($user_details['role'] === 'doctor' && !empty($user_details['doctor_id'])) {
            $h_stmt = $conn->prepare("SELECT a.id as appointment_id, a.date, a.time_slot, a.status, a.payment_status, a.trx_id, a.meeting_link, a.created_at,
                                             pu.name as patient_name, pu.email as patient_email, d.fee
                                      FROM appointments a
                                      JOIN users pu ON a.patient_id = pu.id
                                      JOIN doctors d ON a.doctor_id = d.id
                                      WHERE a.doctor_id = ?
                                      ORDER BY a.date DESC, a.created_at DESC");
            $h_stmt->bind_param("i", $user_details['doctor_id']);
            $h_stmt->execute();
            $h_res = $h_stmt->get_result();
            if ($h_res) {
                while ($row = $h_res->fetch_assoc()) {
                    $user_history[] = $row;
                }
            }
            $h_stmt->close();
        }
    }
}

// 4. User Filter & Search Queries
$search_q = trim($_GET['q'] ?? '');
$role_filter = trim($_GET['role_filter'] ?? 'all');

$users_sql = "SELECT u.id, u.name, u.email, u.role, u.created_at,
                     d.specialty, d.phone, d.fee, COALESCE(d.is_verified, 1) as is_verified
              FROM users u
              LEFT JOIN doctors d ON u.id = d.user_id
              WHERE 1=1";

$params = [];
$types = "";

if (!empty($search_q)) {
    $users_sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR d.specialty LIKE ?)";
    $like_q = "%{$search_q}%";
    $params[] = $like_q;
    $params[] = $like_q;
    $params[] = $like_q;
    $types .= "sss";
}

if (!empty($role_filter) && in_array($role_filter, ['patient', 'doctor', 'admin', 'unverified_doctor'])) {
    if ($role_filter === 'unverified_doctor') {
        $users_sql .= " AND u.role = 'doctor' AND COALESCE(d.is_verified, 1) = 0";
    } else {
        $users_sql .= " AND u.role = ?";
        $params[] = $role_filter;
        $types .= "s";
    }
}

$users_sql .= " ORDER BY u.id DESC";

$u_list_stmt = $conn->prepare($users_sql);
if (!empty($types)) {
    $u_list_stmt->bind_param($types, ...$params);
}
$u_list_stmt->execute();
$users_result = $u_list_stmt->get_result();

// 5. System Appointments Overview (Latest 15)
$app_query = "SELECT a.id as appointment_id, a.date, a.time_slot, a.status, a.payment_status, a.trx_id, a.created_at, 
                     a.patient_id, doc_u.id as doctor_user_id, p.name as patient_name, doc_u.name as doctor_name, d.specialty, d.fee 
              FROM appointments a 
              JOIN users p ON a.patient_id = p.id 
              JOIN doctors d ON a.doctor_id = d.id 
              JOIN users doc_u ON d.user_id = doc_u.id 
              ORDER BY a.id DESC LIMIT 15";
$apps_result = $conn->query($app_query);

$page_title = "Admin Control Panel";
require_once 'header.php';
?>

<!-- Custom Modal Animation Styles -->
<style>
@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes modalScaleUp {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-modal-fade {
    animation: modalFadeIn 0.2s ease-out forwards;
}
.animate-modal-scale {
    animation: modalScaleUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">

    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-[#1E3A8A] text-xs font-semibold uppercase tracking-wider mb-2">
                Administrative Control Panel
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">SmartCare Admin Center</h1>
            <p class="text-slate-500 text-sm mt-0.5">Doctor account verification, appointment status override, and financial metrics.</p>
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

    <!-- 5 Key Platform Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-10">
        
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Users</div>
            <div class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($total_users) ?></div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Registered Doctors</div>
            <div class="text-2xl font-bold text-emerald-900 mt-1 flex items-center justify-between">
                <span><?= number_format($total_doctors) ?></span>
                <?php if ($pending_doctors > 0): ?>
                    <span class="text-[10px] font-extrabold px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full" title="Doctors pending admin approval">
                        <?= $pending_doctors ?> Pending
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Registered Patients</div>
            <div class="text-2xl font-bold text-blue-900 mt-1"><?= number_format($total_patients) ?></div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Appointments</div>
            <div class="text-2xl font-bold text-indigo-900 mt-1"><?= number_format($total_appointments) ?></div>
        </div>

        <!-- Total Revenue (BDT ৳) -->
        <div class="bg-gradient-to-br from-[#1E3A8A] to-[#172e6e] rounded-2xl p-5 shadow-sm text-white">
            <div class="text-[11px] font-semibold text-blue-200 uppercase tracking-wider">Paid Revenue</div>
            <div class="text-2xl font-black mt-1">৳<?= number_format($total_revenue, 2) ?></div>
        </div>

    </div>

    <!-- Section 1: Financial & Commission Breakdown -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-10 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Financial Breakdown & Doctor Revenue Log</h2>
                <p class="text-xs text-slate-500 mt-0.5">Track paid consultations and revenue generated per doctor in BDT (৳).</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="px-3 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl font-semibold">
                    Paid Appts: <strong><?= $paid_appts_cnt ?></strong>
                </span>
                <span class="px-3 py-1.5 bg-amber-50 text-amber-800 border border-amber-200 rounded-xl font-semibold">
                    Unpaid Appts: <strong><?= $unpaid_appts_cnt ?></strong>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500 tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Doctor</th>
                        <th class="px-6 py-3.5">Specialty</th>
                        <th class="px-6 py-3.5">Fee Rate</th>
                        <th class="px-6 py-3.5">Total Bookings</th>
                        <th class="px-6 py-3.5">Paid Consultations</th>
                        <th class="px-6 py-3.5">Total Revenue Generated</th>
                        <th class="px-6 py-3.5 text-right">Verification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if ($doc_earnings_result && $doc_earnings_result->num_rows > 0): ?>
                        <?php while ($d_earn = $doc_earnings_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer" onclick="if(!event.target.closest('button, a, select, input')) window.location.href='admin_panel.php?view_user_id=<?= $d_earn['user_id'] ?>';">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <a href="admin_panel.php?view_user_id=<?= $d_earn['user_id'] ?>" class="hover:text-[#1E3A8A] hover:underline">
                                        Dr. <?= htmlspecialchars($d_earn['doctor_name']) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                                    <?= htmlspecialchars($d_earn['specialty']) ?>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-slate-900 whitespace-nowrap">
                                    ৳<?= number_format((float)$d_earn['fee'], 2) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-700 font-semibold">
                                    <?= $d_earn['total_appts'] ?>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-emerald-700">
                                    <?= $d_earn['paid_appts'] ?> paid
                                </td>
                                <td class="px-6 py-4 font-extrabold text-[#1E3A8A] whitespace-nowrap">
                                    ৳<?= number_format((float)$d_earn['total_earned'], 2) ?>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <?php if ((int)$d_earn['is_verified'] === 1): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Verified
                                        </span>
                                    <?php else: ?>
                                        <form action="admin_panel.php" method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_doctor_verification">
                                            <input type="hidden" name="target_user_id" value="<?= $d_earn['user_id'] ?>">
                                            <input type="hidden" name="is_verified" value="1">
                                            <button type="submit" class="px-3 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-xs font-bold transition-all shadow-sm">
                                                Approve Now
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="p-6 text-center text-xs text-slate-400">No doctors registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: User Detail Centered Modal Popup -->
    <?php if ($user_details): ?>
        <div id="userModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-sm animate-modal-fade" onclick="if(event.target === this) closeUserModal();">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-modal-scale" onclick="event.stopPropagation();">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 sm:px-8 sm:py-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/90 sticky top-0 z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-blue-100 text-[#1E3A8A] flex items-center justify-center font-extrabold text-lg border border-blue-200 shadow-sm shrink-0">
                            <?= strtoupper(substr($user_details['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($user_details['name']) ?></h2>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase <?= $user_details['role'] === 'doctor' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($user_details['role'] === 'patient' ? 'bg-blue-50 text-[#1E3A8A] border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200') ?>">
                                    <?= htmlspecialchars($user_details['role']) ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Email: <?= htmlspecialchars($user_details['email']) ?> | Registered: <?= date('M d, Y', strtotime($user_details['created_at'])) ?></p>
                        </div>
                    </div>
                    
                    <button type="button" onclick="closeUserModal(event)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-200/70 hover:bg-slate-300 text-slate-600 hover:text-slate-900 font-bold transition-colors" title="Close Modal (Esc)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6">

                    <!-- Doctor Profile & Verification Cards if Doctor -->
                    <?php if ($user_details['role'] === 'doctor'): ?>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 block uppercase font-semibold text-[10px]">Specialty</span>
                                <span class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($user_details['specialty'] ?: 'N/A') ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block uppercase font-semibold text-[10px]">Consultation Fee</span>
                                <span class="font-bold text-[#1E3A8A] text-sm">৳<?= number_format((float)($user_details['fee'] ?? 0), 2) ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block uppercase font-semibold text-[10px]">Verification Status</span>
                                <span class="font-bold text-sm <?= (int)$user_details['is_verified'] === 1 ? 'text-emerald-700' : 'text-amber-700' ?>">
                                    <?= (int)$user_details['is_verified'] === 1 ? '✓ Verified / Active' : '⏳ Pending Approval' ?>
                                </span>
                            </div>
                            <div class="flex items-center">
                                <form action="admin_panel.php?view_user_id=<?= $user_details['id'] ?>" method="POST" class="w-full">
                                    <input type="hidden" name="action" value="toggle_doctor_verification">
                                    <input type="hidden" name="target_user_id" value="<?= $user_details['id'] ?>">
                                    <input type="hidden" name="is_verified" value="<?= (int)$user_details['is_verified'] === 1 ? '0' : '1' ?>">
                                    <button type="submit" class="w-full py-2 px-3 text-xs font-bold rounded-xl border transition-colors shadow-sm <?= (int)$user_details['is_verified'] === 1 ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' : 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700' ?>">
                                        <?= (int)$user_details['is_verified'] === 1 ? 'Revoke Verification' : 'Approve & Verify Doctor' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Complete History Log Table -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                <?= $user_details['role'] === 'doctor' ? 'Doctor Appointment Log' : 'Patient Booking History' ?>
                            </h3>
                            <span class="text-xs text-slate-500 font-semibold">Total Records: <?= count($user_history) ?></span>
                        </div>

                        <?php if (!empty($user_history)): ?>
                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="w-full text-left text-xs text-slate-700">
                                    <thead class="bg-slate-50 border-b border-slate-200 uppercase font-semibold text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3">Appt ID</th>
                                            <th class="px-4 py-3"><?= $user_details['role'] === 'doctor' ? 'Patient' : 'Doctor' ?></th>
                                            <th class="px-4 py-3">Date & Slot</th>
                                            <th class="px-4 py-3">Fee</th>
                                            <th class="px-4 py-3">Payment</th>
                                            <th class="px-4 py-3">Status</th>
                                            <th class="px-4 py-3 text-right">Admin Override</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        <?php foreach ($user_history as $h): ?>
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3 font-bold text-slate-400">#<?= $h['appointment_id'] ?></td>
                                                <td class="px-4 py-3 font-semibold text-slate-900">
                                                    <?= htmlspecialchars($user_details['role'] === 'doctor' ? $h['patient_name'] : 'Dr. ' . $h['doctor_name']) ?>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <?= date('M d, Y', strtotime($h['date'])) ?> (<?= htmlspecialchars($h['time_slot']) ?>)
                                                </td>
                                                <td class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">
                                                    ৳<?= number_format((float)$h['fee'], 2) ?>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <?php if (($h['payment_status'] ?? '') === 'paid'): ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            Paid <?= !empty($h['trx_id']) ? '(' . htmlspecialchars($h['trx_id']) . ')' : '' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                            Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $h['status'] === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($h['status'] === 'confirmed' ? 'bg-blue-50 text-blue-700' : ($h['status'] === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700')) ?>">
                                                        <?= htmlspecialchars($h['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                    <form action="admin_panel.php?view_user_id=<?= $view_user_id ?>" method="POST" class="inline-flex items-center gap-1">
                                                        <input type="hidden" name="action" value="override_appointment_status">
                                                        <input type="hidden" name="appointment_id" value="<?= $h['appointment_id'] ?>">
                                                        <select name="new_status" onchange="this.form.submit()" class="px-2 py-1 text-[11px] bg-slate-100 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
                                                            <option value="" disabled selected>Override Status...</option>
                                                            <option value="pending">Set Pending</option>
                                                            <option value="confirmed">Set Confirmed</option>
                                                            <option value="completed">Set Completed</option>
                                                            <option value="cancelled">Set Cancelled</option>
                                                        </select>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-6 bg-slate-50 rounded-xl text-center text-xs text-slate-500 border border-slate-200">
                                No appointment record history found for this user.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <span class="text-xs text-slate-400 font-medium">User ID: #<?= $user_details['id'] ?></span>
                    <button type="button" onclick="closeUserModal(event)" class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-xl transition-colors">
                        Close Window
                    </button>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <!-- Section 3: User Management Table with Filter Toolbar & Verification -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm mb-10 overflow-hidden">
        
        <!-- Filter Toolbar -->
        <div class="px-6 py-5 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">User Management & Doctor Verification</h2>
                <p class="text-xs text-slate-500 mt-0.5">Filter by role, verify doctor accounts, or search patient/doctor records.</p>
            </div>

            <form action="admin_panel.php" method="GET" class="flex flex-wrap items-center gap-3">
                <select name="role_filter" onchange="this.form.submit()" class="px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
                    <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>All Roles</option>
                    <option value="patient" <?= $role_filter === 'patient' ? 'selected' : '' ?>>Patients Only</option>
                    <option value="doctor" <?= $role_filter === 'doctor' ? 'selected' : '' ?>>Doctors Only</option>
                    <option value="unverified_doctor" <?= $role_filter === 'unverified_doctor' ? 'selected' : '' ?>>Pending Verification Doctors</option>
                    <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins Only</option>
                </select>

                <div class="relative">
                    <input type="text" name="q" value="<?= htmlspecialchars($search_q) ?>" placeholder="Search name or email..."
                        class="pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] w-48 sm:w-60">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <button type="submit" class="px-4 py-2 bg-[#1E3A8A] text-white text-xs font-bold rounded-xl hover:bg-[#172e6e] transition-colors">
                    Search
                </button>

                <?php if (!empty($search_q) || $role_filter !== 'all'): ?>
                    <a href="admin_panel.php" class="px-3 py-2 bg-slate-100 text-slate-600 hover:text-slate-900 text-xs font-semibold rounded-xl">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500 tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">ID</th>
                        <th class="px-6 py-3.5">User Name</th>
                        <th class="px-6 py-3.5">Email Address</th>
                        <th class="px-6 py-3.5">Role</th>
                        <th class="px-6 py-3.5">Verification Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                        <?php while ($u = $users_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer" onclick="if(!event.target.closest('button, a, select, input')) window.location.href='admin_panel.php?view_user_id=<?= $u['id'] ?>';">
                                <td class="px-6 py-4 font-bold text-slate-400 text-xs">#<?= $u['id'] ?></td>
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <a href="admin_panel.php?view_user_id=<?= $u['id'] ?>" class="hover:text-[#1E3A8A] hover:underline">
                                        <?= htmlspecialchars($u['name']) ?>
                                    </a>
                                </td>
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
                                <td class="px-6 py-4 text-xs whitespace-nowrap">
                                    <?php if ($u['role'] === 'doctor'): ?>
                                        <?php if ((int)$u['is_verified'] === 1): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                Pending Approval
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">Active User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Doctor Verification Action Toggle -->
                                        <?php if ($u['role'] === 'doctor'): ?>
                                            <form action="admin_panel.php" method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_doctor_verification">
                                                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="is_verified" value="<?= (int)$u['is_verified'] === 1 ? '0' : '1' ?>">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors <?= (int)$u['is_verified'] === 1 ? 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200' : 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700 shadow-sm' ?>">
                                                    <?= (int)$u['is_verified'] === 1 ? 'Revoke' : 'Approve' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="admin_panel.php?view_user_id=<?= $u['id'] ?>" class="px-3 py-1.5 text-xs font-bold text-[#1E3A8A] bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors">
                                            View Log
                                        </a>

                                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                            <form action="admin_panel.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 border border-red-200 rounded-lg transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                                No user accounts matching the search filter criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 4: Platform Appointments Override & Management -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Platform Appointments & Status Override</h2>
            <p class="text-xs text-slate-500 mt-0.5">Overview of latest appointments with direct admin status override options.</p>
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
                            <th class="px-6 py-3.5">Payment</th>
                            <th class="px-6 py-3.5">Current Status</th>
                            <th class="px-6 py-3.5 text-right">Admin Override</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php while ($app = $apps_result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-400 text-xs">#<?= $app['appointment_id'] ?></td>
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <a href="admin_panel.php?view_user_id=<?= $app['patient_id'] ?>" class="hover:text-[#1E3A8A] hover:underline">
                                        <?= htmlspecialchars($app['patient_name']) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">
                                        <a href="admin_panel.php?view_user_id=<?= $app['doctor_user_id'] ?>" class="hover:text-[#1E3A8A] hover:underline">
                                            Dr. <?= htmlspecialchars($app['doctor_name']) ?>
                                        </a>
                                    </div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($app['specialty']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 whitespace-nowrap">
                                    <div class="font-medium text-slate-800"><?= date('M d, Y', strtotime($app['date'])) ?></div>
                                    <div><?= htmlspecialchars($app['time_slot']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (($app['payment_status'] ?? '') === 'paid'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Paid <?= !empty($app['trx_id']) ? '(' . htmlspecialchars($app['trx_id']) . ')' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Pending
                                        </span>
                                    <?php endif; ?>
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
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <form action="admin_panel.php" method="POST" class="inline-flex items-center gap-1">
                                        <input type="hidden" name="action" value="override_appointment_status">
                                        <input type="hidden" name="appointment_id" value="<?= $app['appointment_id'] ?>">
                                        <select name="new_status" onchange="this.form.submit()" class="px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] font-semibold text-slate-700">
                                            <option value="" disabled selected>Override Status...</option>
                                            <option value="pending">Set Pending</option>
                                            <option value="confirmed">Set Confirmed</option>
                                            <option value="completed">Set Completed</option>
                                            <option value="cancelled">Set Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500 text-sm">
                                No appointments registered in system yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
function closeUserModal(e) {
    if (e) e.preventDefault();
    const modal = document.getElementById('userModal');
    if (modal) {
        modal.style.opacity = '0';
        modal.style.transition = 'opacity 0.15s ease-out';
        setTimeout(() => {
            modal.remove();
        }, 150);
    }
    // Update URL parameter without triggering full page reload or scroll jump
    const url = new URL(window.location.href);
    url.searchParams.delete('view_user_id');
    window.history.replaceState({}, document.title, url.toString());
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUserModal();
    }
});
</script>

<?php require_once 'footer.php'; ?>
