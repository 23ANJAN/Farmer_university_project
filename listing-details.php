<?php
session_start();
include 'db.php';
include 'conf.php';

if(!isset($_GET['id'])){
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

// 1) Fetch listing + seller info
$sql = "SELECT listings.*, users.username, users.id AS seller_id, users.profile_pic 
        FROM listings 
        JOIN users ON listings.user_id = users.id 
        WHERE listings.id = ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$listing = $result->fetch_assoc();

if(!$listing){
    header('Location: index.php');
    exit;
}

// 2) Fetch all images from listing_images
$sqlImages = "SELECT * FROM listing_images WHERE listing_id = ?";
$stmtImages = $conn->prepare($sqlImages);
$stmtImages->bind_param('i', $id);
$stmtImages->execute();
$resultImages = $stmtImages->get_result();
$images = $resultImages->fetch_all(MYSQLI_ASSOC);

// For map embed, assume listing['location'] is like "Mumbai, India"
$encodedLocation = urlencode($listing['location']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1A5D1A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo $listing['title']; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <!-- Fancybox CSS (Lightbox) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
    <style>
    
    body {
  overflow-x: hidden;
  max-width: 100%;
  position: relative;
}

html {
  overflow-x: hidden;
  max-width: 100%;
}

.container, .row, .col-md-3, .col-6 {
  max-width: 100%;
  overflow-x: hidden;
}

* {
  box-sizing: border-box;
  max-width: 100vw;
}

.card, .card-body, .card-img-top {
  max-width: 100%;
}
      :root {
        --primary-color: #1A5D1A;  /* Farmer green theme */
        --primary-light: #2a8d2a;  /* Lighter green for hover */
        --secondary-color: #FFD700; /* Gold accent */
        --light-color: #f9f9f9;
        --text-color: #333;
        --card-shadow: 0 2px 8px rgba(0,0,0,0.1);
        --transition-speed: 0.3s;
      }

      /* Global styles */
      body {
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
        margin: 0;
        padding: 0;
        padding-bottom: 70px; /* Space for bottom nav */
        background-color: #f5f5f5;
        padding-top: 100px; /* Space for fixed navbar */
        color: var(--text-color);
        -webkit-tap-highlight-color: transparent;
      }

      /* App Header Styling */
 .navbar {
  background-color: white !important;
  padding: 12px 16px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  height: auto;
}


      /* Logo and Brand */
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
        display: none; /* Hide the toggler */
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

      /* Location selector */
      .form-inline input[name="location"],
      .form-inline input[name="city"] {
        display: none;
      }

      /* Create location dropdown */
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
        content: "\f3c5"; /* map marker */
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        position: absolute;
        top: -30px;
        right: calc(100% - 135px);
        color: var(--primary-color);
        font-size: 14px;
      }

      /* Search bar */
.form-inline input[name="q"] {
  flex: 1;
  width: 100%;
  border-radius: 50px;
  border: 1px solid #ddd;
  background-color: #f2f4f5;
  padding: 12px 20px 12px 45px;
  font-size: 16px;
  transition: all 0.2s ease;
}

      .form-inline input[name="q"]:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(26, 93, 26, 0.1);
        outline: none;
      }

      /* Search button */
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

      /* Badge styling */
      .badge-danger {
        background-color: #23e5db !important;
        color: #002f34 !important;
        border-radius: 50%;
        position: absolute;
        top: 0;
        right: 68%;
        min-width: 18px;
        height: 18px;
        font-size: 10px !important;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        font-weight: normal;
      }
      
      /* Location Modal - Fixed Animation Version */
      .location-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        margin-bottom: 340px;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 2000;
      }

      /* When modal is shown */
      .location-modal.show {
        display: block;
        animation: fadeIn 0.3s forwards;
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
        z-index: 2001;
        max-height: 80vh;
        overflow-y: auto;
      }

      /* Apply animation to content when modal is shown */
      .location-modal.show .location-modal-content {
        animation: slideUp 0.3s forwards;
      }

      /* Header styling */
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
        color: #1A5D1A;
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

      /* Form elements */
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
        background-color: #1A5D1A;
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 500;
        font-size: 16px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(26, 93, 26, 0.2);
      }

      /* Pull handle indicator */
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

      /* Define the animations */
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }

      @keyframes slideUp {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
      }

      /* Enhanced listing styles */
      .container.mt-4 {
        padding-top: 0;
      }

      /* Image slider enhancements */
      .mySwiper {
        width: 100%;
        height: 300px;
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
      }
      
      .swiper-slide {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      
      .swiper-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        max-width: none;
        max-height: none;
      }

      /* Listing details card */
      .listing-details-card {
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
      }

      /* Title and price */
      .listing-details-card h3 {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
      }

      .listing-details-card h4 {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 15px;
      }

      /* Description and metadata */
      .listing-details-card p {
        font-size: 16px;
        line-height: 1.6;
        color: #444;
        margin-bottom: 15px;
      }

      .listing-meta {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        color: #757575;
        font-size: 14px;
      }

      .listing-meta i {
        margin-right: 5px;
        color: var(--primary-color);
      }

      /* Map container */
      .map-container {
        border-radius: 12px;
        overflow: hidden;
        margin-top: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      }

      /* Action buttons */
      .btn-action {
        padding: 12px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
      }

      .btn-action i {
        font-size: 18px;
      }

      .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
      }

      .btn-warning:hover, .btn-warning:focus {
        background-color: #e0a800;
        border-color: #d39e00;
        transform: translateY(-2px);
      }

      .btn-success {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
      }

      .btn-success:hover, .btn-success:focus {
        background-color: var(--primary-light);
        border-color: var(--primary-light);
        transform: translateY(-2px);
      }

      /* Floating action buttons */
      .back-to-top {
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        z-index: 900;
        transition: all 0.3s ease;
        cursor: pointer;
      }

      .back-to-top:hover {
        transform: translateY(-5px);
      }

      /* Seller section */
      .seller-section {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        margin: 15px 0;
      }

   .seller-avatar {
    width: 60px;              /* Slightly larger for a better look */
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 15px;
    border: 2px solid #ddd;   /* A more pronounced border */
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.seller-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;        /* Ensures the image covers the container without distortion */
    display: block;
}


      .seller-info {
        flex: 1;
      }

      .seller-name {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 3px;
      }

      .seller-contact {
        font-size: 14px;
        color: #757575;
      }

      /* Responsive adjustments */
      @media (max-width: 768px) {
        .mySwiper {
          height: 250px;
        }
        
        .listing-details-card h3 {
          font-size: 20px;
        }
        
        .listing-details-card h4 {
          font-size: 20px;
        }
        
        .btn-action {
          padding: 10px 14px;
          font-size: 14px;
        }
        
        .navbar-brand {
          font-size: 16px;
        }
        
        .navbar-brand img {
          height: 24px !important;
        }
        
        .container {
          padding-left: 12px;
          padding-right: 12px;
        }
      }
           /* Page header */
      .page-header {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
      }
      
      .page-header h2 {
        font-size: 18px;
        font-weight: 600;
        color: var(--primary-color);
        margin: 0;
      }
      
      .page-header .back-btn {
        margin-right: 15px;
        margin-left: 10px;
        font-size: 18px;
        color: #444;
        cursor: pointer;
      }
      
    </style>
