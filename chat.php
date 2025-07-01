<?php
session_start();
include 'db.php';
include 'conf.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentUserId = $_SESSION['user_id'];

/* -----------------------------------------------------------
   AJAX Endpoints:
   - ajax=contacts: Return all conversations (contacts) with unread counts (only if there is at least one visible message).
   - ajax=deleteChat: Mark all messages in a conversation as deleted for the current user (and mark them as read).
   - ajax=1: Send and load messages for a conversation.
   ----------------------------------------------------------- */
if (isset($_GET['ajax'])) {
    // 1. Contacts List Endpoint
    if ($_GET['ajax'] == 'contacts') {
        header('Content-Type: application/json');
        // This query groups messages into conversations and computes:
        // - visible_messages: count of messages not flagged as deleted for the current user.
        // - unread_count: messages sent to current user that are unread and not deleted.
        $sqlContacts = "SELECT 
            CASE WHEN m.from_user_id = ? THEN m.to_user_id ELSE m.from_user_id END AS contact_id,
            u.username, 
            SUM(
              CASE 
                WHEN m.from_user_id = ? AND (IFNULL(m.deleted_by_sender, 0) = 0) THEN 1 
                WHEN m.to_user_id = ? AND (IFNULL(m.deleted_by_receiver, 0) = 0) THEN 1 
                ELSE 0 
              END
            ) AS visible_messages,
            SUM(
              CASE WHEN m.to_user_id = ? AND m.is_read = 0 AND (IFNULL(m.deleted_by_receiver, 0) = 0)
              THEN 1 ELSE 0 END
            ) AS unread_count
            FROM messages m
            JOIN users u ON u.id = (CASE WHEN m.from_user_id = ? THEN m.to_user_id ELSE m.from_user_id END)
            WHERE m.from_user_id = ? OR m.to_user_id = ?
            GROUP BY contact_id, u.username
            HAVING visible_messages > 0";
        $stmtContacts = $conn->prepare($sqlContacts);
        // Bind parameters: using $currentUserId seven times.
        $stmtContacts->bind_param('iiiiiii', 
            $currentUserId, // For CASE in SELECT
            $currentUserId, // For visible_messages: sender part
            $currentUserId, // For visible_messages: receiver part
            $currentUserId, // For unread_count: recipient condition
            $currentUserId, // For join CASE in users table
            $currentUserId, // For WHERE: m.from_user_id = ?
            $currentUserId  // For WHERE: m.to_user_id = ?
        );
        $stmtContacts->execute();
        $resultContacts = $stmtContacts->get_result();
        $contacts = [];
        while ($row = $resultContacts->fetch_assoc()) {
            $contacts[] = $row;
        }
        echo json_encode(['contacts' => $contacts]);
        exit;
    }
    
    // 2. Delete Conversation Endpoint
    if ($_GET['ajax'] == 'deleteChat') {
        header('Content-Type: application/json');
        if (!isset($_POST['contact_id'])) {
            echo json_encode(['error' => 'No contact_id provided.']);
            exit;
        }
        $contactId = (int) $_POST['contact_id'];
        // Mark messages sent by the current user as deleted for them
        $sqlUpdateSender = "UPDATE messages SET deleted_by_sender = 1, is_read = 1
                            WHERE from_user_id = ? AND to_user_id = ?";
        $stmtSender = $conn->prepare($sqlUpdateSender);
        $stmtSender->bind_param('ii', $currentUserId, $contactId);
        $stmtSender->execute();
        // Mark messages received by the current user as deleted for them
        $sqlUpdateReceiver = "UPDATE messages SET deleted_by_receiver = 1, is_read = 1
                              WHERE from_user_id = ? AND to_user_id = ?";
        $stmtReceiver = $conn->prepare($sqlUpdateReceiver);
        $stmtReceiver->bind_param('ii', $contactId, $currentUserId);
        $stmtReceiver->execute();
        echo json_encode(['status' => 'ok']);
        exit;
    }
    
    // 3. Chat Messages Endpoint (ajax=1)
    if ($_GET['ajax'] == '1') {
        header('Content-Type: application/json');
        if (!isset($_GET['seller_id'])) {
            echo json_encode(['error' => 'No seller_id provided.']);
            exit;
        }
        $otherUserId = (int) $_GET['seller_id'];
    
        // If sending a new message:
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sendMessage') {
            $message = trim($_POST['message'] ?? '');
            if ($message !== '') {
                $sql = "INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iis', $currentUserId, $otherUserId, $message);
                $stmt->execute();
            }
            echo json_encode(['status' => 'ok']);
            exit;
        }
    
        // Load messages between the two users that are not deleted for the current user.
        $sqlChat = "SELECT * FROM messages
                    WHERE ((from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?))
                      AND (
                        (from_user_id = ? AND (deleted_by_sender IS NULL OR deleted_by_sender = 0))
                        OR (to_user_id = ? AND (deleted_by_receiver IS NULL OR deleted_by_receiver = 0))
                      )
                    ORDER BY created_at ASC";
        $stmtChat = $conn->prepare($sqlChat);
        $stmtChat->bind_param('iiiiii', 
            $currentUserId, $otherUserId,
            $otherUserId, $currentUserId,
            $currentUserId, $currentUserId
        );
        $stmtChat->execute();
        $resultChat = $stmtChat->get_result();
        $messages = [];
        while ($row = $resultChat->fetch_assoc()) {
            $sender = ($row['from_user_id'] == $currentUserId) ? 'you' : 'them';
            $messages[] = [
                'sender' => $sender,
                'text'   => $row['message'],
                'time'   => $row['created_at']
            ];
        }
    
        // Mark as read any messages sent to the current user from the other user that are unread and not deleted.
        $sqlUpdateRead = "UPDATE messages SET is_read = 1 
                          WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0 
                          AND (deleted_by_receiver IS NULL OR deleted_by_receiver = 0)";
        $stmtUpdate = $conn->prepare($sqlUpdateRead);
        $stmtUpdate->bind_param('ii', $currentUserId, $otherUserId);
        $stmtUpdate->execute();
    
        echo json_encode(['messages' => $messages]);
        exit;
    }
}

