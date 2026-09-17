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
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
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
    
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
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

<?php
// Handle Notification Read Actions if logged in
$notifications = [];
$unread_count = 0;

if ($is_logged_in && isset($conn)) {
    // Ensure notifications table exists
    @$conn->query("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $user_id = $_SESSION['user_id'];

    // Handle Mark Single Notification as Read
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_notif_read') {
        $notif_id = (int)($_POST['notif_id'] ?? 0);
        if ($notif_id > 0) {
            $read_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $read_stmt->bind_param("ii", $notif_id, $user_id);
            $read_stmt->execute();
            $read_stmt->close();
        }
    }

    // Handle Mark All as Read
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_notif_read') {
        $read_all_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $read_all_stmt->bind_param("i", $user_id);
        $read_all_stmt->execute();
        $read_all_stmt->close();
    }

    // Fetch Unread Count
    $cnt_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
    $cnt_stmt->bind_param("i", $user_id);
    $cnt_stmt->execute();
    $unread_count = $cnt_stmt->get_result()->fetch_assoc()['unread'] ?? 0;
    $cnt_stmt->close();

    // Fetch Latest 6 Notifications
    $notif_stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
    $notif_stmt->bind_param("i", $user_id);
    $notif_stmt->execute();
    $notif_res = $notif_stmt->get_result();
    if ($notif_res) {
        while ($row = $notif_res->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    $notif_stmt->close();
}
?>

                <!-- Session Buttons / Action Items -->
                <div class="hidden sm:flex items-center gap-3">
                    <?php if ($is_logged_in): ?>
                        <!-- User logged in state -->
                        <div class="flex items-center gap-3">

                            <!-- Notification Bell Icon & Dropdown -->
                            <div class="relative">
                                <button type="button" onclick="toggleNotifDropdown()" class="relative p-2.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors focus:outline-none">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>

                                    <!-- Unread Badge Counter -->
                                    <?php if ($unread_count > 0): ?>
                                        <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-extrabold text-white shadow-sm ring-2 ring-white">
                                            <?= $unread_count > 9 ? '9+' : $unread_count ?>
                                        </span>
                                    <?php endif; ?>
                                </button>

                                <!-- Notification Dropdown Menu -->
                                <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 overflow-hidden">
                                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Notifications</h3>
                                            <?php if ($unread_count > 0): ?>
                                                <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-[#1E3A8A] rounded-full">
                                                    <?= $unread_count ?> new
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($unread_count > 0): ?>
                                            <form action="" method="POST" class="inline">
                                                <input type="hidden" name="action" value="mark_all_notif_read">
                                                <button type="submit" class="text-[11px] font-semibold text-[#1E3A8A] hover:underline">
                                                    Mark all as read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Notification List -->
                                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                                        <?php if (!empty($notifications)): ?>
                                            <?php foreach ($notifications as $n): ?>
                                                <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start justify-between gap-3 <?= $n['is_read'] ? 'opacity-70' : 'bg-blue-50/30' ?>">
                                                    <div class="flex-grow">
                                                        <p class="text-xs text-slate-800 leading-snug <?= $n['is_read'] ? '' : 'font-semibold' ?>">
                                                            <?= htmlspecialchars($n['message']) ?>
                                                        </p>
                                                        <span class="text-[10px] text-slate-400 mt-1 block">
                                                            <?= date('M d, h:i A', strtotime($n['created_at'])) ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!$n['is_read']): ?>
                                                        <form action="" method="POST" class="shrink-0">
                                                            <input type="hidden" name="action" value="mark_notif_read">
                                                            <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                                            <button type="submit" title="Mark as read" class="p-1 text-slate-400 hover:text-blue-600 rounded-md">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="p-6 text-center text-xs text-slate-400">
                                                No notifications yet.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

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
            if (menu) menu.classList.toggle('hidden');
        }

        function toggleNotifDropdown() {
            const dropdown = document.getElementById('notif-dropdown');
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notif-dropdown');
            const btn = e.target.closest('button[onclick="toggleNotifDropdown()"]');
            if (dropdown && !dropdown.contains(e.target) && !btn) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
