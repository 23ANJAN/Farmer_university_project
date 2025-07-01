
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if (!isset($_POST['listing_id'])) {
    echo json_encode(['error' => 'No listing id provided']);
    exit;
}

$listing_id = (int) $_POST['listing_id'];

// Fetch current liked value
$sql = "SELECT liked FROM listings WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $listing_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Listing not found']);
    exit;
}
$row = $result->fetch_assoc();
$currentLiked = (int)$row['liked'];
$newLiked = $currentLiked ? 0 : 1;

// Update liked value
$sqlUpdate = "UPDATE listings SET liked = ? WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param('ii', $newLiked, $listing_id);
if($stmtUpdate->execute()){
    echo json_encode(['status' => 'ok', 'liked' => $newLiked]);
} else {
    echo json_encode(['error' => $stmtUpdate->error]);
}
?>