/* -----------------------------------------------------------
   2) Chat Interface When a seller_id is provided (Conversation View)
   ----------------------------------------------------------- */
if (isset($_GET['seller_id'])) {
    $otherUserId = (int) $_GET['seller_id'];
    // Get the other user's username.
    $sqlUser = "SELECT username FROM users WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param('i', $otherUserId);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $otherUser = $resultUser->fetch_assoc();
    $otherUsername = $otherUser ? $otherUser['username'] : 'Unknown';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Chat with <?php echo htmlspecialchars($otherUsername); ?></title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="icon" href="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" type="image/x-icon">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
        body {
  background-color: white !important;
}

html {
  background-color: white !important;
}



.container, .content, .main, #root, #app, main, section {
  background-color: white !important;
}
:root {
  --primary-color: #1A5D1A;
  --primary-light: #2a8d2a;
  --primary-dark: #124012;
  --secondary-color: #23e5db;
  --light-grey: #f8f9fa;
  --medium-grey: #e9ecef;
  --dark-grey: #6c757d;
  --white: #ffffff;
  --shadow: 0 2px 10px rgba(0,0,0,0.08);
  --border-radius: 16px;
  --border-radius-sm: 12px;
  --transition-speed: 0.3s;
}

/* Body Settings */
/* Body Settings */
body {

  background-color: var(--light-grey);
  padding-bottom: 70px;
  margin: 0;
  color: #333;
  height: 100%;
  width: 100%;
  overflow-x: hidden;
  padding-top: 100px;
  position: fixed;
  max-width: 100%;
}

/* Navbar/Header Styling */
.navbar {
  background-color: white !important;
  padding: 10px 15px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  display: flex;
  flex-direction: column;
  height: auto;
}

.navbar-brand {
  display: flex;
  align-items: center;
  margin-right: auto;
  padding: 0;
  font-weight: bold;
  color: var(--primary-color) !important;
  text-decoration: none !important;
  font-size: 18px;
}

.navbar-brand:hover {
  color: var(--primary-light) !important;
}

.navbar-brand img {
  height: 30px !important;
  margin-right: 10px;
}

.navbar-toggler {
  display: none;
}

.navbar-collapse {
  display: flex !important;
  flex-direction: column;
  width: 100%;
  margin-top: 8px;
}

/* Search Form */
.form-inline {
  width: 100%;
  padding-top: 8px;
  position: relative;
}

.form-inline input[name="location"],
.form-inline input[name="city"] {
  display: none;
}

.form-inline::before {
  content: attr(data-location);
  position: absolute;
  top: -30px;
  right: 15px;
  display: flex;
  align-items: center;
  font-weight: 500;
  color: var(--primary-color);
  cursor: pointer;
  font-size: 14px;
}

.form-inline:not([data-location])::before {
  content: "Select Location ▼";
}

.form-inline::after {
  content: "\f3c5";
  font-family: "Font Awesome 5 Free";
  font-weight: 900;
  position: absolute;
  top: -30px;
  right: calc(100% - 135px);
  color: var(--primary-color);
  font-size: 14px;
}

.form-inline input[name="q"] {
  flex: 1;
  border-radius: 50px;
  border: 1px solid #ddd;
  background-color: #f2f4f5;
  padding: 12px 20px 12px 45px;
  font-size: 16px;
  transition: all 0.2s ease;
  width: 100%;
}

