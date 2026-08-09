<?php
/**
 * SmartCare - Doctors Listing & Appointment Booking Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$booking_success = '';
$booking_error = '';

// Handle Appointment Booking Form Submission (BEFORE any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=" . urlencode("doctors.php"));
        exit;
    }

    if ($_SESSION['user_role'] !== 'patient') {
        $booking_error = "Only registered patients can book appointments. You are logged in as a " . htmlspecialchars($_SESSION['user_role']) . ".";
    } else {
        $patient_id = $_SESSION['user_id'];
        $doctor_id = (int)($_POST['doctor_id'] ?? 0);
        $date = trim($_POST['date'] ?? '');
        $time_slot = trim($_POST['time_slot'] ?? '');

        if ($doctor_id <= 0 || empty($date) || empty($time_slot)) {
            $booking_error = "Please select a valid doctor, date, and time slot.";
        } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $booking_error = "Appointment date cannot be in the past.";
        } else {
            // Check if doctor exists
            $check_doc = $conn->prepare("SELECT id FROM doctors WHERE id = ?");
            $check_doc->bind_param("i", $doctor_id);
            $check_doc->execute();
            if ($check_doc->get_result()->num_rows === 0) {
                $booking_error = "Selected doctor does not exist.";
            } else {
                // Insert appointment into database
                $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, date, time_slot, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iiss", $patient_id, $doctor_id, $date, $time_slot);
                
                if ($stmt->execute()) {
                    $booking_success = "Your appointment has been successfully requested! You can view its status on your Patient Dashboard.";
                } else {
                    $booking_error = "Failed to submit booking request. Please try again.";
                }
                $stmt->close();
            }
            $check_doc->close();
        }
    }
}

// Search and filter parameters
$search_q = trim($_GET['q'] ?? '');
$search_spec = trim($_GET['specialty'] ?? '');

// Fetch doctors
$sql = "SELECT d.id as doctor_id, u.name as doctor_name, u.email, d.specialty, d.phone, d.fee, d.available_days 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search_q)) {
    $sql .= " AND (u.name LIKE ? OR d.specialty LIKE ?)";
    $q_param = "%{$search_q}%";
    $params[] = $q_param;
    $params[] = $q_param;
    $types .= "ss";
}

if (!empty($search_spec)) {
    $sql .= " AND d.specialty = ?";
    $params[] = $search_spec;
    $types .= "s";
}

$sql .= " ORDER BY u.name ASC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$doctors_result = $stmt->get_result();

// Available time slots array for booking form
$time_slots = [
    '09:00 AM - 09:30 AM',
    '10:00 AM - 10:30 AM',
    '11:00 AM - 11:30 AM',
    '02:00 PM - 02:30 PM',
    '03:00 PM - 03:30 PM',
    '04:00 PM - 04:30 PM'
];

// Include header AFTER logic & redirects
$page_title = "Book a Doctor";
require_once 'header.php';
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">

    <!-- Page Title & Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Available Doctors & Specialists</h1>
            <p class="text-slate-500 text-sm mt-1">Select a verified healthcare professional to book an instant appointment.</p>
        </div>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'patient'): ?>
            <a href="patient_dashboard.php" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-[#1E3A8A] text-sm font-semibold rounded-xl border border-blue-100 hover:bg-blue-100/70 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                View My Appointments
            </a>
        <?php endif; ?>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($booking_success)): ?>
        <div class="mb-8 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-sm">Booking Successful</h4>
                    <p class="text-sm mt-0.5 text-emerald-700"><?= htmlspecialchars($booking_success) ?></p>
                </div>
            </div>
            <a href="patient_dashboard.php" class="text-xs font-bold text-emerald-900 underline whitespace-nowrap">Go to Dashboard &rarr;</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($booking_error)): ?>
        <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm"><?= htmlspecialchars($booking_error) ?></p>
        </div>
    <?php endif; ?>

    <!-- Search & Filter Controls -->
    <div class="bg-white p-4 border border-slate-200 rounded-2xl shadow-sm mb-8">
        <form action="doctors.php" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="w-full flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="q" value="<?= htmlspecialchars($search_q) ?>" placeholder="Search doctor name or specialty..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:bg-white">
            </div>
            <div class="w-full sm:w-56">
                <input type="text" name="specialty" value="<?= htmlspecialchars($search_spec) ?>" placeholder="Filter by specialty..." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:bg-white">
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-[#1E3A8A] hover:bg-[#172e6e] text-white text-sm font-medium rounded-xl transition-colors">
                Search
            </button>
            <?php if (!empty($search_q) || !empty($search_spec)): ?>
                <a href="doctors.php" class="px-4 py-2.5 text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Doctor Cards Listing -->
    <?php if ($doctors_result && $doctors_result->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($doc = $doctors_result->fetch_assoc()): ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between hover:border-slate-300 transition-all">
                    <div>
                        <!-- Header / Badge -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-100 text-[#1E3A8A] flex items-center justify-center font-bold text-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-bold px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full">
                                $<?= number_format($doc['fee'], 2) ?> / Visit
                            </span>
                        </div>

                        <!-- Info -->
                        <h3 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($doc['doctor_name']) ?></h3>
                        <p class="text-xs font-bold text-[#1E3A8A] uppercase tracking-wider mt-0.5 mb-4"><?= htmlspecialchars($doc['specialty']) ?></p>

                        <!-- Details -->
                        <div class="space-y-2 text-xs text-slate-600 border-t border-slate-100 pt-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span><?= htmlspecialchars($doc['phone']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Available: <?= htmlspecialchars($doc['available_days'] ?: 'Monday - Friday') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <button type="button" 
                            onclick="openBookingModal(<?= $doc['doctor_id'] ?>, '<?= htmlspecialchars(addslashes($doc['doctor_name'])) ?>', '<?= htmlspecialchars(addslashes($doc['specialty'])) ?>', '<?= number_format($doc['fee'], 2) ?>')"
                            class="w-full text-center px-4 py-2.5 bg-[#1E3A8A] hover:bg-[#172e6e] active:bg-[#0f1d46] text-white text-sm font-medium rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Book Appointment</span>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center max-w-lg mx-auto">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No registered doctors found</h3>
            <p class="text-sm text-slate-500 mt-1">Register a new Doctor account or adjust your search keywords.</p>
            <div class="mt-4">
                <a href="register.php" class="inline-block px-4 py-2 text-xs font-semibold text-[#1E3A8A] bg-blue-50 rounded-lg">Register a Doctor Account &rarr;</a>
            </div>
        </div>
    <?php endif; ?>

</main>

<!-- Appointment Booking Modal -->
<div id="booking-modal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all">
        
        <!-- Modal Header -->
        <div class="bg-[#F8FAFC] px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base" id="modal-doctor-name">Book Appointment</h3>
                <p class="text-xs text-slate-500" id="modal-doctor-specialty">Specialist Doctor</p>
            </div>
            <button type="button" onclick="closeBookingModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="doctors.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="book_appointment">
            <input type="hidden" name="doctor_id" id="modal-doctor-id" value="">

            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-xs flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold">Sign in required</p>
                        <p class="mt-0.5">You must be logged in as a patient to confirm this booking. Submitting will prompt login.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Fee Display -->
            <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3.5 flex items-center justify-between text-xs">
                <span class="text-slate-600 font-medium">Consultation Fee</span>
                <span class="font-bold text-[#1E3A8A] text-sm" id="modal-doctor-fee">$0.00</span>
            </div>

            <!-- Date Picker -->
            <div>
                <label for="booking-date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Select Date</label>
                <input type="date" id="booking-date" name="date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
            </div>

            <!-- Time Slot Selector -->
            <div>
                <label for="booking-time" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">Select Preferred Time Slot</label>
                <select id="booking-time" name="time_slot" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
                    <?php foreach ($time_slots as $slot): ?>
                        <option value="<?= htmlspecialchars($slot) ?>"><?= htmlspecialchars($slot) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Modal Action Footer -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeBookingModal()" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#1E3A8A] hover:bg-[#172e6e] text-white text-xs font-bold rounded-xl shadow-sm transition-colors">
                    Confirm & Request Booking
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function openBookingModal(doctorId, doctorName, specialty, fee) {
        document.getElementById('modal-doctor-id').value = doctorId;
        document.getElementById('modal-doctor-name').textContent = 'Book with ' + doctorName;
        document.getElementById('modal-doctor-specialty').textContent = specialty;
        document.getElementById('modal-doctor-fee').textContent = '$' + fee;
        document.getElementById('booking-modal').classList.remove('hidden');
    }

    function closeBookingModal() {
        document.getElementById('booking-modal').classList.add('hidden');
    }
</script>

<?php require_once 'footer.php'; ?>
