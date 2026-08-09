<?php
/**
 * SmartCare - Shared Header & Navigation Component
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';

// Determine active page filename for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-[#F8FAFC]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - SmartCare' : 'SmartCare - Modern Healthcare & Doctor Appointments' ?></title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f3ff',
                            100: '#e1e7ff',
                            600: '#25459e',
                            800: '#1E3A8A', // Primary dark blue
                            900: '#172e6e',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col bg-[#F8FAFC] text-slate-800 antialiased">

    <!-- Header Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo -->
                <div class="flex items-center gap-8">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-[#1E3A8A] text-white flex items-center justify-center shadow-sm group-hover:bg-[#172e6e] transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-slate-900 tracking-tight">SmartCare</span>
                    </a>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden md:flex items-center gap-1">
                        <a href="index.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $current_page === 'index.php' ? 'text-[#1E3A8A] bg-blue-50/70 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                            Home
                        </a>
                        <a href="doctors.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $current_page === 'doctors.php' ? 'text-[#1E3A8A] bg-blue-50/70 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                            Doctors
                        </a>
                        <a href="about.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $current_page === 'about.php' ? 'text-[#1E3A8A] bg-blue-50/70 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                            About
                        </a>
                    </nav>
                </div>

                <!-- Session Buttons / Action Items -->
                <div class="hidden sm:flex items-center gap-3">
                    <?php if ($is_logged_in): ?>
                        <!-- User logged in state -->
                        <div class="flex items-center gap-3">
                            <a href="dashboard.php" class="px-4 py-2 text-sm font-medium text-[#1E3A8A] bg-blue-50 hover:bg-blue-100/80 rounded-lg transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            <a href="logout.php" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                                Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- User logged out state -->
                        <a href="login.php" class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                            Login
                        </a>
                        <a href="register.php" class="px-4.5 py-2 text-sm font-medium text-white bg-[#1E3A8A] hover:bg-[#172e6e] rounded-lg shadow-sm transition-colors">
                            Register
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center gap-2">
                    <button type="button" onclick="toggleMobileMenu()" class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-200 px-4 pt-3 pb-4 space-y-2 bg-white">
            <a href="index.php" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-slate-50">Home</a>
            <a href="doctors.php" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-slate-50">Doctors</a>
            <a href="about.php" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-slate-50">About</a>
            <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="w-full text-center px-4 py-2 text-sm font-medium text-[#1E3A8A] bg-blue-50 rounded-lg">Dashboard</a>
                    <a href="logout.php" class="w-full text-center px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="w-full text-center px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg">Login</a>
                    <a href="register.php" class="w-full text-center px-4 py-2 text-sm font-medium text-white bg-[#1E3A8A] rounded-lg">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>
