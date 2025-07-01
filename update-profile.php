<?php
session_start();
include 'db.php';
include 'conf.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get posted data
$username = trim($_POST['username']);
$email    = trim($_POST['email']);
$phone    = trim($_POST['phone']);
$based_in = trim($_POST['based_in']);

$new_profile_pic_path = '';

// Check if a new profile picture was uploaded
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath   = $_FILES['profile_pic']['tmp_name'];
    $fileName      = $_FILES['profile_pic']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Optional: Validate file extension
    $allowedExts = array('jpg', 'jpeg', 'png', 'gif');
    if (in_array($fileExtension, $allowedExts)) {
        // Define the new folder and create it if it doesn't exist
        $targetDir = "profile_pic_new/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Create a unique file name to prevent collisions
        $newFileName = time() . "_" . basename($fileName);
        $destPath = $targetDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $new_profile_pic_path = $destPath;
        } else {
            echo "There was an error moving the uploaded file.";
            exit;
        }
    } else {
        echo "Upload failed. Allowed file types: " . implode(", ", $allowedExts);
        exit;
    }
}

// Prepare the SQL query depending on whether a new image was uploaded
if ($new_profile_pic_path != '') {
    $sql = "UPDATE users SET username = ?, email = ?, phone = ?, based_in = ?, profile_pic = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssi', $username, $email, $phone, $based_in, $new_profile_pic_path, $user_id);
} else {
    $sql = "UPDATE users SET username = ?, email = ?, phone = ?, based_in = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $username, $email, $phone, $based_in, $user_id);
}

// Execute the update query
if ($stmt->execute()) {
    header("Location: profile.php");
    exit;
} else {
    echo "Error updating profile: " . $stmt->error;
}
?>