.form-inline input[name="q"]:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(26, 93, 26, 0.1);
  outline: none;
}

.form-inline button {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  padding: 0;
  color: #888;
}

.form-inline button img {
  height: 18px;
  opacity: 0.7;
}

/* Bottom Navigation */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 60px;
  background-color: white;
  display: flex;
  justify-content: space-around;
  align-items: center;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
  z-index: 200; /* Updated z-index to 200 */
  padding-bottom: env(safe-area-inset-bottom);
}

.bottom-nav a {
  color: #002f34;
  text-decoration: none !important;
  font-size: 12px;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px 0;
  transition: color 0.2s ease;
}

.bottom-nav a:hover {
  color: var(--primary-light);
}

.bottom-nav .nav-icon {
  display: block;
  font-size: 20px;
  margin-bottom: 4px;
}

/* Center circle for +SELL */
.sell-btn-wrapper {
  position: relative;
  flex: 1;
  display: flex;
  justify-content: center;
  height: 100%;
}

.sell-btn {
  position: absolute;
  bottom: 15px;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white !important;
  border: 4px solid white;
  font-size: 14px;
  font-weight: bold;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.sell-btn:hover {
  background-color: var(--primary-light);
  transform: scale(1.05);
}

.sell-btn::before {
  content: "+";
  font-size: 26px;
  line-height: 1;
  margin-bottom: -2px;
}

/* Badge styling */
.badge-danger {
  background-color: #23e5db !important;
  color: #002f34 !important;
  border-radius: 50%;
  min-width: 18px;
  height: 18px;
  font-size: 10px !important;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  font-weight: normal;
  position: absolute;
  top: 0;
  right: 68%;
}

/* Location Modal */
.location-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 9999999999999999; /* Uniform z-index */
}

.location-modal.show {
  display: block !important;
  animation: fadeIn 0.3s forwards;
  z-index: 9999999999999999;
}

.location-modal-content {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background-color: #ffffff;
  border-radius: 20px 20px 0 0;
  padding: 25px 20px;
  box-shadow: 0 -5px 25px rgba(0,0,0,0.15);
  z-index: 9999999999999999;
  max-height: 80vh;
  overflow-y: auto;
  transform: translateY(0);
}

.location-modal.show .location-modal-content {
  animation: slideUp 0.3s forwards;
  transform: translateY(0) !important;
  opacity: 1;
  visibility: visible;
  z-index: 9999999999999999;
}

.location-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.location-modal-title {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  color: var(--primary-color);
}

.location-close-btn {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #666;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  padding: 0;
  margin: 0;
}

.location-form select {
  width: 100%;
  padding: 14px;
  border: 1px solid #ddd;
  border-radius: 12px;
  margin-bottom: 20px;
  appearance: none;
  font-size: 16px;
  background-color: #f8f9fa;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888888' d='M6 8.825c-.2 0-.4-.1-.5-.2l-3.5-3.5c-.3-.3-.3-.8 0-1.1.3-.3.8-.3 1.1 0l2.9 2.9 2.9-2.9c.3-.3.8-.3 1.1 0 .3.3.3.8 0 1.1l-3.5 3.5c-.1.1-.3.2-.5.2z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 15px center;
  background-size: 12px;
}

.location-form label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #444;
  font-size: 15px;
}

.location-form button {
  width: 100%;
  padding: 15px;
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 500;
  font-size: 16px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(26, 93, 26, 0.2);
}

.location-modal-content::before {
  content: "";
  display: block;
  width: 40px;
  height: 4px;
  background-color: #ddd;
  border-radius: 4px;
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

/* Chat Interface Specific Styling */
.container {
  max-width: 100% !important;
  width: 100% !important;
  padding: 15px;
}

h2 {
  color: var(--primary-dark);
  margin: 20px 0;
  font-weight: 600;
  font-size: 22px;
  animation: fadeIn 0.4s ease-out;
}

/* Conversation list styles */
.list-group {
  border-radius: var(--border-radius);
  overflow: hidden;
  box-shadow: var(--shadow);
  background: var(--white);
  margin-bottom: 20px;

}

.list-group-item {
  border: none;
  border-bottom: 1px solid var(--medium-grey);
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;

 
}



.list-group-item:last-child {
  border-bottom: none;
}

.list-group-item:hover {
  background-color: rgba(26, 93, 26, 0.05);
  transform: translateX(5px);
}

.list-group-item span:first-child {
  font-weight: 500;
  font-size: 16px;
}

.list-group-item .btn {
  border-radius: 50px;
  padding: 6px 12px;
  font-size: 14px;
  font-weight: 500;
  margin-left: 8px;
  transition: all 0.2s ease;
  border: none;
}

.btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
}

