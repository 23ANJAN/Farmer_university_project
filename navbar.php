<?php
// navbar.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If the user is logged in, fetch unread message count
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    include 'db.php';
    $currentUserId = $_SESSION['user_id'];
    $sqlUnread = "SELECT COUNT(*) as unread_count FROM messages WHERE to_user_id = ? AND is_read = 0";
    $stmtUnread = $conn->prepare($sqlUnread);
    $stmtUnread->bind_param('i', $currentUserId);
    $stmtUnread->execute();
    $resultUnread = $stmtUnread->get_result();
    if ($row = $resultUnread->fetch_assoc()) {
        $unread_count = $row['unread_count'];
    }
}
?>
<!-- Top Navigation (Logo + Search + Location) -->
 <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <!-- Brand/Logo -->
      <a class="navbar-brand" href="index.php">
        <img src="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0"
             alt="Logo" style="height: 30px; margin-right: 10px;">
        <?php echo $CompanyName; ?>
      </a>

      <!-- Toggler for mobile -->
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNew"
              aria-controls="navbarNew" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Collapsible content -->
      <div class="collapse navbar-collapse" id="navbarNew">
        <!-- Location & Search Form -->
        <form class="form-inline mr-auto" action="index.php" method="get" style="width: calc(100% - 40px);">
          <!-- State Input -->
          <input list="states" class="form-control mr-2" name="location" placeholder="Enter state"
                 value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>">
          <datalist id="states">
    <option value="Karnataka">
</datalist>

          <!-- City Input -->
          <input list="cities" class="form-control mr-2" name="city" placeholder="Enter city"
                 value="<?php echo isset($_GET['city']) ? htmlspecialchars($_GET['city']) : ''; ?>">
       <datalist id="cities">
    <option value="Bengaluru">
</datalist>

          <!-- Search Input -->
          <input class="form-control mr-2" type="search" name="q" placeholder="Search" aria-label="Search"
                 value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">

          <!-- Search Button -->
          <button class="btn btn-outline-primary" type="submit">
            <img src="./files/search.svg" alt="Search" style="height: 16px; width: auto; vertical-align: middle;">
          </button>
     </form>
        <!-- Cart Icon with Counter -->
    <div id="cart-icon-container" style="position: absolute; top: 15px; right: 20px; cursor: pointer; z-index: 1000;">
  <i class="fas fa-shopping-cart" style="font-size: 20px; color: var(--primary-color);"></i>
  <span id="cart-counter" style="position: absolute; top: -8px; right: -8px; background-color: #ff5722; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; display: none;">0</span>
</div>

      </div>
    </nav>

