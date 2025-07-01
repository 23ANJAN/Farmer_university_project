<?php
session_start();
include 'db.php';
include 'conf.php';

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Get form data
    $title       = $_POST['title'];
    $price       = $_POST['price'];
    $description = $_POST['description'];
    $landmark    = $_POST['landmark'];  // Landmark Near input
    $state       = $_POST['state'];
    $city        = $_POST['city'];
    // We'll use the landmark as the location value
    $location    = $landmark;
    $user_id     = $_SESSION['user_id'];

    // Insert main listing record
    $sql = "INSERT INTO listings (user_id, title, price, description, location, state, city) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issssss', $user_id, $title, $price, $description, $location, $state, $city);

    if($stmt->execute()){
        $newListingId = $stmt->insert_id;

        // Handle multiple image uploads
        if(!empty($_FILES['images']['name'][0])){
            $targetDir = "uploads/";
            if(!is_dir($targetDir)){
                mkdir($targetDir, 0777, true);
            }
            foreach($_FILES['images']['name'] as $key => $filename){
                $tmpName = $_FILES['images']['tmp_name'][$key];
                if($tmpName){
                    // Generate a unique name to avoid collisions
                    $uniqueName = time() . "_" . $filename;
                    $targetPath = $targetDir . $uniqueName;
                    if(move_uploaded_file($tmpName, $targetPath)){
                        // Insert into listing_images table
                        $sqlImg = "INSERT INTO listing_images (listing_id, image_path) VALUES (?, ?)";
                        $stmtImg = $conn->prepare($sqlImg);
                        $stmtImg->bind_param('is', $newListingId, $targetPath);
                        $stmtImg->execute();
                    }
                }
            }
        }
        header('Location: index.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en" >
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1A5D1A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Add Listing</title>
    <link rel="icon" href="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" type="image/x-icon">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Reset z-index stacking context */
        * {
            position: relative;
            z-index: 1;
        }
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
            --light-color: #f9f9f9;
            --text-color: #333;
            --card-shadow: 0 2px 8px rgba(0,0,0,0.1);
            --transition-speed: 0.3s;
            --card-radius: 12px;
            
            /* Z-index hierarchy */
            --z-background: 1;
            --z-content: 10;
            --z-card: 20;
            --z-form-control: 30;
            --z-form-control-focus: 35;
            --z-dropdown: 500;
            --z-sticky: 800;
            --z-overlay: 900;
            --z-modal: 1000;
            --z-navbar: 1100;
            --z-bottomnav: 1100;
            --z-tooltip: 1200;
            --z-popup: 1300;
        }
        
        html, body {
            position: relative;
            z-index: var(--z-background);
        }
        
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
            background-color: #f5f5f5;
            color: var(--text-color);
            padding-bottom: 70px; /* Space for bottom nav */
            padding-top: 100px; /* Space for fixed navbar */
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Navbar from navbar.php should have this added */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: var(--z-navbar);
        }
        
        /* Bottom nav from navbar.php should have this added */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: var(--z-bottomnav);
        }
        
        /* Page content */
        .container {
            position: relative;
            z-index: var(--z-content);
        }
        
        /* Page title */
        .page-header {
            margin-bottom: 25px;
            position: relative;
            display: flex;
            align-items: center;
            z-index: var(--z-sticky);
        }
        
        .page-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .back-btn {
            margin-right: 12px;
            font-size: 20px;
            color: #444;
            cursor: pointer;
            z-index: var(--z-form-control);
        }
        
        /* Form card */
        .form-card {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            padding: 25px;
            margin-bottom: 25px;
            animation: fadeIn 0.3s ease;
            position: relative;
            z-index: var(--z-card);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Form controls */
        .form-group {
            position: relative;
            z-index: var(--z-form-control);
        }
        
        .form-group label {
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
            z-index: var(--z-form-control);
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            height: auto;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: all 0.2s ease;
            position: relative;
            z-index: var(--z-form-control);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
            outline: none;
            z-index: var(--z-form-control-focus);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        /* Image upload section */
        .image-upload-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #f9f9f9;
            position: relative;
            z-index: var(--z-form-control);
        }
        
        .image-upload-container:hover {
            border-color: var(--primary-color);
            background-color: rgba(26, 93, 26, 0.05);
        }
        
        .image-upload-container i {
            font-size: 40px;
            color: #aaa;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        
        .image-upload-container:hover i {
            color: var(--primary-color);
        }
        
        .image-upload-text {
            color: #666;
            margin-bottom: 10px;
        }
        
        #fileInput {
            display: none;
            position: absolute;
            z-index: -1; /* Hidden but accessible */
        }
        
        /* Image preview */
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            position: relative;
            z-index: var(--z-form-control);
        }
        
        .image-preview {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: var(--z-form-control);
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: var(--z-form-control);
        }
        
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255,255,255,0.8);
            color: #f44336;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: var(--z-form-control-focus); /* Higher than the image */
        }
        
        .remove-image:hover {
            background: white;
            transform: scale(1.1);
        }
        
        /* Form sections */
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            position: relative;
            z-index: var(--z-form-control);
        }
        
        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #444;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .form-section-title i {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        /* Submit button */
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px 20px;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: var(--z-form-control);
        }
        
        .submit-btn:hover, .submit-btn:focus {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 93, 26, 0.2);
        }
        
        .submit-btn i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Any potential modals or overlays */
        .modal, .overlay {
            position: fixed;
            z-index: var(--z-modal);
        }
        
        /* Ensure dropdowns are above other content */
        .dropdown-menu {
            z-index: var(--z-dropdown);
        }
        
        /* Any tooltips */
        .tooltip, [data-toggle="tooltip"] {
            z-index: var(--z-tooltip);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-card {
                padding: 20px 15px;
            }
            
            .page-header h2 {
                font-size: 22px;
            }
            
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include "navbar.php"; ?>
    
    <div class="container">
        <div class="page-header" style="margin-bottom:10px; margin-top:30px;">
             
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
           
            <h2>Add New Listing</h2>
        </div>
        
        <div class="form-card">
            <form method="post" action="" enctype="multipart/form-data" id="listingForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-info-circle"></i> Basic Information
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="What are you selling?" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₹) / Unit (Kg,Grams,Qty,Liter)</label>
                        <input type="text"  name="price" id="price" class="form-control" placeholder="Enter price & units" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" placeholder="Describe your item - include condition, features, and why you're selling"></textarea>
                    </div>
                </div>
                
                <!-- Location Information -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-map-marker-alt"></i> Location Details
                    </div>
                    
                    <div class="form-group">
                        <label for="landmark">Landmark Near</label>
                        <input type="text" name="landmark" id="landmark" class="form-control" placeholder="e.g., MG Road, Shivaji Park" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" name="state" id="state" class="form-control" placeholder="e.g., Maharashtra" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" name="city" id="city" class="form-control" placeholder="e.g., Mumbai" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Images -->
                <div class="form-section" style="border-bottom: none; margin-bottom: 20px; padding-bottom: 0;">
                    <div class="form-section-title">
                        <i class="fas fa-images"></i> Photos
                    </div>
                    
                    <div class="image-upload-container" id="uploadContainer">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p class="image-upload-text">Click to upload photos</p>
                        <p class="text-muted">Add up to 10 photos</p>
                        <input type="file" name="images[]" id="fileInput" class="form-control" multiple accept="image/*">
                    </div>
                    
                    <div class="image-preview-container" id="imagePreviewContainer"></div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-check-circle"></i> Post Listing
                </button>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image upload preview
        document.getElementById('uploadContainer').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });
        
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const files = e.target.files;
            const container = document.getElementById('imagePreviewContainer');
            
            // Clear existing previews if needed
            // container.innerHTML = '';
            
            for(let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Only process images
                if (!file.type.match('image.*')) {
                    continue;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <div class="remove-image">
                            <i class="fas fa-times"></i>
                        </div>
                    `;
                    
                    // Add to container
                    container.appendChild(preview);
                    
                    // Handle remove button
                    preview.querySelector('.remove-image').addEventListener('click', function(e) {
                        e.stopPropagation();
                        preview.remove();
                        // Note: This only removes the preview, not the actual file from the input
                        // In a real implementation, you'd need to handle this differently
                    });
                };
                
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>