.btn-primary:hover, .btn-primary:focus {
  background-color: var(--primary-light);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(26, 93, 26, 0.2);
}

.btn-danger {
  background-color: white;
  color: #dc3545;
  border: 1px solid #dc3545;
}

.btn-danger:hover {
  background-color: #dc3545;
  color: white;
  transform: scale(1.05);
}

.btn-sm {
  padding: 5px 10px;
  font-size: 12px;
}

/* Chat box for conversations */
#chatBox {
  height: calc(100vh - 260px);
  border-radius: var(--border-radius);
  background-color: var(--white);
  box-shadow: var(--shadow);
  padding: 15px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  margin-bottom: 15px;
  scroll-behavior: smooth;
 
}

.message-line {
  max-width: 75%;
  padding: 12px 16px;
  border-radius: 18px;
  margin-bottom: 8px;
  position: relative;
  word-break: break-word;
  line-height: 1.4;
  font-size: 15px;
  box-shadow: 0 1px 1px rgba(0,0,0,0.05);

}

.message-line.you {
  align-self: flex-end;
  background-color: var(--primary-color);
  color: white;
  border-bottom-right-radius: 4px;

}

.message-line.them {
  align-self: flex-start;
  background-color: var(--medium-grey);
  color: #333;
  border-bottom-left-radius: 4px;
 
}

/* Message input styling */
#chatForm {
  display: grid;
  grid-template-columns: 1fr auto;
  grid-gap: 10px;
  align-items: center;

}

#messageInput {
  border-radius: 24px;
  padding: 12px 20px;
  border: 1px solid var(--medium-grey);
  background-color: var(--white);
  resize: none;
  font-size: 15px;
  transition: all 0.25s ease;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  height: 50px;
  margin-bottom: 0;
}

#messageInput:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
  outline: none;
  transform: translateY(-2px);
}

#chatForm .btn {
  height: 50px;
  padding: 0 20px;
  font-weight: 500;
  font-size: 15px;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-secondary {
  background-color: #f1f3f4;
  color: #333;
  border: none;
}

.btn-secondary:hover {
  background-color: #e2e6ea;
  color: var(--dark-grey);
}

/* No conversations message */
#noConvoMsg {
  text-align: center;
  color: var(--dark-grey);
  background-color: var(--white);
  border-radius: var(--border-radius);
  padding: 30px 20px;
  font-size: 16px;
 

}

/* Button ripple effect */
.btn {
  position: relative;
  overflow: hidden;
}

.btn:after {
  content: "";
  display: block;
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  pointer-events: none;
  background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
  background-repeat: no-repeat;
  background-position: 50%;
  transform: scale(10, 10);
  opacity: 0;
  transition: transform 0.5s, opacity 1s;
}

.btn:active:after {
  transform: scale(0, 0);
  opacity: 0.3;
  transition: 0s;
}

/* Preloader */
.app-loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  transition: opacity 0.5s;
}

.app-loader.fade-out {
  opacity: 0;
  pointer-events: none;
}

.app-loader .spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(26, 93, 26, 0.2);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 0.8s linear infinite;
}

/* Prevent body scrolling when modal is open */
body.modal-open {
  overflow: hidden;
}

/* Animations */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes messageInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes messageInLeft {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Responsive tweaks */
@media (max-width: 768px) {
  .navbar-brand {
    font-size: 16px;
  }
  
  .navbar-brand img {
    height: 24px !important;
  }
  
  .form-inline::before {
    font-size: 12px;
    max-width: 40%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  #chatBox {
    height: calc(100vh - 280px);
  }
  
  .message-line {

max-width: 85%;
    padding: 10px 14px;
    font-size: 14px;
  }
  
  .container {
    padding: 10px;
  }
  
  h2 {
    font-size: 20px;
    margin-top: 0;
  }
  
  .btn {
    padding: 8px 16px;
  }
  
  #chatForm {
    grid-template-columns: 1fr auto;
  }
  
  .btn-secondary {
    margin-top: 8px;
  }
}

/* Custom scrollbar for app-like feel */
::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: #bbb;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #999;
}
        </style>
    </head>
    <body>
        <!-- Preloader -->
<div class="preloader">
    <div class="spinner"></div>
</div>

<style>
.preloader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  transition: opacity 0.5s ease;
}
.preloader.fade-out {
  opacity: 0;
  pointer-events: none;
}
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(26, 93, 26, 0.2);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 0.8s linear infinite;
}
/* Custom scrollbar for app-like feel */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb {
  background: #bbb;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #999;
}
/* Animations */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

