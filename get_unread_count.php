<?php
// get_unread_count.php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread_count' => 0]);
    exit;
}

$currentUserId = $_SESSION['user_id'];

$sqlUnread = "SELECT COUNT(*) AS unread_count 
              FROM messages 
              WHERE to_user_id = ? 
                AND is_read = 0 
                AND (IFNULL(deleted_by_receiver, 0) = 0)";
$stmt = $conn->prepare($sqlUnread);
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['unread_count' => $row['unread_count']]);
