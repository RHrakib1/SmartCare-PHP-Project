<?php
/**
 * SmartCare - Landing Page
 */
$page_title = "Find & Book Top Doctors";
require_once 'header.php';
require_once 'db.php';

// Fetch unique specialties available in database for quick filter dropdown
$specialties_query = $conn->query("SELECT DISTINCT specialty FROM doctors WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty ASC");
$specialties = [];
if ($specialties_query) {
    while ($row = $specialties_query->fetch_assoc()) {
        $specialties[] = $row['specialty'];
    }
}
// Fallback default specialties if none in DB yet
if (empty($specialties)) {
    $specialties = ['Cardiology', 'Dermatology', 'General Medicine', 'Neurology', 'Orthopedics', 'Pediatrics'];
}
?>

<!-- Main Content Area -->
<main class="flex-grow">

    <!-- Hero Banner Section -->
    <section class="bg-white border-b border-slate-200 py-16 lg:py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto text-center">
            
            <!-- Badge Tag -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-[#1E3A8A] text-xs font-semibold uppercase tracking-wider mb-6">
                <span class="w-2 h-2 rounded-full bg-[#1E3A8A]"></span>
                Modern Healthcare Booking
            </div>

            <!-- Main Heading -->
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-slate-900 leading-tight mb-6">
                Find & Book Certified Doctors <br class="hidden sm:block"> In Seconds
            </h1>

            <!-- Subtitle -->
            <p class="text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto font-normal leading-relaxed mb-10">
                Connect with leading specialists, compare consultation fees, and schedule your appointment effortlessly online.
            </p>

            <!-- Search Form Bar -->
            <div class="max-w-3xl mx-auto bg-white p-3 border border-slate-200 rounded-2xl shadow-lg shadow-slate-100/80">
                <form action="doctors.php" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative w-full flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="q" placeholder="Search doctor name or specialty (e.g. Cardiology)..." class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:bg-white transition-all">
                    </div>

                    <!-- Specialty Filter Dropdown -->
                    <div class="w-full sm:w-56">
                        <select name="specialty" class="w-full px-3.5 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:bg-white transition-all">
                            <option value="">All Specialties</option>
                            <?php foreach ($specialties as $spec): ?>
                                <option value="<?= htmlspecialchars($spec) ?>"><?= htmlspecialchars($spec) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full sm:w-auto px-7 py-3 bg-[#1E3A8A] hover:bg-[#172e6e] active:bg-[#0f1d46] text-white font-medium text-sm rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2 shrink-0">
                        <span>Search</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Quick Specialty Tags -->
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
                <span class="font-medium text-slate-700">Popular:</span>
                <?php foreach (array_slice($specialties, 0, 5) as $spec): ?>
                    <a href="doctors.php?specialty=<?= urlencode($spec) ?>" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full transition-colors">
                        <?= htmlspecialchars($spec) ?>
                    </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- Key Feature Highlights Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Why Patients & Doctors Choose SmartCare</h2>
            <p class="text-slate-500 text-sm sm:text-base mt-2">Designed to streamline primary care access with transparent scheduling and intuitive tools.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Feature Card 1 -->
            <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm hover:shadow-md transition-shadow flex flex-col items-start">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#1E3A8A] border border-blue-100 flex items-center justify-center mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Easy Online Booking</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Select your preferred doctor, pick an available date and time slot, and confirm your appointment instantly without phone calls.
                </p>
            </div>

            <!-- Feature Card 2 -->
            <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm hover:shadow-md transition-shadow flex flex-col items-start">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#1E3A8A] border border-blue-100 flex items-center justify-center mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Verified Specialists</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Access experienced medical specialists across various disciplines with transparent consultation fees and clear schedule availability.
                </p>
            </div>

            <!-- Feature Card 3 -->
            <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm hover:shadow-md transition-shadow flex flex-col items-start">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#1E3A8A] border border-blue-100 flex items-center justify-center mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Seamless Management</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    Patients and doctors can track upcoming appointments, review booking status, and manage profile schedules through intuitive dashboards.
                </p>
            </div>

        </div>
    </section>

    <!-- Call to Action Banner -->
    <section class="bg-white border-y border-slate-200 py-14 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
            <div>
                <h3 class="text-xl font-bold text-slate-900">Are you a healthcare provider?</h3>
                <p class="text-sm text-slate-500 mt-1">Join SmartCare to manage patient appointments and grow your medical practice.</p>
            </div>
            <a href="register.php" class="px-6 py-3 bg-[#1E3A8A] hover:bg-[#172e6e] text-white text-sm font-medium rounded-xl shadow-sm transition-colors shrink-0">
                Register as a Doctor
            </a>
        </div>
    </section>

</main>

<?php require_once 'footer.php'; ?>
