<?php
/**
 * SmartCare - Demo Payment Gateway
 * 
 * Simulated payment process supporting bKash, Nagad, and Rocket.
 * Updates appointment payment_status to 'paid' and saves the trx_id.
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
    header("Location: index.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$appointment_id = (int)($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);

if ($appointment_id <= 0) {
    header("Location: patient_dashboard.php");
    exit;
}

// Fetch appointment and doctor details
$query = "SELECT a.id as appointment_id, a.date, a.time_slot, a.status, a.payment_status, a.trx_id, 
                 u.name as doctor_name, d.specialty, d.fee 
          FROM appointments a 
          JOIN doctors d ON a.doctor_id = d.id 
          JOIN users u ON d.user_id = u.id 
          WHERE a.id = ? AND a.patient_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$res = $stmt->get_result();
$appointment = $res->fetch_assoc();
$stmt->close();

if (!$appointment) {
    $_SESSION['action_error'] = "Appointment not found or access denied.";
    header("Location: patient_dashboard.php");
    exit;
}

$error_message = '';

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = trim($_POST['payment_method'] ?? '');
    $mobile_number = trim($_POST['mobile_number'] ?? '');
    $trx_id = trim($_POST['trx_id'] ?? '');

    if (empty($payment_method)) {
        $error_message = "Please select a payment method (bKash, Nagad, or Rocket).";
    } elseif (empty($mobile_number)) {
        $error_message = "Please enter your mobile number.";
    } elseif (empty($trx_id)) {
        $error_message = "Please enter a valid Transaction ID.";
    } else {
        $formatted_trx = strtoupper($trx_id);

        // Update appointment table with paid status and transaction ID
        try {
            $update_stmt = $conn->prepare("UPDATE appointments SET payment_status = 'paid', trx_id = ? WHERE id = ? AND patient_id = ?");
            $update_stmt->bind_param("sii", $formatted_trx, $appointment_id, $patient_id);

            if ($update_stmt->execute()) {
                $_SESSION['action_message'] = "Payment of ৳" . number_format($appointment['fee'], 2) . " via " . ucfirst($payment_method) . " was successful! Transaction ID: " . htmlspecialchars($formatted_trx) . " recorded.";
                header("Location: patient_dashboard.php?payment=success");
                exit;
            } else {
                $error_message = "Failed to update payment status. Please try again.";
            }
            $update_stmt->close();
        } catch (mysqli_sql_exception $e) {
            // Handle scenario if columns haven't been added yet to database
            if (strpos($e->getMessage(), "Unknown column") !== false) {
                // Try dynamically adding columns if missing
                try {
                    $conn->query("ALTER TABLE `appointments` ADD COLUMN `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending', ADD COLUMN `trx_id` VARCHAR(255) DEFAULT NULL;");
                } catch (Throwable $ex) {
                    // Ignore duplicate column exception if already present
                }
                
                // Retry update
                $update_stmt = $conn->prepare("UPDATE appointments SET payment_status = 'paid', trx_id = ? WHERE id = ? AND patient_id = ?");
                $update_stmt->bind_param("sii", $formatted_trx, $appointment_id, $patient_id);
                $update_stmt->execute();
                $update_stmt->close();

                $_SESSION['action_message'] = "Payment of ৳" . number_format($appointment['fee'], 2) . " via " . ucfirst($payment_method) . " completed successfully! Transaction ID: " . htmlspecialchars($formatted_trx);
                header("Location: patient_dashboard.php?payment=success");
                exit;
            } else {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

$page_title = "Demo Payment Gateway";
require_once 'header.php';
?>

<main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 py-10 w-full">

    <!-- Back Button -->
    <div class="mb-6">
        <a href="patient_dashboard.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-[#1E3A8A] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#1E3A8A] to-[#172e6e] rounded-2xl p-6 text-white mb-8 shadow-md">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="px-3 py-1 bg-blue-500/30 text-blue-100 rounded-full text-xs font-semibold uppercase tracking-wider">
                    Demo Checkout
                </span>
                <h1 class="text-2xl font-bold mt-2">SmartCare Payment Gateway</h1>
                <p class="text-blue-100 text-sm mt-1">Complete your appointment booking payment safely.</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md px-4 py-3 rounded-xl border border-white/20 text-right sm:text-right">
                <div class="text-xs text-blue-200 uppercase font-semibold">Total Payable</div>
                <div class="text-2xl font-extrabold text-white mt-0.5">৳<?= number_format($appointment['fee'], 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Error Alert -->
    <?php if (!empty($error_message)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-xl text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <!-- Appointment Summary Sidebar -->
        <div class="md:col-span-1">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm sticky top-24">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3 mb-4">
                    Booking Summary
                </h3>

                <div class="space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-medium text-slate-400">Appointment ID</div>
                        <div class="font-semibold text-slate-800">#<?= htmlspecialchars($appointment['appointment_id']) ?></div>
                    </div>

                    <div>
                        <div class="text-xs font-medium text-slate-400">Doctor</div>
                        <div class="font-semibold text-slate-900">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></div>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($appointment['specialty']) ?></div>
                    </div>

                    <div>
                        <div class="text-xs font-medium text-slate-400">Date & Time</div>
                        <div class="font-semibold text-slate-800"><?= date('M d, Y', strtotime($appointment['date'])) ?></div>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($appointment['time_slot']) ?></div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-slate-600 font-medium">Consultation Fee:</span>
                        <span class="font-bold text-[#1E3A8A]">৳<?= number_format($appointment['fee'], 2) ?></span>
                    </div>

                    <?php if ($appointment['payment_status'] === 'paid'): ?>
                        <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-center">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Already Paid (<?= htmlspecialchars($appointment['trx_id']) ?>)
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-400 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    256-bit SSL Encrypted Sandbox Gateway
                </div>
            </div>
        </div>

        <!-- Payment Gateway Form -->
        <div class="md:col-span-2">
            <form action="payment.php" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-sm">
                <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">

                <!-- Step 1: Select Payment Method -->
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-900 mb-3">
                        1. Select Mobile Banking Method <span class="text-red-500">*</span>
                    </label>

                    <div class="grid grid-cols-3 gap-4">

                        <!-- bKash Option -->
                        <label class="relative flex flex-col items-center justify-center p-4 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-[#D12053] transition-all has-[:checked]:border-[#D12053] has-[:checked]:bg-pink-50/40 group">
                            <input type="radio" name="payment_method" value="bkash" class="sr-only" checked>
                            <div class="w-12 h-12 rounded-xl bg-[#D12053] flex items-center justify-center text-white shadow-sm mb-2 group-hover:scale-105 transition-transform">
                                <svg class="w-8 h-8" viewBox="0 0 40 40" fill="currentColor">
                                    <!-- bKash Origami Bird Icon -->
                                    <path d="M8 8 L32 8 L24 20 L32 32 L8 32 L16 20 Z" fill="white" opacity="0.9"/>
                                    <path d="M12 12 L28 12 L20 20 L28 28 L12 28 Z" fill="#D12053"/>
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800">bKash</span>
                            <span class="text-[10px] text-slate-400 font-medium">Instant Pay</span>
                        </label>

                        <!-- Nagad Option -->
                        <label class="relative flex flex-col items-center justify-center p-4 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-[#F7921E] transition-all has-[:checked]:border-[#F7921E] has-[:checked]:bg-orange-50/40 group">
                            <input type="radio" name="payment_method" value="nagad" class="sr-only">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#F7921E] to-[#EA2227] flex items-center justify-center text-white shadow-sm mb-2 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800">Nagad</span>
                            <span class="text-[10px] text-slate-400 font-medium">Fast Checkout</span>
                        </label>

                        <!-- Rocket Option -->
                        <label class="relative flex flex-col items-center justify-center p-4 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-[#802A8F] transition-all has-[:checked]:border-[#802A8F] has-[:checked]:bg-purple-50/40 group">
                            <input type="radio" name="payment_method" value="rocket" class="sr-only">
                            <div class="w-12 h-12 rounded-xl bg-[#802A8F] flex items-center justify-center text-white shadow-sm mb-2 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.71.79-1.81.79-1.81l-3.79-3.79s-1.1.08-1.79.79z"/>
                                    <path d="M12 15l-3-3 8.5-8.5c.83-.83 2.17-.83 3 0s.83 2.17 0 3L12 15z"/>
                                    <path d="M9 18l3 3"/>
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-800">Rocket</span>
                            <span class="text-[10px] text-slate-400 font-medium">DBBL MFS</span>
                        </label>

                    </div>
                </div>

                <!-- Step 2: Mobile Number -->
                <div class="mb-5">
                    <label for="mobile_number" class="block text-sm font-semibold text-slate-800 mb-1.5">
                        2. Mobile Account Number <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm font-semibold">
                            +88
                        </div>
                        <input type="tel" id="mobile_number" name="mobile_number" required placeholder="01712345678" pattern="[0-9]{11}"
                            class="w-full pl-12 pr-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm transition-all"
                            value="01712345678">
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Enter your 11-digit mobile wallet number.</p>
                </div>

                <!-- Step 3: Transaction ID -->
                <div class="mb-6">
                    <label for="trx_id" class="block text-sm font-semibold text-slate-800 mb-1.5">
                        3. Transaction ID (TrxID) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" id="trx_id" name="trx_id" required placeholder="e.g. TRX98765432"
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] text-sm uppercase tracking-wide font-mono transition-all"
                            value="TRX<?= rand(10000000, 99999999) ?>">
                    </div>
                    <p class="text-xs text-slate-400 mt-1">For demo purposes, a auto-generated fake Transaction ID is pre-filled. You can also type any custom TrxID.</p>
                </div>

                <!-- Demo Warning Banner -->
                <div class="mb-6 p-3.5 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-xs text-amber-800 leading-relaxed">
                        <strong class="font-bold">Sandbox Environment:</strong> This is a simulated payment gateway. No real money will be charged from your account. Submitting will update your appointment status to <span class="font-bold text-emerald-700">'paid'</span>.
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3 px-6 bg-[#1E3A8A] hover:bg-[#172e6e] text-white text-base font-bold rounded-xl shadow-md shadow-blue-900/10 transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Confirm & Complete Payment
                </button>
            </form>
        </div>

    </div>

</main>

<?php require_once 'footer.php'; ?>