<script>
window.addEventListener('load', function() {
  setTimeout(function() {
    document.querySelector('.preloader').classList.add('fade-out');
  }, 500);
});
</script>
    <?php include "navbar.php"; ?>
    <div class="container mt-4" style="margin-top:-20px;">
      <!--  <h2 style="margin-top:-20px; margin-bottom:-0px;">Chat with <?php echo htmlspecialchars($otherUsername); ?></h2>-->
        <div id="chatBox"></div>
        <!-- Send a new message -->
        <form id="chatForm" onsubmit="return sendMessage();">
            <div class="form-group">
                <textarea id="messageInput" class="form-control" placeholder="Type your message..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
          
        </form>
    </div>
    <script>
      let sellerId = <?php echo $otherUserId; ?>;
      // Function to load messages via AJAX
      function loadMessages(){
        fetch(`chat.php?ajax=1&seller_id=${sellerId}`)
          .then(res => res.json())
          .then(data => {
            if(data.messages){
              const chatBox = document.getElementById('chatBox');
              chatBox.innerHTML = '';
              data.messages.forEach(msg => {
                let div = document.createElement('div');
                div.classList.add('message-line', msg.sender);
                div.innerHTML = `<strong>${msg.sender}:</strong> ${msg.text}`;
                chatBox.appendChild(div);
              });
              chatBox.scrollTop = chatBox.scrollHeight;
            }
          })
          .catch(err => console.log(err));
      }
    
      // Function to send a new message via AJAX
      function sendMessage(){
        const messageInput = document.getElementById('messageInput');
        const msg = messageInput.value.trim();
        if(msg === '') return false;
    
        fetch(`chat.php?ajax=1&seller_id=${sellerId}`, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: new URLSearchParams({
            'action': 'sendMessage',
            'message': msg
          })
        })
        .then(res => res.json())
        .then(data => {
          messageInput.value = '';
          loadMessages();
        })
        .catch(err => console.log(err));
    
        return false;
      }
    
      // Poll for new messages every 2 seconds
      setInterval(loadMessages, 2000);
      loadMessages();
    </script>
    
    
    </body>
    </html>
    <?php
    exit;
}

/* -----------------------------------------------------------
   3) Chat Contacts List (No seller_id provided)
   ----------------------------------------------------------- */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Chats</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
  background-color: white !important;
}

html {
  background-color: white !important;
}



.container, .content, .main, #root, #app, main, section {
  background-color: white !important;
}
:root {
  --primary-color: #1A5D1A;
  --primary-light: #2a8d2a;
  --primary-dark: #124012;
  --secondary-color: #23e5db;
  --light-grey: #f8f9fa;
  --medium-grey: #e9ecef;
  --dark-grey: #6c757d;
  --white: #ffffff;
  --shadow: 0 2px 10px rgba(0,0,0,0.08);
  --border-radius: 16px;
  --border-radius-sm: 12px;
  --transition-speed: 0.3s;
}

/* Body Settings */
/* Body Settings */
body {

  background-color: var(--light-grey);
  padding-bottom: 70px;
  margin: 0;
  color: #333;
  height: 100%;
  width: 100%;
  overflow-x: hidden;
  padding-top: 100px;
  position: fixed;
  max-width: 100%;
}

/* Navbar/Header Styling */
.navbar {
  background-color: white !important;
  padding: 10px 15px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;

  display: flex;
  flex-direction: column;
  height: auto;
}

.navbar-brand {
  display: flex;
  align-items: center;
  margin-right: auto;
  padding: 0;
  font-weight: bold;
  color: var(--primary-color) !important;
  text-decoration: none !important;
  font-size: 18px;
}

.navbar-brand:hover {
  color: var(--primary-light) !important;
}

.navbar-brand img {
  height: 30px !important;
  margin-right: 10px;
}

.navbar-toggler {
  display: none;
}

.navbar-collapse {
  display: flex !important;
  flex-direction: column;
  width: 100%;
  margin-top: 8px;
}

/* Search Form */
.form-inline {
  width: 100%;
  padding-top: 8px;
  position: relative;
}

.form-inline input[name="location"],
.form-inline input[name="city"] {
  display: none;
}

.form-inline::before {
  content: attr(data-location);
  position: absolute;
  top: -30px;
  right: 15px;
  display: flex;
  align-items: center;
  font-weight: 500;
  color: var(--primary-color);
  cursor: pointer;
  font-size: 14px;
}

.form-inline:not([data-location])::before {
  content: "Select Location ▼";
}

.form-inline::after {
  content: "\f3c5";
  font-family: "Font Awesome 5 Free";
  font-weight: 900;
  position: absolute;
  top: -30px;
  right: calc(100% - 135px);
  color: var(--primary-color);
  font-size: 14px;
}

