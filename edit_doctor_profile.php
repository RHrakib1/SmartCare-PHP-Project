<?php
/**
 * SmartCare - Doctor Profile Management / Edit Profile
 * 
 * Accessible to logged-in doctors to update their profile details:
 * Name, Phone, Specialty, Consultation Fee, and Available Days.
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
$success_message = '';
$error_message = '';

// Fetch Doctor & User Data
$stmt = $conn->prepare("SELECT u.name, u.email, d.id as doctor_id, d.specialty, d.phone, d.fee, d.available_days 
                        FROM users u 
                        LEFT JOIN doctors d ON u.id = d.user_id 
                        WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res->fetch_assoc();
$stmt->close();

// If doctor row missing, create default entry
if (!$profile || empty($profile['doctor_id'])) {
    $ins = $conn->prepare("INSERT INTO doctors (user_id, specialty, phone, fee, available_days) VALUES (?, 'General Medicine', '01700000000', 50.00, 'Monday, Wednesday, Friday')");
    $ins->bind_param("i", $user_id);
    $ins->execute();
    $ins->close();

    // Re-fetch
    $stmt = $conn->prepare("SELECT u.name, u.email, d.id as doctor_id, d.specialty, d.phone, d.fee, d.available_days 
                            FROM users u 
                            JOIN doctors d ON u.id = d.user_id 
                            WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $fee = floatval($_POST['fee'] ?? 0);
    $available_days = trim($_POST['available_days'] ?? '');

    if (empty($name)) {
        $error_message = "Please enter your full name.";
    } elseif (empty($phone)) {
        $error_message = "Please enter your phone number.";
    } elseif (empty($specialty)) {
        $error_message = "Please enter your medical specialty.";
    } elseif ($fee < 0) {
        $error_message = "Consultation fee cannot be negative.";
    } elseif (empty($available_days)) {
        $error_message = "Please specify your available days.";
    } else {
        // Begin Transaction / Updates
        $conn->begin_transaction();

        try {
            // 1. Update Users Table (Name)
            $u_stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $u_stmt->bind_param("si", $name, $user_id);
            $u_stmt->execute();
            $u_stmt->close();

            // 2. Update Doctors Table (Specialty, Phone, Fee, Available Days)
            $d_stmt = $conn->prepare("UPDATE doctors SET specialty = ?, phone = ?, fee = ?, available_days = ? WHERE user_id = ?");
            $d_stmt->bind_param("ssdsi", $specialty, $phone, $fee, $available_days, $user_id);
            $d_stmt->execute();
            $d_stmt->close();

            $conn->commit();

            // Update Session Name
            $_SESSION['user_name'] = $name;

            // Refresh Profile Data
            $profile['name'] = $name;
            $profile['phone'] = $phone;
            $profile['specialty'] = $specialty;
            $profile['fee'] = $fee;
            $profile['available_days'] = $available_days;

            $success_message = "Your doctor profile has been updated successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to update profile: " . $e->getMessage();
        }
    }
}

$page_title = "Edit Doctor Profile - SmartCare";
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

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#1E3A8A] to-[#172e6e] rounded-2xl p-6 text-white mb-8 shadow-md">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center font-bold text-2xl text-white shadow-inner border border-white/20">
                Dr
            </div>
            <div>
                <span class="px-3 py-1 bg-blue-500/30 text-blue-100 rounded-full text-xs font-semibold uppercase tracking-wider">
                    Profile Settings
                </span>
                <h1 class="text-2xl font-bold mt-1">Edit Professional Profile</h1>
                <p class="text-blue-100 text-sm">Update your public consultation details, fee, and available schedule.</p>
            </div>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($success_message)): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Profile Edit Form -->
    <form action="edit_doctor_profile.php" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm">
        
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3 mb-6">
            Personal & Professional Details
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-800 mb-2">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" required
                    value="<?= htmlspecialchars($profile['name'] ?? '') ?>"
                    placeholder="e.g. Dr. Sarah Ahmed"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm transition-all">
            </div>

            <!-- Email (Read-Only) -->
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-800 mb-2">
                    Account Email (Cannot be changed)
                </label>
                <input type="email" id="email" disabled
                    value="<?= htmlspecialchars($profile['email'] ?? '') ?>"
                    class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 text-sm cursor-not-allowed">
            </div>

            <!-- Specialty -->
            <div>
                <label for="specialty" class="block text-sm font-semibold text-slate-800 mb-2">
                    Medical Specialty <span class="text-red-500">*</span>
                </label>
                <input type="text" id="specialty" name="specialty" required
                    value="<?= htmlspecialchars($profile['specialty'] ?? '') ?>"
                    placeholder="e.g. Cardiology, General Medicine, Pediatrics"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm transition-all">
            </div>

            <!-- Phone Number -->
            <div>
                <label for="phone" class="block text-sm font-semibold text-slate-800 mb-2">
                    Contact Phone Number <span class="text-red-500">*</span>
                </label>
                <input type="tel" id="phone" name="phone" required
                    value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                    placeholder="e.g. 01712345678"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm transition-all">
            </div>

            <!-- Consultation Fee -->
            <div>
                <label for="fee" class="block text-sm font-semibold text-slate-800 mb-2">
                    Consultation Fee (৳) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-sm">
                        ৳
                    </div>
                    <input type="number" id="fee" name="fee" step="0.01" min="0" required
                        value="<?= htmlspecialchars($profile['fee'] ?? 0) ?>"
                        placeholder="500.00"
                        class="w-full pl-9 pr-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm font-semibold transition-all">
                </div>
            </div>

            <!-- Available Days -->
            <div>
                <label for="available_days" class="block text-sm font-semibold text-slate-800 mb-2">
                    Available Days <span class="text-red-500">*</span>
                </label>
                <input type="text" id="available_days" name="available_days" required
                    value="<?= htmlspecialchars($profile['available_days'] ?? '') ?>"
                    placeholder="e.g. Monday, Wednesday, Friday"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm transition-all">
                <p class="text-xs text-slate-400 mt-1">Comma-separated days e.g., Monday, Wednesday, Friday</p>
            </div>

        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="doctor_dashboard.php" class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-[#1E3A8A] hover:bg-[#172e6e] rounded-xl shadow-md transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Profile Changes
            </button>
        </div>

    </form>

</main>

<?php require_once 'footer.php'; ?>
