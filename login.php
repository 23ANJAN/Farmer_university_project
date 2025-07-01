<?php
session_start();
include 'db.php';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if($user){
        // Verify password
        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            $error_message = "Invalid password!";
        }
    } else {
        $error_message = "No user found with that email!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1A5D1A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Login</title>
    <link rel="icon" href="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" type="image/x-icon">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #1A5D1A;  /* Farmer green theme */
            --primary-light: #2a8d2a;  /* Lighter green for hover */
            --secondary-color: #FFD700; /* Gold accent */
            --error-color: #e74c3c;    /* Error red */
            --light-color: #f9f9f9;
            --text-color: #333;
            --card-shadow: 0 2px 15px rgba(0,0,0,0.1);
            --card-radius: 16px;
            --input-radius: 12px;
            --transition-speed: 0.3s;
            
            /* Z-index hierarchy */
            --z-background: 1;
            --z-content: 10;
            --z-card: 20;
            --z-form-control: 30;
            --z-form-control-focus: 35;
        }
        
        * {
            position: relative;
            z-index: var(--z-background);
            box-sizing: border-box;
        }
        
   html, body {
    height: 100%;
    min-height: 100vh;
    background-color: white !important;
}

body {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
    background: white !important;
    background-image: none !important;
    background-color: white !important;
    color: var(--text-color);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 0;
    -webkit-tap-highlight-color: transparent;
}
        
        .container {
            position: relative;
            z-index: var(--z-content);
            max-width: 450px;
            width: 100%;
            padding: 0 15px;
        }
        
        .login-card {
            background-color: white;
            border-radius: var(--card-radius);
           
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease;
            position: relative;
            z-index: var(--z-card);
            overflow: hidden;
        }
        
    
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }
        
        .login-logo img {
            height: 60px;
            width: auto;
        }
        
        .login-title {
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 25px;
        }
        
        .error-message {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
            40%, 60% { transform: translate3d(3px, 0, 0); }
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            z-index: var(--z-form-control);
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 0.95rem;
        }
        
        .input-icon-wrap {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            transition: color var(--transition-speed);
        }
        
        .form-control {
            height: auto;
            padding: 13px 15px 13px 45px;
            border-radius: var(--input-radius);
            border: 1px solid #ddd;
            font-size: 16px;
            transition: all var(--transition-speed);
            width: 100%;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
            outline: none;
        }
        
        .form-control:focus + .input-icon {
            color: var(--primary-color);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: all var(--transition-speed);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            z-index: var(--z-form-control);
        }
        
        .btn-login::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.6s;
            z-index: -1;
        }
        
        .btn-login:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 93, 26, 0.2);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 25px;
            color: #777;
            font-size: 0.9rem;
        }
        
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition-speed);
        }
        
        .form-footer a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .login-card {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
     <div id="user-type-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(26, 93, 26, 0.95); display: flex; align-items: center; justify-content: center; z-index: 9999; flex-direction: column;">
        <div style="background-color: white; border-radius: 16px; padding: 30px; width: 90%; max-width: 500px; text-align: center;">
            <img src="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" alt="Logo" style="height: 80px; margin-bottom: 20px;">
            <h2 style="color: #1A5D1A; margin-bottom: 30px; font-size: 24px; font-weight: 600;">Welcome to Go Fresh</h2>
            <p style="color: #555; margin-bottom: 25px; font-size: 16px;">Please select how you would like to use our platform:</p>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <button id="seller-btn" style="background-color: #1A5D1A; color: white; border: none; border-radius: 8px; padding: 14px; font-size: 16px; font-weight: 500; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <i class="fas fa-store"></i> I'm a Seller
                </button>
                <button id="buyer-btn" style="background-color: #1A5D1A; color: white; border: none; border-radius: 8px; padding: 14px; font-size: 16px; font-weight: 500; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <i class="fas fa-shopping-bag"></i> I'm a Buyer
                </button>
            </div>
        </div>
    </div>
    
    
<script>
document.addEventListener('DOMContentLoaded', function() {
  const buyerBtn = document.getElementById('buyer-btn');
  const sellerBtn = document.getElementById('seller-btn');
  
  if (buyerBtn) {
    buyerBtn.addEventListener('click', function() {
      // Store user type in localStorage
      localStorage.setItem('goFreshUserType', 'buyer');
      
      // Close the modal (assuming you have a modal)
      // If you're using Bootstrap modal:
      $('#userTypeModal').modal('hide'); // For Bootstrap modal
      // Or if using a custom modal with a close function:
      // closeModal();
      
      // No redirect - user stays on login.php to enter credentials
    });
  }
  
  if (sellerBtn) {
    sellerBtn.addEventListener('click', function() {
      // Store user type in localStorage
      localStorage.setItem('goFreshUserType', 'seller');
      
      // Close the modal
      $('#userTypeModal').modal('hide'); // For Bootstrap modal
      // Or if using a custom modal with a close function:
      // closeModal();
      
      // No redirect - user stays on login.php to enter credentials
    });
  }
  
  // If you have a login form submission, you can add that handler here
  // const loginForm = document.getElementById('login-form');
  // if (loginForm) {
  //   loginForm.addEventListener('submit', function(e) {
  //     // Your login form submission logic
  //     // The userType is already stored in localStorage
  //   });
  // }
});
</script>


    
    
    
    
    
  
    <div class="container">
        <div class="login-card">
            <div class="login-logo">
                <img src="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" alt="Logo">
            </div>
            <h2 class="login-title">Welcome Back</h2>
            
            <?php if(isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-icon-wrap">
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required/>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon-wrap">
                        <input type="password" id="password" name="password" class="form-control" required/>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                <button type="submit" class="btn-login">Login</button>
                
                <div class="form-footer">
                    Don't have an account? <a href="register.php">Register</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show the modal on page load
            const modal = document.getElementById('user-type-modal');
            
            // Handle seller button click
            document.getElementById('seller-btn').addEventListener('click', function() {
                // Store user type preference (could use localStorage if needed)
                localStorage.setItem('userType', 'seller');
                // Hide the modal
                modal.style.display = 'none';
            });
            
            // Handle buyer button click
            document.getElementById('buyer-btn').addEventListener('click', function() {
                // Store user type preference (could use localStorage if needed)
                localStorage.setItem('userType', 'buyer');
                // Hide the modal
                modal.style.display = 'none';
            });
        });
    </script>
</body>
</html>