.form-inline input[name="q"] {
  flex: 1;
  border-radius: 50px;
  border: 1px solid #ddd;
  background-color: #f2f4f5;
  padding: 12px 20px 12px 45px;
  font-size: 16px;
  transition: all 0.2s ease;
  width: 100%;
}

.form-inline input[name="q"]:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(26, 93, 26, 0.1);
  outline: none;
}

.form-inline button {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  padding: 0;
  color: #888;
}

.form-inline button img {
  height: 18px;
  opacity: 0.7;
}

/* Bottom Navigation */
/* Bottom Navigation */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 60px;
  background-color: white;
  display: flex;
  justify-content: space-around;
  align-items: center;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
  z-index: 200; /* Updated z-index to 200 */
  padding-bottom: env(safe-area-inset-bottom);
}

.bottom-nav a {
  color: #002f34;
  text-decoration: none !important;
  font-size: 12px;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px 0;
  transition: color 0.2s ease;
}

.bottom-nav a:hover {
  color: var(--primary-light);
}

.bottom-nav .nav-icon {
  display: block;
  font-size: 20px;
  margin-bottom: 4px;
}

/* Center circle for +SELL */
.sell-btn-wrapper {
  position: relative;
  flex: 1;
  display: flex;
  justify-content: center;
  height: 100%;
}

.sell-btn {
  position: absolute;
  bottom: 15px;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white !important;
  border: 4px solid white;
  font-size: 14px;
  font-weight: bold;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.sell-btn:hover {
  background-color: var(--primary-light);
  transform: scale(1.05);
}

.sell-btn::before {
  content: "+";
  font-size: 26px;
  line-height: 1;
  margin-bottom: -2px;
}

/* Badge styling */
.badge-danger {
  background-color: #23e5db !important;
  color: #002f34 !important;
  border-radius: 50%;
  padding: 4px 8px;
  font-weight: normal;
  font-size: 12px;
  animation: pulse 2s infinite;
}
/* Location Modal */
.location-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 9999999999999999; /* Uniform z-index */
}

.location-modal.show {
  display: block !important;
  animation: fadeIn 0.3s forwards;
  z-index: 9999999999999999;
}

.location-modal-content {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background-color: #ffffff;
  border-radius: 20px 20px 0 0;
  padding: 25px 20px;
  box-shadow: 0 -5px 25px rgba(0,0,0,0.15);
  z-index: 9999999999999999;
  max-height: 80vh;
  overflow-y: auto;
  transform: translateY(0);
}

.location-modal.show .location-modal-content {
  animation: slideUp 0.3s forwards;
  transform: translateY(0) !important;
  opacity: 1;
  visibility: visible;
  z-index: 9999999999999999;
}

.location-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.location-modal-title {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  color: var(--primary-color);
}

.location-close-btn {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #666;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  padding: 0;
  margin: 0;
}

.location-form select {
  width: 100%;
  padding: 14px;
  border: 1px solid #ddd;
  border-radius: 12px;
  margin-bottom: 20px;
  appearance: none;
  font-size: 16px;
  background-color: #f8f9fa;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888888' d='M6 8.825c-.2 0-.4-.1-.5-.2l-3.5-3.5c-.3-.3-.3-.8 0-1.1.3-.3.8-.3 1.1 0l2.9 2.9 2.9-2.9c.3-.3.8-.3 1.1 0 .3.3.3.8 0 1.1l-3.5 3.5c-.1.1-.3.2-.5.2z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 15px center;
  background-size: 12px;
}

.location-form label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #444;
  font-size: 15px;
}

.location-form button {
  width: 100%;
  padding: 15px;
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 500;
  font-size: 16px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(26, 93, 26, 0.2);
}

.location-modal-content::before {
  content: "";
  display: block;
  width: 40px;
  height: 4px;
  background-color: #ddd;
  border-radius: 4px;
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

/* Chat Interface Specific Styling */
.container {
  max-width: 100% !important;
  width: 100% !important;
  padding: 15px;
  
}

h2 {
  color: var(--primary-color);
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 24px;
  animation: fadeInDown 0.5s ease-out;
}

/* Conversation list styles */
.list-group {
  border-radius: var(--border-radius);
  overflow: hidden;
  box-shadow: var(--shadow);

}

.list-group-item {
  border: none;
  border-bottom: 1px solid var(--medium-grey);
  padding: 15px;

}

.list-group-item:nth-child(1) { animation-delay: 0.05s; }
.list-group-item:nth-child(2) { animation-delay: 0.1s; }
.list-group-item:nth-child(3) { animation-delay: 0.15s; }
.list-group-item:nth-child(4) { animation-delay: 0.2s; }
.list-group-item:nth-child(5) { animation-delay: 0.25s; }
.list-group-item:nth-child(6) { animation-delay: 0.3s; }
.list-group-item:nth-child(7) { animation-delay: 0.35s; }
.list-group-item:nth-child(8) { animation-delay: 0.4s; }
.list-group-item:nth-child(9) { animation-delay: 0.45s; }
.list-group-item:nth-child(10) { animation-delay: 0.5s; }

.list-group-item:last-child {
  border-bottom: none;
}

.list-group-item:hover {
  background-color: rgba(26, 93, 26, 0.05);
  transform: translateX(5px);
}

.list-group-item .btn {
  border-radius: 50px;
  padding: 8px 15px;
  font-size: 14px;
  margin-left: 8px;
  transition: all 0.3s ease;
  transform: scale(1);
}

.btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
}

