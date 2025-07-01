<?php
session_start();
include 'db.php';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $username, $email, $password);
    if($stmt->execute()){
        // Log the user in immediately
        $_SESSION['user_id'] = $stmt->insert_id;
        header('Location: index.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
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
    <title>Register</title>
    <link rel="icon" href="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" type="image/x-icon">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #1A5D1A;  /* Farmer green theme */
            --primary-light: #2a8d2a;  /* Lighter green for hover */
            --secondary-color: #FFD700; /* Gold accent */
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
            --z-form-control: 30;
            --z-form-icon: 35;
            --z-overlay: 50;
        }
        
        * {
            position: relative;
            z-index: var(--z-background);
        }
        
body {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
    background-color: white !important;
    background-image: none !important;
    background: white !important;
    background-attachment: fixed;
    color: var(--text-color);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    -webkit-tap-highlight-color: transparent;
}
        .app-container {
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.5s ease;
            z-index: var(--z-content);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .app-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .app-logo img {
            height: 70px;
            width: auto;
        }
        
        .app-title {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .app-title h1 {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .app-title p {
            color: #666;
            font-size: 16px;
        }
        
        .form-card {
            background-color: white;
            border-radius: var(--card-radius);
           
            padding: 30px;
            margin-bottom: 20px;
            z-index: var(--z-card);
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            z-index: var(--z-form-control);
        }
        
        .form-group label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            height: auto;
            padding: 12px 15px 12px 45px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 93, 26, 0.1);
            outline: none;
        }
        
        .form-icon {
            position: absolute;
            left: 15px;
            top: 42px;
            color: #aaa;
            font-size: 18px;
            transition: all 0.2s ease;
            z-index: var(--z-form-icon);
        }
        
        .form-control:focus + .form-icon {
            color: var(--primary-color);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            color: #aaa;
            font-size: 16px;
            cursor: pointer;
            z-index: var(--z-form-icon);
        }
        
        .password-toggle:hover {
            color: #666;
        }
        
        .btn-register {
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
            margin-top: 10px;
        }
        
        .btn-register:hover, .btn-register:focus {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 93, 26, 0.2);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
            color: #666;
        }
        
        .login-link a {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        /* Form validation styling */
        .form-control.is-valid {
            border-color: #28a745;
            background-image: none;
            padding-right: 15px;
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: none;
            padding-right: 15px;
        }
        
        .valid-feedback, .invalid-feedback {
            position: absolute;
            font-size: 12px;
            margin-top: 5px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .form-card {
                padding: 20px;
            }
            
            .app-title h1 {
                font-size: 24px;
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
    <div class="app-container">
        <div class="app-logo">
            <img src="https://lh3.googleusercontent.com/3OQwwHdr24p7tomSrSxiXkt5kxExHlXzJaMQM4Eq5T-sBnEDZpxig0W-7FrAQvjKGz2mvFFk4-zetsiR19K-Cxt7Ec96zrsdtReuFcSsT0HFZCjnXKW0" alt="Logo">
        </div>
        
        <div class="app-title">
            <h1>Create Account</h1>
            <p>Join our community today</p>
        </div>
        
        <div class="form-card">
            <form method="post" action="" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" required minlength="3" autocomplete="username">
                    <i class="fas fa-user form-icon"></i>
                    <div class="valid-feedback">Looks good!</div>
                    <div class="invalid-feedback">Please choose a username (min 3 characters).</div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control" required autocomplete="email">
                    <i class="fas fa-envelope form-icon"></i>
                    <div class="valid-feedback">Looks good!</div>
                    <div class="invalid-feedback">Please provide a valid email.</div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required minlength="6" autocomplete="new-password">
                    <i class="fas fa-lock form-icon"></i>
                    <i class="fas fa-eye password-toggle" id="passwordToggle"></i>
                    <div class="valid-feedback">Looks good!</div>
                    <div class="invalid-feedback">Password must be at least 6 characters.</div>
                </div>
                
                <button type="submit" class="btn-register">Create Account</button>
            </form>
        </div>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.getElementById('passwordToggle').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Basic form validation
        const form = document.getElementById('registerForm');
        const inputs = form.querySelectorAll('input');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
        });
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Mark all fields as touched
                inputs.forEach(input => {
                    if (!input.checkValidity()) {
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.add('is-valid');
                    }
                });
            }
        });
    </script>
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