<!-- Bottom Navigation CSS -->
<style>
  .bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0; 
    right: 0;
    height: 60px;
    background-color: #fff;
    display: flex;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 -1px 5px rgba(0,0,0,0.1);
 
  }
  .bottom-nav a {
    color: #333;
    text-decoration: none;
    font-size: 0.9rem;
    text-align: center;
    flex: 1;
  }
  .bottom-nav .nav-icon {
    display: block;
    font-size: 1.2rem;
  }
  .sell-btn-wrapper {
    position: relative;
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .sell-btn {
    position: absolute;
    bottom: 15px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #007bff;
    color: #fff;
    border: none;
    font-size: 0.9rem;
    text-align: center;
    line-height: 60px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
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

body {
  font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
  margin: 0;
  padding: 0;
  padding-bottom: 70px; /* Space for bottom nav */
  background-color: #f5f5f5;
}

/* App Header Styling */
.navbar {
  background-color: white !important;
  padding: 10px 15px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
z-index: 99999; 
  display: flex;
  flex-direction: column;
  height: auto;
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
  content: "Karnataka, Bengaluru ▼";  /* Changed from "Select Location ▼" */
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
  box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
  z-index: 99999; 
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
  right: 23%; /*  redd  */
}
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
  z-index: 2147483647 !important;
}
.location-modal.show {
  display: block !important;
  animation: fadeIn 0.3s forwards;
  z-index: 2147483647 !important;
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
  max-height: 80vh;
  overflow-y: auto;
  z-index: 2147483647 !important;
}
.location-modal.show .location-modal-content {
  animation: slideUp 0.3s forwards;
  transform: translateY(0) !important;
  opacity: 1;
  visibility: visible;
  z-index: 999999999999999999999999999;
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

body {
  padding-top: 100px;
}

/* Loading Animation */
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

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .navbar-brand {
    font-size: 16px;
  }
  
  .navbar-brand img {
    height: 24px !important;
  }
}

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
  z-index: 2147483647 !important;
}
.location-modal.show {
  display: block !important;
  animation: fadeIn 0.3s forwards;
  z-index: 2147483647 !important;
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
  max-height: 80vh;
  overflow-y: auto;
  z-index: 2147483647 !important;
}
.location-modal.show .location-modal-content {
  animation: slideUp 0.3s forwards;
  transform: translateY(0) !important;
  opacity: 1;
  visibility: visible;
  z-index: 999999999999999999999999999;
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

</style>

<!-- Bottom Navigation -->
<?php if (isset($_SESSION['user_id'])): ?>
  <!-- Show this bottom nav if user is logged in -->
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
</div>
<?php else: ?>
  <!-- Show this bottom nav if user is logged out -->
  <div class="bottom-nav">
    <a href="login.php">
      <span class="nav-icon"><i class="fas fa-sign-in-alt"></i></span>
      Login
    </a>
  </div>
<?php endif; ?>

<!-- Optional auto-detect script -->
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

<script>
// Location modal handling code
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
  const modal = document.createElement('div');
  modal.className = 'location-modal';
  modal.innerHTML = `
    <div class="location-modal-content">
      <div class="location-modal-header">
        <h3 class="location-modal-title">Select Location</h3>
        <button class="location-close-btn">&times;</button>
      </div>
        <form class="location-form" action="index.php" method="get">
      <label for="modal-state">State</label>
      <select id="modal-state" name="location">
        <option value="Karnataka" selected>Karnataka</option>
      </select>
      
      <label for="modal-city">City</label>
      <select id="modal-city" name="city">
        <option value="Bengaluru" selected>Bengaluru</option>
      </select>
      
      <label for="modal-district">District</label>
      <select id="modal-district" name="district">
        <option value="">Select District</option>
        <option value="Koramangala">Koramangala</option>
        <option value="Indiranagar">Indiranagar</option>
        <option value="Jayanagar">Jayanagar</option>
        <option value="Whitefield">Whitefield</option>
        <option value="Electronic City">Electronic City</option>
        <option value="Malleshwaram">Malleshwaram</option>
        <option value="HSR Layout">HSR Layout</option>
        <option value="BTM Layout">BTM Layout</option>
        <option value="JP Nagar">JP Nagar</option>
        <option value="Banashankari">Banashankari</option>
      </select>
      
      <!-- Search query will be preserved by JavaScript -->
      
      <button type="submit">Apply</button>
    </form>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Open modal
// Open modal only when clicking the location selector
form.addEventListener('click', function(e) {
  // Get the position of the location text at the top
  const formBounds = form.getBoundingClientRect();
  const isLocationArea = e.clientY - formBounds.top < 0;
  
  // Only open modal if clicking in the location area at the top
  // and NOT on search input or search button
  if ((isLocationArea && !e.target.matches('input, button')) || 
      e.target.matches('.form-inline::before') || 
      e.target.matches('.form-inline::after')) {
    modal.classList.add('show');
    e.preventDefault();
    e.stopPropagation();
  }
});
  
  // Close modal with close button
  modal.querySelector('.location-close-btn').addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    modal.classList.remove('show');
  });
  
  // Close when clicking outside
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      modal.classList.remove('show');
    }
  });
  
  // Prevent clicks inside from closing
  modal.querySelector('.location-modal-content').addEventListener('click', function(e) {
    e.stopPropagation();
  });
}

// Call the setup function when the page loads
document.addEventListener('DOMContentLoaded', setupLocationModal);


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


