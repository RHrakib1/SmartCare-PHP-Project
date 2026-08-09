<?php
/**
 * SmartCare - Shared Footer Component
 */
?>
    <!-- Footer Section -->
    <footer class="mt-auto bg-white border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand Info -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#1E3A8A] text-white flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-slate-900">SmartCare</span>
                    </div>
                    <p class="text-sm text-slate-500 max-w-sm leading-relaxed">
                        Connecting patients with top healthcare professionals for seamless online appointment scheduling and care management.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Quick Links</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="index.php" class="text-slate-600 hover:text-[#1E3A8A] transition-colors">Home</a></li>
                        <li><a href="doctors.php" class="text-slate-600 hover:text-[#1E3A8A] transition-colors">Find Doctors</a></li>
                        <li><a href="about.php" class="text-slate-600 hover:text-[#1E3A8A] transition-colors">About SmartCare</a></li>
                    </ul>
                </div>

                <!-- Account -->
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Account Access</h3>
                    <ul class="space-y-2.5 text-sm">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="dashboard.php" class="text-slate-600 hover:text-[#1E3A8A] transition-colors">My Dashboard</a></li>
                            <li><a href="logout.php" class="text-slate-600 hover:text-[#1E3A8A] transition-colors">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="text-slate-600 hover:text-[#1E3A8A] transition-colors">Patient / Doctor Login</a></li>
                            <li><a href="register.php" class="text-slate-600 hover:text-[#1E3A8A] transition-colors">Create an Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                <p>&copy; <?= date('Y') ?> SmartCare Inc. All rights reserved.</p>
                <p>Designed for excellence in healthcare access.</p>
            </div>
        </div>
    </footer>

</body>
</html>
