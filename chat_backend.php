<?php
/**
 * SmartCare - Real-time AJAX Chat Backend
 * 
 * Handles fetching messages (JSON) and sending new messages securely via prepared statements.
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Access Control: Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please login.']);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

// Auto-create messages table if missing
try {
    $conn->query("CREATE TABLE IF NOT EXISTS `messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `appointment_id` INT NOT NULL,
        `sender_id` INT NOT NULL,
        `receiver_id` INT NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_messages_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (Throwable $e) {
    // Table already exists
}

/**
 * Validate appointment membership and determine participant receiver_id
 */
function validate_chat_membership($conn, $appointment_id, $user_id) {
    $stmt = $conn->prepare("SELECT a.id, a.patient_id, a.doctor_id, d.user_id as doctor_user_id 
                            FROM appointments a 
                            JOIN doctors d ON a.doctor_id = d.id 
                            WHERE a.id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $appt = $res->fetch_assoc();
    $stmt->close();

    if (!$appt) return false;

    if ($user_id == $appt['patient_id']) {
        return ['receiver_id' => $appt['doctor_user_id'], 'role' => 'patient'];
    } elseif ($user_id == $appt['doctor_user_id']) {
        return ['receiver_id' => $appt['patient_id'], 'role' => 'doctor'];
    }

    return false;
}

$action = $_REQUEST['action'] ?? 'fetch_messages';
$appointment_id = (int)($_REQUEST['appointment_id'] ?? 0);

if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid appointment ID.']);
    exit;
}

$access = validate_chat_membership($conn, $appointment_id, $current_user_id);
if (!$access) {
    echo json_encode(['success' => false, 'error' => 'Access denied. You are not a participant in this appointment.']);
    exit;
}

// Action 1: Send Message (POST)
if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message text cannot be empty.']);
        exit;
    }

    $receiver_id = $access['receiver_id'];

    $ins_stmt = $conn->prepare("INSERT INTO messages (appointment_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
    $ins_stmt->bind_param("iiis", $appointment_id, $current_user_id, $receiver_id, $message);

    if ($ins_stmt->execute()) {
        $msg_id = $conn->insert_id;
        $ins_stmt->close();

        // Send an In-App notification to receiver
        $sender_name = $_SESSION['user_name'] ?? 'Someone';
        $notif_text = "New chat message from {$sender_name} for Appointment #{$appointment_id}.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notif_stmt->bind_param("is", $receiver_id, $notif_text);
        $notif_stmt->execute();
        $notif_stmt->close();

        echo json_encode(['success' => true, 'message_id' => $msg_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save message to database.']);
    }
    exit;
}

// Action 2: Fetch Messages (GET or POST)
if ($action === 'fetch_messages') {
    // Mark unread messages sent to me as read
    $read_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE appointment_id = ? AND receiver_id = ?");
    $read_stmt->bind_param("ii", $appointment_id, $current_user_id);
    $read_stmt->execute();
    $read_stmt->close();

    // Fetch conversation
    $msg_stmt = $conn->prepare("SELECT m.id, m.sender_id, m.message, m.created_at, u.name as sender_name 
                                FROM messages m 
                                JOIN users u ON m.sender_id = u.id 
                                WHERE m.appointment_id = ? 
                                ORDER BY m.created_at ASC, m.id ASC");
    $msg_stmt->bind_param("i", $appointment_id);
    $msg_stmt->execute();
    $msg_res = $msg_stmt->get_result();

    $messages = [];
    if ($msg_res) {
        while ($row = $msg_res->fetch_assoc()) {
            $messages[] = [
                'id' => (int)$row['id'],
                'sender_id' => (int)$row['sender_id'],
                'sender_name' => $row['sender_name'],
                'message' => htmlspecialchars($row['message']),
                'created_at' => date('h:i A', strtotime($row['created_at'])),
                'is_mine' => ((int)$row['sender_id'] === $current_user_id)
            ];
        }
    }
    $msg_stmt->close();

    echo json_encode([
        'success' => true,
        'user_id' => $current_user_id,
        'messages' => $messages
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action specified.']);
