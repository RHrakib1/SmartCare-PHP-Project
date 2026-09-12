<?php
/**
 * SmartCare - User Registration
 * 
 * Handles user registration for Patients and Doctors.
 */
session_start();
require_once 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? 'patient');

    // Doctor specific fields
    $specialty = trim($_POST['specialty'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $fee = trim($_POST['fee'] ?? '0');
    $available_days = isset($_POST['available_days']) && is_array($_POST['available_days']) 
        ? implode(',', $_POST['available_days']) 
        : trim($_POST['available_days_str'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password and Confirm Password do not match.';
    } elseif (!in_array($role, ['patient', 'doctor'])) {
        $error = 'Invalid role selected.';
    } elseif ($role === 'doctor' && (empty($specialty) || empty($phone))) {
        $error = 'Specialty and phone number are required for Doctor registration.';
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = 'An account with this email address already exists.';
        } else {
            $check_stmt->close();

            // Hash password securely
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Insert into users table
                $user_stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $user_stmt->bind_param("ssss", $name, $email, $password_hash, $role);
                $user_stmt->execute();
                $user_id = $conn->insert_id;
                $user_stmt->close();

                // If Doctor, insert into doctors table
                if ($role === 'doctor') {
                    $doc_fee = (float)$fee;
                    $doc_stmt = $conn->prepare("INSERT INTO doctors (user_id, specialty, phone, fee, available_days) VALUES (?, ?, ?, ?, ?)");
                    $doc_stmt->bind_param("issds", $user_id, $specialty, $phone, $doc_fee, $available_days);
                    $doc_stmt->execute();
                    $doc_stmt->close();
                }

                $conn->commit();

                // Automatically log in the newly registered user
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);

                $_SESSION['user_id']    = $user_id;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role']  = $role;

                // Redirect directly to home page
                header("Location: index.php");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Registration failed due to a system error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - SmartCare</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 antialiased min-h-screen flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center mb-6">
        <a href="index.php" class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-[#1E3A8A] text-white shadow-sm mb-3 hover:bg-[#172e6e] transition-colors">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
        </a>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Join SmartCare</h2>
        <p class="text-sm text-slate-500 mt-1">Healthcare appointment management simplified</p>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-lg">
        <div class="bg-white py-8 px-6 shadow-sm border border-slate-200/80 rounded-2xl sm:px-10">

            <?php if (!empty($error)): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div>
                        <p class="font-medium"><?= htmlspecialchars($success) ?></p>
                        <p class="mt-1"><a href="login.php" class="underline font-semibold hover:text-emerald-800">Click here to log in</a></p>
                    </div>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-5">
                <!-- Role Selector -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Register As</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex items-center justify-center p-3 border rounded-xl cursor-pointer text-sm font-medium transition-all select-none text-slate-700 border-slate-200 hover:border-slate-300 has-[:checked]:border-[#1E3A8A] has-[:checked]:bg-blue-50/50 has-[:checked]:text-[#1E3A8A]">
                            <input type="radio" name="role" value="patient" class="sr-only" <?= (!isset($_POST['role']) || $_POST['role'] === 'patient') ? 'checked' : '' ?> onchange="toggleDoctorFields(false)">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Patient</span>
                            </div>
                        </label>
                        <label class="relative flex items-center justify-center p-3 border rounded-xl cursor-pointer text-sm font-medium transition-all select-none text-slate-700 border-slate-200 hover:border-slate-300 has-[:checked]:border-[#1E3A8A] has-[:checked]:bg-blue-50/50 has-[:checked]:text-[#1E3A8A]">
                            <input type="radio" name="role" value="doctor" class="sr-only" <?= (isset($_POST['role']) && $_POST['role'] === 'doctor') ? 'checked' : '' ?> onchange="toggleDoctorFields(true)">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Doctor</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="e.g. Dr. Sarah Jenkins or John Doe" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@example.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <div class="relative flex items-center">
                        <input type="password" id="password" name="password" required minlength="6" placeholder="At least 6 characters" class="w-full pl-3.5 pr-11 py-2.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all">
                        <button type="button" onclick="togglePasswordVisibility('password', 'password-eye-icon')" class="absolute right-3 text-slate-400 hover:text-slate-600 focus:outline-none p-1 transition-colors" title="Toggle Password Visibility">
                            <svg id="password-eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                    <div class="relative flex items-center">
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Re-enter your password" class="w-full pl-3.5 pr-11 py-2.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', 'confirm-password-eye-icon')" class="absolute right-3 text-slate-400 hover:text-slate-600 focus:outline-none p-1 transition-colors" title="Toggle Confirm Password Visibility">
                            <svg id="confirm-password-eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Doctor Fields (Conditional) -->
                <div id="doctor-fields" class="space-y-4 pt-2 border-t border-slate-100 <?= (isset($_POST['role']) && $_POST['role'] === 'doctor') ? '' : 'hidden' ?>">
                    <h3 class="text-sm font-semibold text-[#1E3A8A] uppercase tracking-wider">Doctor Professional Profile</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="specialty" class="block text-sm font-medium text-slate-700 mb-1">Specialty</label>
                            <input type="text" id="specialty" name="specialty" value="<?= htmlspecialchars($_POST['specialty'] ?? '') ?>" placeholder="e.g. Cardiology" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+1 (555) 000-0000" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="fee" class="block text-sm font-medium text-slate-700 mb-1">Consultation Fee ($)</label>
                            <input type="number" step="0.01" min="0" id="fee" name="fee" value="<?= htmlspecialchars($_POST['fee'] ?? '50.00') ?>" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        </div>
                        <div>
                            <label for="available_days_str" class="block text-sm font-medium text-slate-700 mb-1">Available Days</label>
                            <input type="text" id="available_days_str" name="available_days_str" value="<?= htmlspecialchars($_POST['available_days_str'] ?? 'Monday,Wednesday,Friday') ?>" placeholder="e.g. Monday, Wednesday, Friday" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#1E3A8A] hover:bg-[#162a64] active:bg-[#0f1d46] text-white font-medium py-2.5 px-4 rounded-lg shadow-sm transition-colors text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1E3A8A]">
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-500 border-t border-slate-100 pt-4">
                Already have an account? 
                <a href="login.php" class="font-semibold text-[#1E3A8A] hover:underline">Sign in here</a>
            </div>
        </div>
    </div>

    <script>
        function toggleDoctorFields(show) {
            const container = document.getElementById('doctor-fields');
            if (show) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.122-.963c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-2.518 3.864M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }
    </script>
</body>
</html>

