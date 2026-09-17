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

// Fetch top 3 featured doctors from database
$featured_doctors = [];
$doc_query = $conn->query("
    SELECT d.id as doctor_id, u.name as doctor_name, u.email, d.specialty, d.phone, d.fee, d.available_days 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    ORDER BY d.id ASC 
    LIMIT 3
");

if ($doc_query && $doc_query->num_rows > 0) {
    while ($row = $doc_query->fetch_assoc()) {
        $featured_doctors[] = $row;
    }
}

// Fallback demo doctors if database has fewer than 3 doctors
$fallback_doctors = [
    [
        'doctor_id' => 1,
        'doctor_name' => 'Dr. Sarah Jenkins',
        'specialty' => 'Cardiology',
        'fee' => '80.00',
        'available_days' => 'Monday, Wednesday, Friday',
        'rating' => '4.9',
        'reviews' => 128,
        'initials' => 'SJ'
    ],
    [
        'doctor_id' => 2,
        'doctor_name' => 'Dr. Marcus Vance',
        'specialty' => 'Neurology',
        'fee' => '95.00',
        'available_days' => 'Tuesday, Thursday, Saturday',
        'rating' => '4.8',
        'reviews' => 94,
        'initials' => 'MV'
    ],
    [
        'doctor_id' => 3,
        'doctor_name' => 'Dr. Elena Rostova',
        'specialty' => 'Dermatology',
        'fee' => '75.00',
        'available_days' => 'Monday, Tuesday, Thursday',
        'rating' => '5.0',
        'reviews' => 156,
        'initials' => 'ER'
    ]
];

// Combine DB doctors with fallbacks to ensure exactly 3 doctors displayed
while (count($featured_doctors) < 3) {
    $fallback = $fallback_doctors[count($featured_doctors)];
    $featured_doctors[] = $fallback;
}
?>

<!-- Main Content Area -->
<main class="flex-grow bg-[#F8FAFC]">

    <!-- Hero Banner Section (Split Grid Layout) -->
    <section class="relative overflow-hidden bg-white border-b border-slate-200/80 py-12 lg:py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            
            <!-- Left Column: Value Proposition & Search -->
            <div class="lg:col-span-7 space-y-6 text-left">
                
                <!-- Badge Tag -->
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-[#1E3A8A] text-xs font-semibold tracking-wide shadow-sm">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-[#1E3A8A]"></span>
                    </span>
                    <span>Verified Healthcare Network</span>
                </div>

                <!-- Main Heading -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.15]">
                    Find & Book <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#1E3A8A] to-blue-600">Certified Doctors</span> In Seconds
                </h1>

                <!-- Subtitle -->
                <p class="text-base sm:text-lg text-slate-600 font-normal leading-relaxed max-w-xl">
                    Connect with leading medical specialists, compare consultation fees, view live availability, and schedule your appointment effortlessly online.
                </p>

                <!-- Search Form Bar -->
                <div class="bg-white p-2.5 sm:p-3 border border-slate-200/80 rounded-2xl shadow-xl shadow-slate-200/50 hover:border-slate-300 transition-all max-w-2xl">
                    <form action="doctors.php" method="GET" class="flex flex-col sm:flex-row items-center gap-2.5">
                        
                        <!-- Search Input -->
                        <div class="relative w-full flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" name="q" placeholder="Doctor name or specialty (e.g. Cardiology)..." class="w-full pl-10 pr-3.5 py-3 bg-slate-50 border border-slate-200/80 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:bg-white transition-all">
                        </div>

                        <!-- Specialty Dropdown -->
                        <div class="w-full sm:w-48">
                            <select name="specialty" class="w-full px-3 py-3 bg-slate-50 border border-slate-200/80 rounded-xl text-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:bg-white transition-all">
                                <option value="">All Specialties</option>
                                <?php foreach ($specialties as $spec): ?>
                                    <option value="<?= htmlspecialchars($spec) ?>"><?= htmlspecialchars($spec) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-[#1E3A8A] hover:bg-[#172e6e] active:bg-[#0f1d46] text-white font-medium text-sm rounded-xl shadow-md transition-all flex items-center justify-center gap-2 shrink-0 group">
                            <span>Search</span>
                            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Popular Specialty Quick Filter Tags & Trust indicators -->
                <div class="pt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="font-medium text-slate-700">Popular:</span>
                    <?php foreach (array_slice($specialties, 0, 5) as $spec): ?>
                        <a href="doctors.php?specialty=<?= urlencode($spec) ?>" class="px-3 py-1 bg-slate-100/90 hover:bg-blue-50 hover:text-[#1E3A8A] text-slate-600 rounded-full border border-slate-200/60 transition-all font-medium">
                            <?= htmlspecialchars($spec) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Small Trust Proof Badges -->
                <div class="pt-3 flex items-center gap-6 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Free Instant Booking</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Verified Specialist Profiles</span>
                    </div>
                </div>

            </div>

            <!-- Right Column: High-Quality Medical Hero Photography -->
            <div class="lg:col-span-5 relative">
                <!-- Background Ambient Blur Glow -->
                <div class="absolute -inset-3 bg-gradient-to-r from-blue-600/20 via-indigo-600/20 to-emerald-500/20 rounded-[2.5rem] blur-2xl opacity-70 pointer-events-none"></div>
                
                <!-- Hero Medical Photography Container -->
                <div class="relative rounded-3xl overflow-hidden shadow-2xl border border-slate-100/90 bg-white group">
                    <img src="https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=1000&q=80" alt="Doctor consulting patient digitally" class="w-full h-[420px] sm:h-[460px] object-cover object-top group-hover:scale-[1.02] transition-transform duration-700">
                    
                    <!-- Gradient Overlay Tint -->
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-slate-900/10 to-transparent"></div>

                    <!-- Floating Badge Top Right: Doctor Rating -->
                    <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-2xl shadow-lg border border-white/50 flex items-center gap-2">
                        <div class="flex items-center text-amber-400 text-sm">
                            ★ <span class="text-slate-900 font-bold ml-1 text-xs">4.9/5</span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-semibold border-l border-slate-200 pl-2">Top Rated</span>
                    </div>

                    <!-- Floating Card Bottom: Appointment Booking Preview -->
                    <div class="absolute bottom-4 left-4 right-4 bg-white/95 backdrop-blur-md p-3.5 rounded-2xl shadow-xl border border-white/60 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900">Dr. Sarah Jenkins</p>
                                <p class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                                    Cardiology • Available Today
                                </p>
                            </div>
                        </div>
                        <a href="doctors.php" class="px-3.5 py-2 bg-[#1E3A8A] hover:bg-[#172e6e] text-white text-xs font-bold rounded-xl shadow-sm transition-colors shrink-0">
                            Book Slot
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Dynamic Stats & Trust Counter Band -->
    <section class="bg-white border-b border-slate-200/80 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-0 divide-y md:divide-y-0 md:divide-x divide-slate-200/80">
                
                <!-- Stat 1 -->
                <div class="pt-4 md:pt-0 md:px-6 text-center md:text-left flex flex-col justify-center">
                    <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight font-heading">100+</div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-500 mt-1">Verified Doctors</div>
                </div>

                <!-- Stat 2 -->
                <div class="pt-4 md:pt-0 md:px-6 text-center md:text-left flex flex-col justify-center">
                    <div class="text-3xl sm:text-4xl font-extrabold text-[#1E3A8A] tracking-tight font-heading">24/7</div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-500 mt-1">Online Booking</div>
                </div>

                <!-- Stat 3 -->
                <div class="pt-4 md:pt-0 md:px-6 text-center md:text-left flex flex-col justify-center">
                    <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight font-heading">100%</div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-500 mt-1">Transparent Pricing</div>
                </div>

                <!-- Stat 4 -->
                <div class="pt-4 md:pt-0 md:px-6 text-center md:text-left flex flex-col justify-center">
                    <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight font-heading">15,000+</div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-500 mt-1">Patients Served</div>
                </div>

            </div>
        </div>
    </section>

    <!-- Featured Doctors Carousel / Dynamic Grid Section -->
    <section class="py-16 sm:py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        
        <!-- Section Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
            <div>
                <span class="text-xs font-bold text-[#1E3A8A] uppercase tracking-wider bg-blue-50 px-3 py-1 rounded-full border border-blue-100">Top Rated Doctors</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-3">Featured Medical Specialists</h2>
                <p class="text-slate-500 text-sm sm:text-base mt-1 max-w-xl">Consult with top-rated, certified physicians verified for outstanding care quality and patient satisfaction.</p>
            </div>
            <a href="doctors.php" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1E3A8A] hover:text-[#172e6e] group shrink-0">
                <span>View All Doctors</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>

        <!-- Featured Doctors Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($featured_doctors as $doc): ?>
                <?php
                // Generate initials for doctor avatar
                $doc_name = $doc['doctor_name'] ?? 'Doctor';
                $name_parts = explode(' ', str_replace(['Dr.', 'Prof.'], '', $doc_name));
                $initials = '';
                foreach ($name_parts as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $initials .= strtoupper($part[0]);
                    }
                }
                $initials = substr($initials, 0, 2);
                if (empty($initials)) $initials = 'DR';
                
                $rating = $doc['rating'] ?? '4.9';
                $reviews = $doc['reviews'] ?? (40 + rand(10, 80));
                $doctor_id = $doc['doctor_id'] ?? 1;
                ?>

                <!-- Doctor Card -->
                <div class="group bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <!-- Top header: Avatar & Specialty -->
                        <div class="flex items-start justify-between mb-5">
                            <div class="relative">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#1E3A8A] to-blue-600 text-white font-bold text-base flex items-center justify-center shadow-md group-hover:scale-105 transition-transform">
                                    <?= htmlspecialchars($initials) ?>
                                </div>
                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white" title="Active Specialist"></span>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-blue-50 text-[#1E3A8A] border border-blue-100/80 text-xs font-semibold">
                                <?= htmlspecialchars($doc['specialty']) ?>
                            </span>
                        </div>

                        <!-- Doctor Info -->
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-[#1E3A8A] transition-colors">
                            <?= htmlspecialchars($doc['doctor_name']) ?>
                        </h3>
                        
                        <!-- Rating -->
                        <div class="flex items-center gap-1 mt-1 text-amber-400 text-xs">
                            <span>★</span>
                            <span class="font-bold text-slate-800 ml-0.5"><?= htmlspecialchars($rating) ?></span>
                            <span class="text-slate-400">(<?= htmlspecialchars($reviews) ?> reviews)</span>
                        </div>

                        <!-- Details List -->
                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-medium">Consultation Fee:</span>
                                <span class="font-bold text-slate-900">৳<?= htmlspecialchars(number_format((float)$doc['fee'], 2)) ?></span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-slate-400 font-medium shrink-0">Available Days:</span>
                                <span class="font-medium text-slate-700 text-right truncate max-w-[170px]" title="<?= htmlspecialchars($doc['available_days']) ?>">
                                    <?= htmlspecialchars($doc['available_days']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Action Button -->
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <a href="doctors.php?doctor_id=<?= (int)$doctor_id ?>" class="w-full py-2.5 bg-slate-50 hover:bg-[#1E3A8A] text-slate-800 hover:text-white border border-slate-200/80 hover:border-[#1E3A8A] rounded-xl text-xs font-semibold transition-all duration-200 flex items-center justify-center gap-1.5">
                            <span>Book Now</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </section>

    <!-- How It Works (Step-by-Step UI) Section -->
    <section class="py-16 sm:py-24 bg-white border-t border-slate-200/80 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold text-[#1E3A8A] uppercase tracking-wider bg-blue-50 px-3 py-1 rounded-full border border-blue-100">Simple 3-Step Process</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3">How SmartCare Works</h2>
                <p class="text-slate-500 text-sm sm:text-base mt-2">Book appointments with certified healthcare providers in three effortless steps.</p>
            </div>

            <!-- Workflow Steps Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                
                <!-- Step Card 1 -->
                <div class="relative bg-white border border-slate-200/80 rounded-2xl p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <!-- Step Badge & Icon -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1E3A8A] border border-blue-100/80 flex items-center justify-center font-bold text-base shadow-sm group-hover:bg-[#1E3A8A] group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <span class="text-3xl font-black text-slate-200 group-hover:text-[#1E3A8A]/20 transition-colors">01</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">1. Search & Filter</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Search by doctor name or medical discipline. Filter specialists by fees, available days, and ratings to find your ideal match.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-semibold text-[#1E3A8A]">
                        <span>Browse Directory</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </div>
                </div>

                <!-- Step Card 2 -->
                <div class="relative bg-white border border-slate-200/80 rounded-2xl p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <!-- Step Badge & Icon -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1E3A8A] border border-blue-100/80 flex items-center justify-center font-bold text-base shadow-sm group-hover:bg-[#1E3A8A] group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="text-3xl font-black text-slate-200 group-hover:text-[#1E3A8A]/20 transition-colors">02</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">2. Select Time Slot</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Pick an open appointment date and specific time slot directly from the doctor's live availability calendar.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-semibold text-[#1E3A8A]">
                        <span>Real-Time Availability</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </div>
                </div>

                <!-- Step Card 3 -->
                <div class="relative bg-white border border-slate-200/80 rounded-2xl p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <!-- Step Badge & Icon -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1E3A8A] border border-blue-100/80 flex items-center justify-center font-bold text-base shadow-sm group-hover:bg-[#1E3A8A] group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <span class="text-3xl font-black text-slate-200 group-hover:text-[#1E3A8A]/20 transition-colors">03</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">3. Instant Confirmation</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Submit your booking request with one click. Access video consultation links and booking updates right in your dashboard.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-semibold text-[#1E3A8A]">
                        <span>Dashboard Tracking</span>
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Call to Action Banner for Doctors -->
    <section class="py-14 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="relative overflow-hidden bg-gradient-to-r from-[#1E3A8A] via-blue-800 to-indigo-900 rounded-3xl p-8 sm:p-12 text-white shadow-2xl shadow-blue-950/20">
            <!-- Decorative Accent circles -->
            <div class="absolute -top-24 -right-24 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
                <div class="max-w-2xl space-y-2">
                    <span class="inline-block px-3 py-1 rounded-full bg-blue-500/20 text-blue-200 border border-blue-400/30 text-xs font-semibold uppercase tracking-wider">
                        For Healthcare Providers
                    </span>
                    <h3 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Are you a doctor looking to expand your practice?</h3>
                    <p class="text-blue-100 text-sm sm:text-base font-normal leading-relaxed">
                        Join SmartCare to manage patient appointments seamlessly, set custom availability schedules, and deliver modern telemedicine care.
                    </p>
                </div>
                <div class="flex items-center gap-4 shrink-0 w-full sm:w-auto">
                    <a href="register.php" class="w-full sm:w-auto px-7 py-3.5 bg-white hover:bg-blue-50 text-[#1E3A8A] text-sm font-bold rounded-xl shadow-lg transition-all text-center">
                        Register as a Doctor
                    </a>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require_once 'footer.php'; ?>

