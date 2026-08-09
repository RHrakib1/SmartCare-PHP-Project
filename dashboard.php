<?php
/**
 * SmartCare - Main Dashboard Router
 * 
 * Redirects authenticated users to their role-specific dashboard panel.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['user_role'] ?? 'patient';

if ($role === 'doctor') {
    header("Location: doctor_dashboard.php");
    exit;
} elseif ($role === 'admin') {
    header("Location: admin_panel.php");
    exit;
} else {
    header("Location: patient_dashboard.php");
    exit;
}
