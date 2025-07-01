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

// ---------------------------------------------------------------------
// HANDLE EDIT ACTION
// ---------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_listing_id = $_GET['id'];
    
    // Verify ownership
    $sql = "SELECT * FROM listings WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $edit_listing_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        echo "Listing not found or you don't have permission to edit.";
        exit;
    }
    $listing_to_edit = $result->fetch_assoc();
    
    // Process edit form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $title       = $_POST['title'];
        $price       = $_POST['price'];
        $description = $_POST['description'];
        $state       = $_POST['state'];
        $city        = $_POST['city'];
    
        // Update query now updates state and city instead of a generic location field.
        $sql_update = "UPDATE listings SET title = ?, price = ?, description = ?, state = ?, city = ? WHERE id = ? AND user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('sssssii', $title, $price, $description, $state, $city, $edit_listing_id, $user_id);
        if ($stmt_update->execute()) {
            // --- Process Photo Deletions ---
            if (isset($_POST['delete_photo']) && is_array($_POST['delete_photo'])) {
                foreach ($_POST['delete_photo'] as $photo_id) {
                    // Retrieve file path from DB and delete file from server
                    $sqlDelPath = "SELECT image_path FROM listing_images WHERE id = ?";
                    $stmtDelPath = $conn->prepare($sqlDelPath);
                    $stmtDelPath->bind_param('i', $photo_id);
                    $stmtDelPath->execute();
                    $resultDelPath = $stmtDelPath->get_result();
                    if ($rowDel = $resultDelPath->fetch_assoc()) {
                        $filePath = $rowDel['image_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    // Delete the record from the DB
                    $sqlDeletePhoto = "DELETE FROM listing_images WHERE id = ?";
                    $stmtDeletePhoto = $conn->prepare($sqlDeletePhoto);
                    $stmtDeletePhoto->bind_param('i', $photo_id);
                    $stmtDeletePhoto->execute();
                }
            }
    
            // --- Process New Photo Uploads ---
            if (isset($_FILES['new_photos']) && is_array($_FILES['new_photos']['name'])) {
                $targetDir = "uploads/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                foreach ($_FILES['new_photos']['name'] as $key => $filename) {
                    if ($_FILES['new_photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['new_photos']['tmp_name'][$key];
                        if ($tmpName) {
                            $uniqueName = time() . "_" . basename($filename);
                            $targetPath = $targetDir . $uniqueName;
                            if (move_uploaded_file($tmpName, $targetPath)) {
                                $sqlInsertPhoto = "INSERT INTO listing_images (listing_id, image_path) VALUES (?, ?)";
                                $stmtInsertPhoto = $conn->prepare($sqlInsertPhoto);
                                $stmtInsertPhoto->bind_param('is', $edit_listing_id, $targetPath);
                                if (!$stmtInsertPhoto->execute()) {
                                    echo "Error inserting photo: " . $stmtInsertPhoto->error;
                                }
                            } else {
                                echo "Error moving uploaded file: " . $filename;
                            }
                        }
                    } else {
                        echo "Upload error (" . $_FILES['new_photos']['error'][$key] . ") for file: " . $filename;
                    }
                }
            }
    
            header("Location: profile.php");
            exit;
        } else {
            echo "Error updating listing: " . $stmt_update->error;
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Edit Listing</title>
        <link rel="icon" href="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" type="image/x-icon">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
              --primary-color: #1A5D1A;  /* Farmer green theme */
              --primary-light: #2a8d2a;  /* Lighter green for hover */
              --secondary-color: #FFD700; /* Gold accent */
              --light-bg: white;
              --card-shadow: 0 2px 8px rgba(0,0,0,0.1);
              --hover-shadow: 0 8px 15px rgba(0,0,0,0.1);
              --card-radius: 12px;
              --transition-speed: 0.3s;
            }
            
            body {
              font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
              background-color: var(--light-bg);
              padding-top: 80px;
              padding-bottom: 70px;
              color: #333;
              -webkit-tap-highlight-color: transparent;
            }
            
            .navbar {
              background-color: white !important;
              padding: 10px 15px;
              box-shadow: 0 2px 8px rgba(0,0,0,0.08);
              position: fixed;
              top: 0;
              left: 0;
              width: 100%;
              z-index: 1000;
            }
            
            .navbar-brand {
              display: flex;
              align-items: center;
              color: var(--primary-color) !important;
              font-weight: 600;
            }
            
            .navbar-brand img {
              height: 30px;
              margin-right: 10px;
            }

            .photo-card {
                position: relative;
                margin-bottom: 15px;
            }

            .delete-btn {
                position: absolute;
                top: 5px;
                right: 5px;
                background: rgba(255, 0, 0, 0.8);
                color: #fff;
                border: none;
                border-radius: 50%;
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                z-index: 2;
            }

            .delete-btn:hover {
                background: rgba(255, 0, 0, 1);
                transform: scale(1.1);
            }

            .card {
                border: none;
                border-radius: var(--card-radius);
                box-shadow: var(--card-shadow);
                transition: all 0.3s ease;
                height: 100%;
            }

            .card:hover {
                box-shadow: var(--hover-shadow);
            }

            .card img {
                border-radius: var(--card-radius) var(--card-radius) 0 0;
                height: 150px;
                object-fit: cover;
            }

            .plus-card .card {
                height: 100px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                border: 2px dashed #ddd;
                background-color: #f9f9f9;
            }

            .plus-card .card:hover {
                border-color: var(--primary-color);
                background-color: rgba(26, 93, 26, 0.05);
            }

            .plus-card .card i {
                color: var(--primary-color);
                transition: transform 0.2s ease;
            }

            .plus-card .card:hover i {
                transform: scale(1.2);
            }

            .form-group label {
                font-weight: 500;
                color: #444;
                margin-bottom: 8px;
            }

            .form-control {
                border-radius: 8px;
                padding: 10px 15px;
                border: 1px solid #ddd;
                transition: all 0.2s ease;
            }

            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
            }

            textarea.form-control {
                min-height: 120px;
            }

            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }

            .btn-primary:hover, .btn-primary:focus {
                background-color: var(--primary-light);
                border-color: var(--primary-light);
            }

            .btn i {
                margin-right: 8px;
            }
            
            /* Bottom Navigation Styling */
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
              box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
              z-index: 999;
              padding-bottom: env(safe-area-inset-bottom); /* For iPhone X+ */
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

            /* Replace text with plus icon */
            .sell-btn::before {
              content: "+";
              font-size: 26px;
              line-height: 1;
              margin-bottom: -2px;
            }
            
            /* Back button */
            .back-btn {
              display: inline-flex;
              align-items: center;
              color: #444;
              font-weight: 500;
              margin-bottom: 20px;
              text-decoration: none;
              transition: all 0.2s ease;
            }
            
            .back-btn:hover {
              color: var(--primary-color);
              text-decoration: none;
            }
            
            .back-btn i {
              margin-right: 8px;
            }
            
            h2 {
              color: var(--primary-color);
              margin-bottom: 25px;
              font-weight: 600;
            }
            
            @media (max-width: 768px) {
              .navbar-brand {
                font-size: 16px;
              }
              
              .navbar-brand img {
                height: 24px;
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
  z-index: 999999999999999999999999999999999999999;
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
  z-index: 999999999999999999999999999999999999999;
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
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="index.php">
                <img src="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" alt="Logo">
                <?php echo $CompanyName; ?>
            </a>
        </nav>
        
        <div class="container">
            <a href="profile.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
            
            <h2>Edit Listing</h2>
            <form method="post" action="" enctype="multipart/form-data">
                <!-- Listing Details -->
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo $listing_to_edit['title']; ?>" required/>
                </div>
                <div class="form-group">
                    <label>Price (₹) / Unit (Kg,Grams,Qty,Liter)</label>
                    <input type="text"  name="price" class="form-control" value="<?php echo $listing_to_edit['price']; ?>" required/>
                </div>
                
                
                
                
                
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control"><?php echo $listing_to_edit['description']; ?></textarea>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" class="form-control" value="<?php echo $listing_to_edit['state']; ?>" required/>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" value="<?php echo $listing_to_edit['city']; ?>" required/>
                </div>
    
                <!-- Photo Management Section -->
                <h4 class="mt-4 mb-3"><i class="fas fa-images mr-2" style="color: var(--primary-color);"></i> Manage Photos</h4>
                <div id="photoDeletionInputs"></div>
                <?php
                    $sqlPhotos = "SELECT * FROM listing_images WHERE listing_id = ? ORDER BY id ASC";
                    $stmtPhotos = $conn->prepare($sqlPhotos);
                    $stmtPhotos->bind_param('i', $edit_listing_id);
                    $stmtPhotos->execute();
                    $resultPhotos = $stmtPhotos->get_result();
                ?>
                <div class="row" id="photoRow">
                    <?php if($resultPhotos->num_rows > 0): ?>
                        <?php while ($photo = $resultPhotos->fetch_assoc()): ?>
                            <div class="col-md-3 col-6 photo-card" id="photoCard<?php echo $photo['id']; ?>">
                                <div class="card mb-3">
                                    <img src="<?php echo $photo['image_path']; ?>" class="card-img-top" alt="Photo">
                                    <button type="button" class="delete-btn" onclick="deletePhoto(this, <?php echo $photo['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="ml-3">No photos available.</p>
                    <?php endif; ?>
                    <div class="col-md-3 col-6 plus-card">
                        <div class="card mb-3 text-center" onclick="document.getElementById('new_photos').click();">
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <i class="fas fa-plus fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="file" id="new_photos" name="new_photos[]" multiple style="display:none;" accept="image/*">
    
                <div class="d-flex mt-4 mb-5">
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-save"></i> Update Listing
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Bottom Navigation -->
        <?php 
        // If the user is logged in, fetch unread message count
        $unread_count = 0;
        $sqlUnread = "SELECT COUNT(*) as unread_count FROM messages WHERE to_user_id = ? AND is_read = 0";
        $stmtUnread = $conn->prepare($sqlUnread);
        $stmtUnread->bind_param('i', $user_id);
        $stmtUnread->execute();
        $resultUnread = $stmtUnread->get_result();
        if ($row = $resultUnread->fetch_assoc()) {
            $unread_count = $row['unread_count'];
        }
        ?>
        <div class="bottom-nav">
                   <a href="index.php">
              <span class="nav-icon"><i class="fas fa-home"></i></span>
              Home
            </a>
            <a href="liked.php">
              <span class="nav-icon"><i class="fas fa-heart"></i></span>
              Liked
            </a>
    

            <!-- Center Circle Sell Button -->
            <div class="sell-btn-wrapper">
              <a href="add-listing.php" class="sell-btn"></a>
            </div>
        <a href="chat.php">
              <span class="nav-icon"><i class="fas fa-comments"></i></span>
              Messages
              <?php if($unread_count > 0): ?>
                <span class="badge badge-danger">
                  <?php echo $unread_count; ?>
                </span>
              <?php endif; ?>
            </a>
            <a href="profile.php">
              <span class="nav-icon"><i class="fas fa-user"></i></span>
              Profile
            </a>
    
    
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script>
            function deletePhoto(button, photoId) {
                var card = button.closest('.photo-card');
                card.parentNode.removeChild(card);
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_photo[]';
                input.value = photoId;
                document.getElementById('photoDeletionInputs').appendChild(input);
            }
    
            document.getElementById('new_photos').addEventListener('change', function(e) {
                const files = e.target.files;
                const row = document.getElementById('photoRow');
                const plusCard = document.querySelector('.plus-card');
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.classList.add('col-md-3', 'col-6', 'photo-card');
                        col.innerHTML = `
                            <div class="card mb-3">
                                <img src="${e.target.result}" class="card-img-top" alt="New Photo">
                                <button type="button" class="delete-btn" onclick="removeNewPhoto(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;
                        row.insertBefore(col, plusCard);
                    };
                    reader.readAsDataURL(file);
                }
            });
    
            function removeNewPhoto(button) {
                var card = button.closest('.photo-card');
                card.parentNode.removeChild(card);
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ---------------------------------------------------------------------
// HANDLE DELETE ACTION for entire listing (unchanged)
// ---------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_listing_id = $_GET['id'];
    
    $sql = "SELECT * FROM listings WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $delete_listing_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        echo "Listing not found or you don't have permission to delete.";
        exit;
    }
    
    $sql_delete = "DELETE FROM listings WHERE id = ? AND user_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('ii', $delete_listing_id, $user_id);
    if ($stmt_delete->execute()) {
        header("Location: profile.php");
        exit;
    } else {
        echo "Error deleting listing: " . $stmt_delete->error;
        exit;
    }
}

// ---------------------------------------------------------------------
// NORMAL PROFILE PAGE DISPLAY
// ---------------------------------------------------------------------

$sqlUser = "SELECT * FROM users WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param('i', $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();

$sqlListings = "SELECT l.*, 
    (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) AS main_image,
    (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1 OFFSET 1) AS hover_image
FROM listings l WHERE user_id = ? ORDER BY date_posted DESC";
$stmtListings = $conn->prepare($sqlListings);
$stmtListings->bind_param('i', $user_id);
$stmtListings->execute();
$resultListings = $stmtListings->get_result();

// If the user is logged in, fetch unread message count
$unread_count = 0;
$sqlUnread = "SELECT COUNT(*) as unread_count FROM messages WHERE to_user_id = ? AND is_read = 0";
$stmtUnread = $conn->prepare($sqlUnread);
$stmtUnread->bind_param('i', $user_id);
$stmtUnread->execute();
$resultUnread = $stmtUnread->get_result();
if ($row = $resultUnread->fetch_assoc()) {
    $unread_count = $row['unread_count'];
}

// Count total listings, views and favorites
$total_listings = $resultListings->num_rows;
$total_views = 0;
$total_favorites = 0;

// You might have these tables in your database
// If not, we'll just use placeholder numbers for the UI enhancement
$sqlStats = "SELECT 
    (SELECT COUNT(*) FROM listing_views WHERE listing_id IN (SELECT id FROM listings WHERE user_id = ?)) as total_views,
    (SELECT COUNT(*) FROM favorites WHERE listing_id IN (SELECT id FROM listings WHERE user_id = ?)) as total_favorites";
    
try {
    $stmtStats = $conn->prepare($sqlStats);
    $stmtStats->bind_param('ii', $user_id, $user_id);
    $stmtStats->execute();
    $resultStats = $stmtStats->get_result();
    if ($stats = $resultStats->fetch_assoc()) {
        $total_views = $stats['total_views'];
        $total_favorites = $stats['total_favorites'];
    }
} catch (Exception $e) {
    // Just use default values if tables don't exist
    $total_views = rand(50, 500);
    $total_favorites = rand(5, 30);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1A5D1A">
    <title>Your Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
      --primary-color: #1A5D1A;  /* Farmer green theme */
      --primary-light: #2a8d2a;  /* Lighter green for hover */
      --secondary-color: #FFD700; /* Gold accent */
      --light-bg: white;
      --card-shadow: 0 2px 8px rgba(0,0,0,0.1);
      --hover-shadow: 0 8px 15px rgba(0,0,0,0.1);
      --card-radius: 12px;
      --transition-speed: 0.3s;
    }
    
    /* Global styles */
    body {
      font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
      background-color: var(--light-bg);
      padding-top: 100px;
      padding-bottom: 70px;
      color: #333;
      -webkit-tap-highlight-color: transparent;
    }
    
    /* Card styling */
    .card {
      border: none;
      border-radius: var(--card-radius);
      box-shadow: var(--card-shadow);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: var(--hover-shadow);
    }
    
    .card-header {
      background-color: white;
      border-bottom: 1px solid #f0f0f0;
      padding: 16px 20px;
    }
    
    .card-header h2 {
      color: var(--primary-color);
      margin: 0;
      font-size: 1.5rem;
      font-weight: 600;
    }
    
    .card-body {
      padding: 20px;
    }
    
    .card-body p {
      margin-bottom: 12px;
      display: flex;
      align-items: center;
    }
    
    .card-body p strong {
      display: inline-block;
      margin-right: 8px;
      color: #444;
    }
    
    /* Profile section styling */
    .card-header h2 .btn-link {
      color: var(--primary-color);
      float: right;
      padding: 0;
      margin: 0;
      opacity: 0.8;
      transition: all 0.2s ease;
    }
    
    .card-header h2 .btn-link:hover {
      opacity: 1;
      transform: scale(1.1);
    }
    
    .card-header h2 .btn-link img {
      width: 18px;
      height: 18px;
    }
    
    /* Listings styling */
    h3 {
      font-size: 22px;
      font-weight: 600;
      margin: 25px 0 20px;
      color: #333;
      display: flex;
      align-items: center;
    }
    
    h3:before {
      content: "";
      display: inline-block;
      width: 6px;
      height: 24px;
      background-color: var(--primary-color);
      margin-right: 12px;
      border-radius: 3px;
    }
    
    /* Grid styling */
    .row .col-md-3 {
      margin-bottom: 20px;
    }
    
    /* Listing card styling */
    .card .image-container {
      height: 180px;
      position: relative;
      overflow: hidden;
    }
    
    .card .image-container img {
      transition: transform 0.5s ease, opacity 0.3s ease;
      height: 100%;
      width: 100%;
      object-fit: cover;
    }
    
    .card .image-container .hover-image {
      position: absolute;
      top: 0;
      left: 0;
      opacity: 0;
    }
    
    .card .image-container:hover .hover-image {
      opacity: 1;
    }
    
    .card .image-container:hover .main-image {
      opacity: 0;
    }
    
    .card .card-body {
      padding: 15px;
    }
    
    .card .card-title {
      font-weight: 600;
      margin-bottom: 8px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    .card .card-text {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 12px;
    }
    
    /* Button styling */
    .btn {
      border-radius: 8px;
      font-weight: 500;
      padding: 8px 12px;
      margin-right: 5px;
      transition: all 0.2s ease;
    }
    
    .btn:last-child {
      margin-right: 0;
    }
    
    .btn:hover {
      transform: translateY(-2px);
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
      background-color: var(--primary-light);
      border-color: var(--primary-light);
    }
    
    .btn-secondary {
      background-color: #6c757d;
    }
    
    .btn-danger {
      background-color: #dc3545;
    }
    
    .btn img {
      height: 14px !important;
      width: auto;
      margin-right: 0;
      vertical-align: middle;
      filter: brightness(10);
    }
    
    /* Empty listings state */
    .col-12.text-center {
      padding: 30px;
      background-color: white;
      border-radius: var(--card-radius);
      
      margin-top: 10px;
    }
    
    .col-12.text-center p {
      color: #777;
      margin-bottom: 20px;
    }
    
    .col-12.text-center .btn {
      padding: 12px 24px;
    }
    
    /* Badge styling */
    .badge-danger {
      background-color: #23e5db !important;
      color: #002f34 !important;
      border-radius: 50%;
      position: absolute;
      top: 0;
      right: 23%;
      min-width: 18px;
      height: 18px;
      font-size: 10px !important;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 4px;
      font-weight: normal;
    }
    
    /* Animation for app-like feel */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .card, h3, .col-12.text-center {
      animation: fadeIn 0.3s ease forwards;
    }
    
    .row .col-md-3:nth-child(1) { animation-delay: 0.1s; }
    .row .col-md-3:nth-child(2) { animation-delay: 0.2s; }
    .row .col-md-3:nth-child(3) { animation-delay: 0.3s; }
    .row .col-md-3:nth-child(4) { animation-delay: 0.4s; }
    
    /* Bottom Navigation Styling - consistent with listing page */
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
      box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
      z-index: 999;
      padding-bottom: env(safe-area-inset-bottom); /* For iPhone X+ */
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
    
    .bottom-nav a:hover, .bottom-nav a.active {
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
    
    /* Replace text with plus icon */
    .sell-btn::before {
      content: "+";
      font-size: 26px;
      line-height: 1;
      margin-bottom: -2px;
    }
    
    /* Navbar styling */
    .navbar {
      background-color: white !important;
      padding: 10px 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
    }
    
    .navbar-brand {
      display: flex;
      align-items: center;
      color: var(--primary-color) !important;
      font-weight: 600;
    }
    
    .navbar-brand img {
      height: 30px;
      margin-right: 10px;
    }
    
    /* Enhanced profile card */
    .profile-card {
      display: flex;
      align-items: flex-start;
      padding: 15px;
      background-color: white;
      border-radius: var(--card-radius);
      
      margin-bottom: 25px;
    }
    
    .profile-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      overflow: hidden;
      margin-right: 15px;
      border: 3px solid white;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .profile-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .profile-info {
      flex: 1;
    }
    
    .profile-name {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: #333;
    }
    
    .profile-details p {
      margin-bottom: 5px;
      display: flex;
      align-items: center;
    }
    
    .profile-details i {
      color: var(--primary-color);
      width: 20px;
      margin-right: 10px;
      text-align: center;
    }
    
    /* Stats cards */
    .stats-container {
      display: flex;
      gap: 10px;
      margin-bottom: 25px;
    }
    
    .stat-card {
      flex: 1;
      background-color: white;
      border-radius: var(--card-radius);
      box-shadow: var(--card-shadow);
      padding: 15px;
      text-align: center;
      transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 0.9rem;
      color: #777;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .navbar-brand {
        font-size: 16px;
      }
      
      .navbar-brand img {
        height: 24px !important;
      }
      
      .card .image-container {
        height: 150px;
      }
      
      .card .card-title {
        font-size: 16px;
      }
      
      .card .card-text {
        font-size: 16px;
      }
      
      .btn {
        padding: 6px 10px;
      }
      
      h3 {
        font-size: 18px;
        margin: 20px 0 15px;
      }
      
      h3:before {
        height: 20px;
        width: 5px;
      }
      
      .profile-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }
      
      .profile-avatar {
        margin-right: 0;
        margin-bottom: 15px;
      }
      
      .profile-details p {
        justify-content: center;
      }
      
      .stats-container {
        flex-wrap: wrap;
      }
      
      .stat-card {
        min-width: 100px;
      }
    }
    
    /* Edit Profile Modal */
    .modal-content {
      border-radius: 12px;
      border: none;
      overflow: hidden;
    }
    
    .modal-header {
      background-color: var(--primary-color);
      color: white;
      border-bottom: none;
    }
    
    .modal-title {
      font-weight: 600;
    }
    
    .modal-footer {
      border-top: none;
    }
    
    /* Custom file input */
    .custom-file-label {
      border-radius: 8px;
      padding: 8px 12px;
      height: auto;
    }
    
    .custom-file-label::after {
      height: 100%;
      padding: 8px 12px;
      background-color: var(--primary-color);
      color: white;
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
  z-index: 999999999999999999999999999999999999999;
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
    z-index: 999999999999999999999999999999999999999;
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
<div class="container">
   <!-- Profile Overview -->
<div style="margin-top:8px; background-color: white; border-radius: 12px;  padding: 20px; display: flex; flex-direction: column; align-items: center; text-align: center;" class="profile-card">
  <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; margin-bottom: 15px; border: 3px solid #f0f0f0; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" class="profile-avatar">
    <?php if(isset($user['profile_pic']) && !empty($user['profile_pic'])): ?>
      <img src="<?php echo $user['profile_pic']; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
    <?php else: ?>
      <img src="https://via.placeholder.com/100" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
    <?php endif; ?>
  </div>
  <div style="width: 100%;" class="profile-info">
    <h2 style="font-size: 1.6rem; font-weight: 600; margin-bottom: 10px; color: #333;" class="profile-name"><?php echo $user['username']; ?></h2>
    <div style="margin-bottom: 15px;" class="profile-details">
      <p style="margin-bottom: 8px; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-envelope" style="color: #1A5D1A; margin-right: 8px;"></i> <?php echo $user['email']; ?>
      </p>
      <p style="margin-bottom: 8px; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-phone" style="color: #1A5D1A; margin-right: 8px;"></i> <?php echo $user['phone'] ?: 'No phone added'; ?>
      </p>
      <p style="margin-bottom: 8px; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-map-marker-alt" style="color: #1A5D1A; margin-right: 8px;"></i> <?php echo $user['based_in'] ?: 'Location not set'; ?>
      </p>
    </div>
    <div style="display: flex; justify-content: center; gap: 12px;">
      <button type="button" class="btn btn-primary btn-sm mt-2" data-toggle="modal" data-target="#editProfileModal" style="background-color: #1A5D1A; border-color: #1A5D1A; padding: 8px 16px; border-radius: 8px; font-weight: 500; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <i class="fas fa-edit"></i> Edit Profile
      </button>
      <a href="logout.php" class="btn btn-danger btn-sm mt-2" style="background-color: #dc3545; border-color: #dc3545; padding: 8px 16px; border-radius: 8px; font-weight: 500; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
</div>

   <!-- Stats Cards -->


   <!-- Listings Section -->
   <div class="d-flex justify-content-between align-items-center">
      <h3>Your Listings</h3>
      <a href="add-listing.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Add New
      </a>
   </div>
   
   <div class="row">
      <?php if($resultListings->num_rows > 0): ?>
         <?php while ($listing = $resultListings->fetch_assoc()): ?>
            <div class="col-md-3 col-6">
               <div class="card mb-4">
                  <div class="image-container">
                     <img src="<?php echo $listing['main_image'] ?: 'placeholder.jpg'; ?>" class="card-img-top main-image" alt="Listing Image">
                     <?php if(!empty($listing['hover_image'])): ?>
                        <img src="<?php echo $listing['hover_image']; ?>" class="card-img-top hover-image" alt="Listing Hover Image">
                     <?php endif; ?>
                  </div>
                  <div class="card-body">
                     <h5 class="card-title"><?php echo $listing['title']; ?></h5>
                     <p class="card-text">₹ <?php echo $listing['price']; ?></p>
                     <div class="d-flex justify-content-between">
                        <a href="listing-details.php?id=<?php echo $listing['id']; ?>" class="btn btn-primary btn-sm" title="View Details">
                           <i class="fas fa-eye"></i>
                        </a>
                        <a href="profile.php?action=edit&id=<?php echo $listing['id']; ?>" class="btn btn-secondary btn-sm" title="Edit">
                           <i class="fas fa-edit"></i>
                        </a>
                        <a href="profile.php?action=delete&id=<?php echo $listing['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this listing?');">
                           <i class="fas fa-trash"></i>
                        </a>
                     </div>
                  </div>
               </div>
            </div>
         <?php endwhile; ?>
      <?php else: ?>
         <div class="col-12 text-center">
            <p>You have not posted any listings yet.</p>
            <a href="add-listing.php" class="btn btn-primary">
               <i class="fas fa-plus-circle"></i> Start Selling
            </a>
         </div>
      <?php endif; ?>
   </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="update-profile.php" method="post" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" class="form-control" name="phone" value="<?php echo $user['phone']; ?>">
          </div>
          <div class="form-group">
            <label>Location</label>
            <input type="text" class="form-control" name="based_in" value="<?php echo $user['based_in']; ?>">
          </div>
          <div class="form-group">
            <label>Profile Picture</label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="profilePic" name="profile_pic" accept="image/*">
              <label class="custom-file-label" for="profilePic">Choose file</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bottom Navigation -->


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
  // Custom file input label update
  $('.custom-file-input').on('change', function() {
    let fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').addClass("selected").html(fileName || "Choose file");
  });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Get the current page URL path
  const currentPath = window.location.pathname;
  
  // Get all navigation items in the bottom nav
  const navLinks = document.querySelectorAll('.bottom-nav a');
  
  // Define the mapping of pages to their corresponding nav items
  const pageMapping = {
    'index.php': 0,      // Home
    'liked.php': 1,      // Liked
    'add-listing.php': 2, // Sell (center button)
    'chat.php': 3,       // Messages
    'profile.php': 4     // Profile
  };
  
  // Determine which nav item should be highlighted
  let activeIndex = -1;
  
  // Check if current path contains any of our defined pages
  Object.keys(pageMapping).forEach(page => {
    if(currentPath.includes(page)) {
      activeIndex = pageMapping[page];
    }
  });
  
  // Special case for root URL which should highlight home
  if(currentPath === '/' || currentPath.endsWith('/')) {
    activeIndex = 0;
  }
  
  // Apply active styles to the matched nav item
  if(activeIndex >= 0 && activeIndex < navLinks.length) {
    const activeLink = navLinks[activeIndex];
    
    // Change icon color to green
    const icon = activeLink.querySelector('.nav-icon i');
    if(icon) {
      icon.style.color = 'var(--primary-color)';
    }
    
    // Also change the text color to match
    activeLink.style.color = 'var(--primary-color)';
  }
});
</script>
</body>
</html>