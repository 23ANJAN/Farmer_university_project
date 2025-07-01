<?php
session_start();
include 'db.php';
include 'conf.php';
$sql = "SELECT l.*, 
       (SELECT image_path FROM listing_images WHERE listing_id = l.id LIMIT 1) AS main_image
       FROM listings l
       WHERE liked = 1
       ORDER BY date_posted DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1A5D1A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Liked Listings - <?php echo $CompanyName; ?></title>
    <link rel="icon" href="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" type="image/x-icon">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        --heart-color: #ff3b5c;    /* Heart red */
        --heart-color-hover: #ff0037; /* Heart red hover */
        --light-color: #f9f9f9;
        --text-color: #333;
        --card-shadow: 0 2px 8px rgba(0,0,0,0.1);
        --card-shadow-hover: 0 8px 16px rgba(0,0,0,0.15);
        --transition-speed: 0.3s;
        --card-radius: 12px;
        
        /* Z-index hierarchy */
        --z-background: 1;
        --z-content: 10;
        --z-card: 20;
        --z-buttons: 30;
        --z-navbar: 1000;
        --z-bottomnav: 1000;
      }
      
      * {
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
      
      /* Fixed navbar & bottom nav should have this z-index */
      .navbar, .bottom-nav {
        z-index: var(--z-navbar);
      }
      
      .container {
        position: relative;
        z-index: var(--z-content);
      }
      
      /* Page header */
      .page-header {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
      }
      
      .page-header h2 {
        font-size: 24px;
        font-weight: 600;
        color: var(--primary-color);
        margin: 0;
      }
      
      .page-header .back-btn {
        margin-right: 12px;
        font-size: 20px;
        color: #444;
        cursor: pointer;
      }
      
      /* Card styles */
      .card {
        border: none;
        border-radius: var(--card-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
        transition: all var(--transition-speed) ease;
        height: 100%;
        position: relative;
        z-index: var(--z-card);
      }
      
      .card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow-hover);
      }
      
      .card-img-top {
        height: 180px;
        object-fit: cover;
        transition: transform 0.5s ease;
      }
      
      .card:hover .card-img-top {
        transform: scale(1.05);
      }
      
      .card-body {
        padding: 15px;
      }
      
      .card-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      
      .card-text {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 12px;
      }
      
      .card-footer {
        padding: 12px 15px;
        background: white;
        border-top: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      
      /* Button styling */
      .btn-view {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        border-radius: 8px;
        font-size: 0.9rem;
        padding: 6px 12px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        position: relative;
        z-index: var(--z-buttons);
      }
      
      .btn-view:hover, .btn-view:focus {
        background-color: var(--primary-light);
        border-color: var(--primary-light);
        transform: translateY(-2px);
      }
      
      .btn-view i {
        margin-right: 5px;
      }
      
      /* Heart button */
      .like-btn {
        cursor: pointer;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
        border: none;
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: var(--z-buttons);
      }
      
      .like-btn i {
        font-size: 1.2rem;
        color: var(--heart-color);
        transition: all 0.2s ease;
      }
      
      .like-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 3px 8px rgba(0,0,0,0.15);
      }
      
      .like-btn:hover i {
        color: var(--heart-color-hover);
      }
      
      .like-btn.animate i {
        animation: heartBeat 0.3s;
      }
      
      @keyframes heartBeat {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
      }
      
      /* Empty state styling */
      .empty-state {
        background-color: white;
        border-radius: var(--card-radius);
     
        padding: 40px 20px;
        text-align: center;
        margin-bottom: 20px;
      }
      
      .empty-state-icon {
        font-size: 60px;
        color: #ddd;
        margin-bottom: 20px;
      }
      
      .empty-state h3 {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #444;
      }
      
      .empty-state p {
        color: #777;
        margin-bottom: 20px;
      }
      
      .btn-browse {
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-weight: 500;
        font-size: 1rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        margin: 0 auto;
      }
      
      .btn-browse:hover {
        background-color: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(26, 93, 26, 0.2);
        color: white;
        text-decoration: none;
      }
      
      .btn-browse i {
        margin-right: 8px;
      }
      
      /* Fadeout animation for cards */
      .fade-out {
        animation: fadeOut 0.5s forwards;
      }
      
      @keyframes fadeOut {
        from { opacity: 1; transform: scale(1); }
        to { opacity: 0; transform: scale(0.8); }
      }
      
      /* Location badge */
      .location-badge {
        position: absolute;
        bottom: 180px;
        left: 10px;
        background-color: rgba(255,255,255,0.9);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: #555;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        max-width: calc(100% - 20px);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        z-index: var(--z-buttons);
      }
      
      .location-badge i {
        margin-right: 4px;
        color: var(--primary-color);
      }
      
      /* Responsive adjustments */
      @media (max-width: 767px) {
        .col-md-3 {
          margin-bottom: 15px;
        }
        
        .card-img-top {
          height: 150px;
        }
        
        .location-badge {
          bottom: 150px;
        }
        
        .page-header h2 {
          font-size: 20px;
        }
        
        .container {
          padding-left: 10px;
          padding-right: 10px;
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
  z-index: 999999; 
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
<div class="container" style="margin-right:-16px; ">
    <div class="page-header" style="margin-top:20px; margin-bottom:10px;">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left" ></i></a>
        <h2 >Liked Listings</h2>
    </div>
    
    <div class="row">
        <?php if($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-6 col-md-3 mb-4" id="listing-<?php echo $row['id']; ?>">
                    <div class="card">
                        <?php if(!empty($row['main_image'])): ?>
                            <img src="<?php echo $row['main_image']; ?>" class="card-img-top" alt="Listing">
                        <?php else: ?>
                            <img src="placeholder.jpg" class="card-img-top" alt="No Image">
                        <?php endif; ?>
                        
                        <!-- Location badge if available -->
                        <?php if(!empty($row['location']) || (!empty($row['city']) && !empty($row['state']))): ?>
                            <div class="location-badge">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo !empty($row['location']) ? $row['location'] : $row['city'] . ', ' . $row['state']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Like button -->
                        <button class="like-btn" data-listing-id="<?php echo $row['id']; ?>">
                            <i class="fas fa-heart"></i>
                        </button>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $row['title']; ?></h5>
                            <p class="card-text">₹ <?php echo $row['price']; ?></p>
                            <a href="listing-details.php?id=<?php echo $row['id']; ?>" class="btn btn-view btn-block">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="far fa-heart"></i>
                    </div>
                    <h3>No liked listings yet</h3>
                    <p>Items you like will appear here so you can easily find them later.</p>
                    <a href="index.php" class="btn-browse">
                        <i class="fas fa-search"></i> Browse Listings
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
  // When a like button is clicked in liked.php, toggle the like status
  $(document).on('click', '.like-btn', function(){
      var btn = $(this);
      var listingId = btn.data('listing-id');
      
      // Add animation class
      btn.addClass('animate');
      
      $.ajax({
          url: 'toggle_like.php',
          type: 'POST',
          dataType: 'json',
          data: { listing_id: listingId },
          success: function(response) {
              if(response.status === 'ok'){
                  // If the listing is now unliked, remove its card from the liked page.
                  if(response.liked == 0){
                      $("#listing-" + listingId).addClass('fade-out');
                      setTimeout(function() {
                          $("#listing-" + listingId).remove();
                          
                          // Check if there are no more listings
                          if ($('.card').length === 0) {
                              // Refresh the page to show empty state
                              location.reload();
                          }
                      }, 500);
                  }
              } else {
                  alert("Error: " + response.error);
              }
              
              // Remove animation class after animation completes
              setTimeout(function() {
                  btn.removeClass('animate');
              }, 300);
          },
          error: function(xhr, status, error){
              alert("An error occurred: " + error);
              btn.removeClass('animate');
          }
      });
  });
</script>
<script>
window.addEventListener('load', function() {
  const stateInput = document.querySelector("input[name='location']");
  const cityInput = document.querySelector("input[name='city']");
  if (stateInput && cityInput && stateInput.value.trim() === "" && cityInput.value.trim() === "" && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;
      fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
        .then(response => response.json())
        .then(data => {
          if (data.address) {
            if (stateInput.value.trim() === "" && data.address.state) {
              stateInput.value = data.address.state;
            }
            const city = data.address.city || data.address.town || data.address.village || "";
            if (cityInput.value.trim() === "" && city !== "") {
              cityInput.value = city;
            }
          }
        })
        .catch(err => console.error("Reverse geocoding error:", err));
    }, function(error) {
      console.error("Error getting geolocation:", error);
    });
  }
});
</script>
</body>
</html>