.btn-primary:hover {
  background-color: var(--primary-light);
  border-color: var(--primary-light);
  transform: scale(1.05);
}

.btn-danger {
  background-color: #fff;
  border-color: #dc3545;
  color: #dc3545;
}

.btn-danger:hover {
  background-color: #dc3545;
  color: white;
  transform: scale(1.05);
}

/* Button ripple effect */
.btn {
  position: relative;
  overflow: hidden;
}

.btn:after {
  content: "";
  display: block;
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  pointer-events: none;
  background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
  background-repeat: no-repeat;
  background-position: 50%;
  transform: scale(10, 10);
  opacity: 0;
  transition: transform 0.5s, opacity 1s;
}

.btn:active:after {
  transform: scale(0, 0);
  opacity: 0.3;
  transition: 0s;
}

/* No conversations message */
#noConvoMsg {
  text-align: center;
  color: var(--dark-grey);
  padding: 30px;
  background-color: var(--white);
  border-radius: var(--border-radius);


}

/* Prevent body scrolling when modal is open */
body.modal-open {
  overflow: hidden;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Custom scrollbar for app-like feel */
::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: #bbb;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #999;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .navbar-brand {
    font-size: 16px;
  }
  
  .navbar-brand img {
    height: 24px !important;
  }
  
  .form-inline::before {
    font-size: 12px;
    max-width: 40%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .container {
    padding: 10px;
  }
  
  .btn {
    padding: 8px 16px;
  }
  
  h2 {
    font-size: 20px;
  }
  
  .list-group-item {
    padding: 12px;
  }
}
    </style>
</head>
<body>
    <!-- Preloader -->
<div class="preloader">
    <div class="spinner"></div>
</div>

<style>
.preloader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999999999999999999;
  transition: opacity 0.5s ease;
}
.preloader.fade-out {
  opacity: 0;
  pointer-events: none;
}
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(26, 93, 26, 0.2);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 0.8s linear infinite;
}
/* Custom scrollbar for app-like feel */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb {
  background: #bbb;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #999;
}
/* Animations */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

<script>
window.addEventListener('load', function() {
  setTimeout(function() {
    document.querySelector('.preloader').classList.add('fade-out');
  }, 500);
});
</script>
<?php include "navbar.php"; ?>
<div class="container mt-4">
    <h2 style="margin-top:-20px;">Your Chats</h2>
    <!-- The contacts list will be loaded dynamically via AJAX -->
    <ul class="list-group" id="contactsList"></ul>
    <p id="noConvoMsg" class="mt-3" style="display:none;">No conversations yet.</p>
</div>