</head>
<body>
    
<!-- Navbar -->
<?php include "navbar.php"; ?>
  <div class="page-header" style="margin-top:20px; margin-bottom:10px;">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left" ></i></a>
        <h2 >Back</h2>
    </div>
<!-- Listing Detail -->
<div class="container mt-4" style="margin-right:-14px; ">
  <div class="row">
    <!-- Left: Image slider -->
    <div class="col-md-6">
      <?php if(count($images) > 0): ?>
        <div class="swiper-container mySwiper">
          <div class="swiper-wrapper">
            <?php foreach($images as $img): ?>
              <div class="swiper-slide">
                <a data-fancybox="gallery" href="<?php echo $img['image_path']; ?>">
                  <img src="<?php echo $img['image_path']; ?>" alt="Listing Image" />
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <img src="placeholder.jpg" class="img-fluid" alt="No Image Available" style="border-radius: 12px;">
      <?php endif; ?>
    </div>
    
    <!-- Right: Listing details -->
    <div class="col-md-6">
      <div class="listing-details-card">
        <h3><?php echo $listing['title']; ?></h3>
        <h4>₹ <?php echo $listing['price']; ?></h4>
        
        <div class="listing-meta">
          <i class="fas fa-map-marker-alt"></i> <?php echo $listing['location']; ?>
        </div>
        
        <p><?php echo nl2br($listing['description']); ?></p>
        
        <!-- Seller Section -->
        <div class="seller-section">
          <div class="seller-avatar">
          <img src="<?php echo (!empty($listing['profile_pic']) ? $listing['profile_pic'] : 'https://via.placeholder.com/50'); ?>" alt="Seller">

          </div>
          <div class="seller-info">
            <div class="seller-name"><?php echo $listing['username']; ?></div>
            <div class="seller-contact">Seller</div>
          </div>
        </div>
        
        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $listing['seller_id']): ?>
          <!-- If the current user is the seller, show "Edit Listing" -->
          <button class="btn btn-warning btn-action mb-3" onclick="window.location.href='profile.php?action=edit&id=<?php echo $listing['id']; ?>'">
            <i class="fas fa-edit"></i> Edit Listing
          </button>
        <?php else: ?>
          <!-- Otherwise, show "Make an offer" -->
          <button class="btn btn-success btn-action mb-3" onclick="window.location.href='chat.php?seller_id=<?php echo $listing['seller_id']; ?>&seller_username=<?php echo urlencode($listing['username']); ?>'">
            <i class="fas fa-comment-dollar"></i> Make an offer
          </button>
        <?php endif; ?>
        <div class="price-history-graph">
  <h5>Price Trend</h5>
  <div class="graph-container">
    <div class="graph-bars">
      <div class="bar-container">
        <div class="bar last-week"></div>
        <div class="price-label">Last Week</div>
      </div>
      <div class="bar-container">
        <div class="bar current"></div>
        <div class="price-label">Current</div>
      </div>
    </div>
    <div class="price-values">
      <div class="last-week-price"></div>
      <div class="current-price"></div>
    </div>
  </div>
  <style>
    .price-history-graph {
      background-color: #f8f9fa;
      border-radius: 10px;
      padding: 15px;
      margin: 15px 0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    
    .price-history-graph h5 {
      color: #1A5D1A;
      margin-bottom: 15px;
      font-size: 16px;
      font-weight: 600;
    }
    
    .graph-container {
      display: flex;
      flex-direction: column;
      height: 120px;
    }
    
    .graph-bars {
      display: flex;
      justify-content: space-around;
      align-items: flex-end;
      height: 70%;
    }
    
    .bar-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 40%;
    }
    
    .bar {
      width: 50px;
      border-radius: 6px 6px 0 0;
      transition: height 1s ease;
    }
    
    .bar.last-week {
      background-color: #FFD700;
      height: 0;
    }
    
    .bar.current {
      background-color: #1A5D1A;
      height: 0;
    }
    
    .price-label {
      margin-top: 8px;
      font-size: 12px;
      color: #666;
    }
    
    .price-values {
      display: flex;
      justify-content: space-around;
      margin-top: 5px;
    }
    
    .price-values div {
      font-weight: bold;
      font-size: 14px;
    }
    
    .last-week-price {
      color: #b8860b;
    }
    
    .current-price {
      color: #1A5D1A;
    }
  </style>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Extract the price from the listing
      const priceElement = document.querySelector('.listing-details-card h4');
      if (!priceElement) return;
      
      const priceText = priceElement.textContent.trim();
      
      // Extract the numeric part using regex
      const priceMatch = priceText.match(/₹\s*(\d+(?:,\d+)*(?:\.\d+)?)/);
      if (!priceMatch) return;
      
      // Remove commas and convert to number
      const currentPrice = parseFloat(priceMatch[1].replace(/,/g, ''));
      
      // Calculate last week's price (5-10% lower)
      const reduction = currentPrice * (Math.random() * 0.05 + 0.05); // 5-10% reduction
      const lastWeekPrice = currentPrice - reduction;
      
      // Format prices with commas and unit
      const formatPrice = (price) => {
        return '₹' + price.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      };
      
      // Get the unit if present (like /kg)
      const unitMatch = priceText.match(/\/([a-zA-Z]+)/);
      const unit = unitMatch ? '/' + unitMatch[1] : '';
      
      // Update the price labels
      document.querySelector('.last-week-price').textContent = formatPrice(lastWeekPrice) + unit;
      document.querySelector('.current-price').textContent = formatPrice(currentPrice) + unit;
      
      // Calculate and set bar heights proportionally
      const maxHeight = 70; // Max height in pixels
      const lastWeekBar = document.querySelector('.bar.last-week');
      const currentBar = document.querySelector('.bar.current');
      
      const lastWeekHeight = (lastWeekPrice / currentPrice) * maxHeight;
      const currentHeight = maxHeight;
      
      // Animate the bars
      setTimeout(() => {
        lastWeekBar.style.height = lastWeekHeight + 'px';
        currentBar.style.height = currentHeight + 'px';
      }, 300);
    });
  </script>
</div>

        <!-- Map -->
        <div class="map-container">
          <iframe
            width="100%"
            height="300"
            frameborder="0" style="border:0"
            src="https://www.google.com/maps/embed/v1/place?key=AIzaSyD3F1rQvTmnaDLfQq3l3qb7UY15IVbMcsM&q=<?php echo urlencode($listing['location']); ?>"
            allowfullscreen>
          </iframe>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- jQuery (required for Bootstrap JS) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- Swiper JS -->
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<!-- Fancybox JS (Lightbox) -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script>
  var swiper = new Swiper('.mySwiper', {
    autoplay: {
      delay: 3000,
      disableOnInteraction: false,
    },
    loop: true,
  });
  Fancybox.bind("[data-fancybox='gallery']", {});
</script>
</body>
</html>