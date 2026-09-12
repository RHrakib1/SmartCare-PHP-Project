<?php
/**
 * SmartCare - About Us Page
 */
$page_title = "About Us";
require_once 'header.php';
?>

<main class="flex-grow bg-[#F8FAFC]">

    <!-- Hero Header Banner Section -->
    <section class="bg-white border-b border-slate-200/80 py-16 sm:py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center space-y-4">
            
            <!-- Badge Tag -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-[#1E3A8A] text-xs font-semibold uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-[#1E3A8A]"></span>
                Our Story & Mission
            </div>

            <!-- Main Heading -->
            <h1 class="text-4xl sm:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Transforming Healthcare Access Through <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#1E3A8A] to-blue-600">Smart Technology</span>
            </h1>

            <!-- Subtitle -->
            <p class="text-lg text-slate-600 max-w-2xl mx-auto font-normal leading-relaxed">
                SmartCare is engineered to eliminate friction in primary care access, empowering patients to find top-rated medical specialists and book transparent appointments in seconds.
            </p>

        </div>
    </section>

    <!-- Company Story & Mission Split Section with Visual Image -->
    <section class="py-16 sm:py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Column: Medical Team & Clinic Environment Photography -->
            <div class="lg:col-span-6 relative">
                <!-- Ambient Blur Glow -->
                <div class="absolute -inset-3 bg-gradient-to-r from-blue-600/15 to-indigo-600/15 rounded-[2.5rem] blur-2xl opacity-70 pointer-events-none"></div>
                
                <!-- Main Image Container -->
                <div class="relative rounded-3xl overflow-hidden shadow-2xl border border-slate-100/90 group">
                    <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1000&q=80" alt="Modern healthcare team and clinic environment" class="w-full h-[400px] sm:h-[480px] object-cover group-hover:scale-[1.02] transition-transform duration-700">
                    
                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/50 via-slate-900/10 to-transparent"></div>

                    <!-- Floating Quality Badge Accent -->
                    <div class="absolute bottom-6 left-6 right-6 bg-white/95 backdrop-blur-md p-4 rounded-2xl shadow-xl border border-white/60 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-blue-50 text-[#1E3A8A] border border-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900">Verified Specialist Network</h4>
                                <p class="text-[11px] text-slate-500">Board-certified doctors across 20+ disciplines</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-[#1E3A8A] bg-blue-50 px-3 py-1 rounded-full border border-blue-100">100% Quality Care</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Our Mission & Narrative -->
            <div class="lg:col-span-6 space-y-6">
                <span class="text-xs font-bold text-[#1E3A8A] uppercase tracking-wider bg-blue-50 px-3 py-1 rounded-full border border-blue-100">Why We Founded SmartCare</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Healthcare Should Be Accessible, Transparent, & Immediate
                </h2>
                <p class="text-slate-600 text-base leading-relaxed">
                    Traditional doctor appointment booking is often plagued by long phone waits, hidden consultation costs, and fragmented schedule availability. SmartCare was built to solve these systemic pain points.
                </p>
                <p class="text-slate-600 text-base leading-relaxed">
                    Our platform bridges the gap between patients and top-rated medical specialists. By unifying real-time schedule calendars, transparent pricing upfront, and automated consultation reminders, we ensure every patient receives fast, dependable care.
                </p>

                <!-- Checkpoints list -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-800">Zero phone call queues or administrative delays</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-800">Complete fee transparency with no surprise bills</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-800">Direct patient-doctor connection & dashboard tracking</span>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- Core Values Section (3-Column Layout with Custom Icons) -->
    <section class="py-16 sm:py-24 bg-white border-t border-slate-200/80 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold text-[#1E3A8A] uppercase tracking-wider bg-blue-50 px-3 py-1 rounded-full border border-blue-100">Principles That Guide Us</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mt-3">Our Core Pillars & Values</h2>
                <p class="text-slate-500 text-sm sm:text-base mt-2">Built around patient safety, medical excellence, and digital simplicity.</p>
            </div>

            <!-- 3-Column Values Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Pillar 1 -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-[#1E3A8A] border border-blue-100 flex items-center justify-center mb-6 shadow-sm group-hover:bg-[#1E3A8A] group-hover:text-white transition-colors">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Patient-Centered Experience</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            We design every workflow with the patient in mind — from simple search filters to instant booking confirmations and clear schedule details.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-[#1E3A8A] flex items-center gap-1.5">
                        <span>Built for Patient Comfort</span>
                    </div>
                </div>

                <!-- Pillar 2 -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-[#1E3A8A] border border-blue-100 flex items-center justify-center mb-6 shadow-sm group-hover:bg-[#1E3A8A] group-hover:text-white transition-colors">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Uncompromised Quality</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Every medical professional listed on SmartCare is thoroughly verified. We maintain strict standards for credentials, specialties, and care practices.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-[#1E3A8A] flex items-center gap-1.5">
                        <span>Board-Certified Specialists</span>
                    </div>
                </div>

                <!-- Pillar 3 -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-[#1E3A8A] border border-blue-100 flex items-center justify-center mb-6 shadow-sm group-hover:bg-[#1E3A8A] group-hover:text-white transition-colors">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Seamless Digital Access</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            No hidden fees or complex paperwork. Patients can review consultation pricing upfront, choose real-time slots, and receive meeting details instantly.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-[#1E3A8A] flex items-center gap-1.5">
                        <span>Real-Time Consultation Links</span>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- Platform Impact Counter Band -->
    <section class="bg-white border-y border-slate-200/80 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-[#1E3A8A] tracking-tight font-heading">100+</div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-500 mt-1">Verified Specialists</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight font-heading">15,000+</div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-500 mt-1">Appointments Booked</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight font-heading">4.9 / 5</div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-500 mt-1">Patient Satisfaction</div>
                </div>
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight font-heading">24/7</div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-500 mt-1">Platform Availability</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="bg-gradient-to-r from-[#1E3A8A] via-blue-800 to-indigo-900 rounded-3xl p-8 sm:p-12 text-white shadow-2xl shadow-blue-950/20 text-center space-y-6">
            <h3 class="text-2xl sm:text-4xl font-extrabold tracking-tight">Ready to Experience Better Healthcare?</h3>
            <p class="text-blue-100 text-sm sm:text-base max-w-xl mx-auto">
                Explore our directory of board-certified doctors, check available schedule slots, and book your consultation in minutes.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                <a href="doctors.php" class="w-full sm:w-auto px-7 py-3.5 bg-white hover:bg-blue-50 text-[#1E3A8A] text-sm font-bold rounded-xl shadow-lg transition-all">
                    Find a Doctor
                </a>
                <a href="register.php" class="w-full sm:w-auto px-7 py-3.5 bg-blue-700/60 hover:bg-blue-700 text-white text-sm font-bold rounded-xl border border-blue-400/40 transition-all">
                    Register as a Patient
                </a>
            </div>
        </div>
    </section>

</main>

<?php require_once 'footer.php'; ?>