<!-- JavaScript for loading contacts dynamically and handling deletion -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
  function loadContacts(){
    $.ajax({
        url: 'chat.php?ajax=contacts',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            let html = '';
            if(response.contacts && response.contacts.length > 0){
                response.contacts.forEach(function(contact){
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${contact.username}</span>
                                <span>
                                    <a href="chat.php?seller_id=${contact.contact_id}" class="btn btn-sm btn-primary">Open Chat</a>
                                    <button class="btn btn-sm btn-danger delete-chat" data-contact-id="${contact.contact_id}">
                                        <img src="./files/trash.svg" alt="Delete" style="height:16px; width:auto;">
                                    </button>`;
                    if(contact.unread_count > 0){
                        html += `<span class="badge badge-danger ml-2" style="border-radius: 50%;">${contact.unread_count}</span>`;
                    }
                    html += `   </span>
                             </li>`;
                });
                $('#contactsList').html(html);
                $('#noConvoMsg').hide();
            } else {
                $('#contactsList').html('');
                $('#noConvoMsg').show();
            }
        },
        error: function(xhr, status, error){
            console.log("Error loading contacts: " + error);
        }
    });
  }
  
  // Initial load and set up periodic refresh
  loadContacts();
  setInterval(loadContacts, 5000);
  
  // Delete chat handler
  $(document).on('click', '.delete-chat', function(){
      if(!confirm("Are you sure you want to delete this conversation? This will hide all previous messages.")){
          return;
      }
      let btn = $(this);
      let contactId = btn.data('contact-id');
      $.ajax({
          url: 'chat.php?ajax=deleteChat',
          method: 'POST',
          dataType: 'json',
          data: { contact_id: contactId },
          success: function(response) {
              if(response.status === 'ok'){
                  // Visual feedback - slide up and fade out the item
                  btn.closest('li').animate({
                      opacity: 0,
                      height: 0,
                      padding: 0
                  }, 300, function() {
                      $(this).remove();
                      loadContacts();
                  });
              } else {
                  alert("Error: " + response.error);
              }
          },
          error: function(xhr, status, error){
              alert("An error occurred: " + error);
          }
      });
  });
  
  // Location modal handling
  function setupLocationModal() {
    const form = document.querySelector('.form-inline');
    if (!form) return;

    const locationInput = form.querySelector('input[name="location"]');
    const cityInput = form.querySelector('input[name="city"]');
    
    if (!locationInput || !cityInput) return;
    
    // Set initial location display
    const location = locationInput.value || '';
    const city = cityInput.value || '';
    
    if (location || city) {
      form.setAttribute('data-location', city ? `${city}, ${location}` : location);
    }
    
    // Create location modal
    const modal = document.querySelector('.location-modal');
    if (!modal) {
      // Create modal if it doesn't exist
      const newModal = document.createElement('div');
      newModal.className = 'location-modal';
      newModal.innerHTML = `
        <div class="location-modal-content">
          <div class="location-modal-header">
            <h3 class="location-modal-title">Select Location</h3>
            <button class="location-close-btn">&times;</button>
          </div>
          <form class="location-form" action="index.php" method="get">
            <label for="modal-state">State</label>
            <select id="modal-state" name="location">
              <option value="">Select State</option>
              ${Array.from(document.querySelectorAll('#states option')).map(opt => 
                `<option value="${opt.value}" ${locationInput.value === opt.value ? 'selected' : ''}>${opt.value}</option>`
              ).join('')}
            </select>
            
            <label for="modal-city">City</label>
            <select id="modal-city" name="city">
              <option value="">Select City</option>
              ${Array.from(document.querySelectorAll('#cities option')).map(opt => 
                `<option value="${opt.value}" ${cityInput.value === opt.value ? 'selected' : ''}>${opt.value}</option>`
              ).join('')}
            </select>
            
            ${document.querySelector('input[name="q"]')?.value ? 
              `<input type="hidden" name="q" value="${document.querySelector('input[name="q"]').value}">` : ''}
            
            <button type="submit">Apply</button>
          </form>
        </div>
      `;
      
      document.body.appendChild(newModal);
      
      // Handle events for the new modal
      setupModalEvents(newModal);
    } else {
      // Update existing modal and its events
      setupModalEvents(modal);
    }
  }
  
  function setupModalEvents(modal) {
    const form = document.querySelector('.form-inline');
    const modalContent = modal.querySelector('.location-modal-content');
    const closeBtn = modal.querySelector('.location-close-btn');
    
    // Function to open modal
    function openLocationModal() {
      modal.style.display = "block";
      modal.style.zIndex = "9999";
      modalContent.style.zIndex = "10000";
      
      // Force repaint
      void modal.offsetWidth;
      
      modal.classList.add('show');
      document.body.classList.add('modal-open');
      
      // Ensure modal content is visible
      modalContent.style.transform = "translateY(0)";
      modalContent.style.opacity = "1";
      modalContent.style.visibility = "visible";
    }
    
    // Function to close modal
    function closeLocationModal() {
      modal.classList.remove('show');
      document.body.classList.remove('modal-open');
      
      // After animation is complete, hide the modal
      setTimeout(() => {
        modal.style.display = "none";
      }, 300);
    }
    
    // Open modal when clicking the location selector
    form.addEventListener('click', function(e) {
      const formBounds = form.getBoundingClientRect();
      const isLocationArea = e.clientY - formBounds.top < 0;
      
      if (isLocationArea && !e.target.matches('input, button')) {
        openLocationModal();
        e.preventDefault();
        e.stopPropagation();
      }
    });
    
    // Close modal with close button
    closeBtn.addEventListener('click', function(e) {
      e.preventDefault();
      closeLocationModal();
    });
    
    // Close when clicking outside
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeLocationModal();
      }
    });
    
    // Prevent modal content clicks from closing
    modalContent.addEventListener('click', function(e) {
      e.stopPropagation();
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('show')) {
        closeLocationModal();
      }
    });
  }

  // Initialize everything when document is ready
  $(document).ready(function() {
    setupLocationModal();
  });
</script>
</body>
</html>