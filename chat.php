<?php
/**
 * SmartCare - Real-Time Consultation Chat Interface
 * 
 * Responsive AJAX-based chat interface for Patients and Doctors.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Access Control: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$current_role = $_SESSION['user_role'] ?? '';

$appointment_id = (int)($_GET['appointment_id'] ?? 0);

if ($appointment_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Fetch appointment & participant details
$query = "SELECT a.id as appointment_id, a.date, a.time_slot, a.status, a.meeting_link,
                 pu.id as patient_user_id, pu.name as patient_name, pu.email as patient_email,
                 du.id as doctor_user_id, du.name as doctor_name, d.specialty, d.phone as doctor_phone
          FROM appointments a
          JOIN users pu ON a.patient_id = pu.id
          JOIN doctors d ON a.doctor_id = d.id
          JOIN users du ON d.user_id = du.id
          WHERE a.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();
$appt = $res->fetch_assoc();
$stmt->close();

if (!$appt) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Appointment Not Found</h2><a href='dashboard.php'>Go to Dashboard</a></div>");
}

// Verify that the logged in user is either the patient or doctor
if ($current_user_id !== (int)$appt['patient_user_id'] && $current_user_id !== (int)$appt['doctor_user_id']) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>You are not a participant in this consultation chat.</p><a href='dashboard.php'>Go to Dashboard</a></div>");
}

// Determine partner name and title
if ($current_user_id === (int)$appt['patient_user_id']) {
    $partner_name = "Dr. " . $appt['doctor_name'];
    $partner_role = $appt['specialty'];
    $back_url = "patient_dashboard.php";
} else {
    $partner_name = $appt['patient_name'];
    $partner_role = "Patient";
    $back_url = "doctor_dashboard.php";
}

$page_title = "Chat with " . $partner_name;
require_once 'header.php';
?>

<main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 py-6 w-full flex flex-col">

    <!-- Chat Card Container -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden h-[600px] w-full">

        <!-- 1. Chat Header Bar -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <a href="<?= $back_url ?>" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition-colors" title="Back to Dashboard">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>

                <!-- Participant Avatar -->
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-[#1E3A8A] text-white flex items-center justify-center font-bold text-sm">
                        <?= strtoupper(substr($partner_name, 0, 1)) ?>
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white" title="Active Consultation"></span>
                </div>

                <div>
                    <h2 class="text-base font-bold text-slate-900 leading-tight"><?= htmlspecialchars($partner_name) ?></h2>
                    <p class="text-xs text-slate-500 font-medium"><?= htmlspecialchars($partner_role) ?> | Appt #<?= $appt['appointment_id'] ?></p>
                </div>
            </div>

            <!-- Actions Right (Video Call Link & Details) -->
            <div class="flex items-center gap-3">
                <?php if (!empty($appt['meeting_link'])): ?>
                    <a href="<?= htmlspecialchars($appt['meeting_link']) ?>" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Video Call
                    </a>
                <?php endif; ?>

                <div class="text-right hidden md:block border-l border-slate-200 pl-3">
                    <span class="text-[10px] text-slate-400 block uppercase font-semibold">Slot Date</span>
                    <span class="text-xs font-semibold text-slate-700"><?= date('M d', strtotime($appt['date'])) ?> (<?= htmlspecialchars($appt['time_slot']) ?>)</span>
                </div>
            </div>
        </div>

        <!-- 2. Scrollable Message Container -->
        <div id="chat-messages" class="flex-grow p-6 overflow-y-auto bg-slate-50/50 space-y-4">
            <!-- Loading Indicator -->
            <div id="chat-loading" class="flex items-center justify-center py-10 text-slate-400 text-xs gap-2">
                <svg class="animate-spin w-4 h-4 text-[#1E3A8A]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Connecting to consultation room...
            </div>
        </div>

        <!-- 3. Bottom Input Bar -->
        <form id="chat-form" class="p-4 bg-white border-t border-slate-200 flex items-center gap-3 shrink-0">
            <input type="hidden" name="action" value="send_message">
            <input type="hidden" name="appointment_id" value="<?= $appointment_id ?>">

            <div class="relative flex-grow">
                <input type="text" id="chat-input" name="message" required autocomplete="off"
                    placeholder="Type your message to <?= htmlspecialchars($partner_name) ?>..."
                    class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:bg-white text-sm transition-all">
            </div>

            <button type="submit" id="chat-send-btn" class="px-5 py-3 bg-[#1E3A8A] hover:bg-[#172e6e] active:scale-95 text-white text-sm font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                <span>Send</span>
                <svg class="w-4 h-4 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>

    </div>

</main>

<!-- AJAX Polling & Messaging Script -->
<script>
    const appointmentId = <?= $appointment_id ?>;
    const currentUserId = <?= $current_user_id ?>;
    const chatContainer = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const loadingEl = document.getElementById('chat-loading');

    let isFirstLoad = true;
    let lastMessageCount = 0;

    /**
     * Render message bubbles cleanly
     */
    function renderMessages(messages) {
        if (loadingEl) loadingEl.remove();

        if (!messages || messages.length === 0) {
            chatContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-slate-400 text-xs py-12">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="font-semibold text-slate-600">No messages yet</p>
                    <p class="mt-1">Start the live consultation chat by typing below.</p>
                </div>
            `;
            return;
        }

        const shouldScroll = isFirstLoad || (chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight < 120);

        let html = '';
        messages.forEach(msg => {
            if (msg.is_mine) {
                // Sent by me (Right Aligned - Dark Blue)
                html += `
                    <div class="flex justify-end">
                        <div class="max-w-[78%] sm:max-w-[65%]">
                            <div class="bg-[#1E3A8A] text-white px-4 py-2.5 rounded-2xl rounded-tr-none shadow-sm text-sm leading-relaxed break-words">
                                ${msg.message}
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block text-right font-medium">${msg.created_at}</span>
                        </div>
                    </div>
                `;
            } else {
                // Received from partner (Left Aligned - White)
                html += `
                    <div class="flex justify-start">
                        <div class="max-w-[78%] sm:max-w-[65%]">
                            <div class="bg-white border border-slate-200 text-slate-800 px-4 py-2.5 rounded-2xl rounded-tl-none shadow-sm text-sm leading-relaxed break-words">
                                <div class="text-[10px] font-bold text-[#1E3A8A] mb-0.5">${msg.sender_name}</div>
                                ${msg.message}
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block font-medium">${msg.created_at}</span>
                        </div>
                    </div>
                `;
            }
        });

        chatContainer.innerHTML = html;

        if (shouldScroll) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
            isFirstLoad = false;
        }
    }

    /**
     * AJAX Polling to Fetch Messages
     */
    function fetchMessages() {
        fetch(`chat_backend.php?action=fetch_messages&appointment_id=${appointmentId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.messages);
                }
            })
            .catch(err => console.error('Chat polling error:', err));
    }

    /**
     * Submit Form via AJAX POST
     */
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const messageText = chatInput.value.trim();
        if (!messageText) return;

        const formData = new FormData(chatForm);

        fetch('chat_backend.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                chatInput.value = '';
                isFirstLoad = true; // Force scroll to bottom on sent message
                fetchMessages();
            } else {
                alert('Error sending message: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Send message error:', err);
            alert('Network error. Could not send message.');
        });
    });

    // Initial Fetch and 2-Second Polling Loop
    fetchMessages();
    setInterval(fetchMessages, 2000);
</script>

<?php require_once 'footer.php'; ?>
