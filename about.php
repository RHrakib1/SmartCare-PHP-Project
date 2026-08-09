<?php
/**
 * SmartCare - About Page
 */
$page_title = "About Us";
require_once 'header.php';
?>

<main class="flex-grow max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white border border-slate-200 rounded-3xl p-8 sm:p-12 shadow-sm">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-[#1E3A8A] text-xs font-semibold uppercase tracking-wider mb-6">
            About SmartCare
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight mb-6">
            Connecting Patients with Exceptional Medical Care
        </h1>
        <p class="text-slate-600 text-base leading-relaxed mb-6">
            SmartCare is a modern healthcare booking platform built to eliminate friction in primary care access. Our mission is to connect patients with board-certified doctors, specialists, and clinics through an intuitive, secure digital environment.
        </p>
        <p class="text-slate-600 text-base leading-relaxed mb-8">
            Whether you need a routine check-up, specialist consultation, or urgent booking, SmartCare empowers you to compare availability, view consultation fees upfront, and confirm your slot in seconds.
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-6 border-t border-slate-100 text-center">
            <div>
                <div class="text-3xl font-bold text-[#1E3A8A]">100+</div>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider mt-1">Verified Doctors</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-[#1E3A8A]">24/7</div>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider mt-1">Online Booking</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-[#1E3A8A]">100%</div>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider mt-1">Transparent Pricing</div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'footer.php'; ?>
