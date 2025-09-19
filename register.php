<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists!";
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);
                
                $success = "Registration successful! You can now login.";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: skyblue;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
      padding: 1rem 0;
    }

    .register-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      padding: 2.5rem;
      width: 100%;
      max-width: 450px;
      margin: 1rem;
    }

    .brand-logo {
      width: 60px;
      height: 60px;
     
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }

    .brand-logo img {
      height:70px;
      width:70px;
    }

    h2 {
      text-align: center;
      margin-bottom: 2rem;
      color: #333;
      font-weight: 600;
    }

    .form-control {
      border: 2px solid #e0e6ed;
      border-radius: 10px;
      height: 50px;
      margin-bottom: 1rem;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }

    .btn-register {
      background:skyblue;
      border: none;
      border-radius: 10px;
      height: 50px;
      font-weight: 600;
      transition: transform 0.3s ease;
    }

    .btn-register:hover {
      transform: translateY(-2px);
    }

    .alert-custom {
      background: rgba(220, 53, 69, 0.1);
      border: 1px solid rgba(220, 53, 69, 0.2);
      border-radius: 10px;
      color: #dc3545;
      margin-bottom: 1rem;
      padding: 0.75rem;
      text-align: center;
    }

    .alert-success {
      background: rgba(25, 135, 84, 0.1);
      border: 1px solid rgba(25, 135, 84, 0.2);
      border-radius: 10px;
      color: #198754;
      margin-bottom: 1rem;
      padding: 0.75rem;
      text-align: center;
    }

    .text-center a {
      color: #667eea;
      text-decoration: none;
    }

    .text-center a:hover {
      color: #764ba2;
    }

    .password-strength {
      height: 4px;
      border-radius: 2px;
      margin-top: 0.5rem;
      background: #e9ecef;
      overflow: hidden;
    }

    .strength-bar {
      height: 100%;
      transition: all 0.3s ease;
      border-radius: 2px;
    }

    .strength-weak { background: #dc3545; width: 33%; }
    .strength-medium { background: #ffc107; width: 66%; }
    .strength-strong { background: #198754; width: 100%; }

    /* Mobile responsive */
    @media (max-width: 480px) {
      .register-card {
        padding: 1.5rem;
        margin: 0.5rem;
        border-radius: 15px;
      }
      
      h2 {
        font-size: 1.5rem;
      }
      
      .form-control {
        height: 45px;
      }
      
      .btn-register {
        height: 45px;
      }
    }
  </style>
</head>
<body>
  <div class="register-card">
    <div class="brand-logo">
    <img src="images/logo.png" alt="Logo" class="logo-img">
    </div>
    
    <h2>Create Account</h2>
    
    <?php if ($error): ?>
      <div class="alert-custom">
        <i class="bi bi-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
      <div class="alert-success">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="registerForm">
      <input type="text" class="form-control" name="username" placeholder="Username" 
             value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
      
      <input type="email" class="form-control" name="email" placeholder="Email Address" 
             value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
      
      <input type="password" class="form-control" name="password" placeholder="Password" 
             id="password" required>
      <div class="password-strength">
        <div class="strength-bar" id="strengthBar"></div>
      </div>
      <small class="text-muted">Minimum 6 characters</small>
      
      <input type="password" class="form-control mt-2" name="confirm_password" 
             placeholder="Confirm Password" id="confirmPassword" required>
      
      <button type="submit" class="btn btn-register w-100 text-white mt-3">
        Create Account
      </button>
    </form>
    
    <div class="text-center mt-3">
      <small>Already have an account? <a href="login.php">Sign in here</a></small>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Password strength checker
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const strengthBar = document.getElementById('strengthBar');
      
      if (password.length === 0) {
        strengthBar.className = 'strength-bar';
        return;
      }
      
      let strength = 0;
      if (password.length >= 6) strength++;
      if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
      if (password.match(/\d/)) strength++;
      if (password.match(/[^a-zA-Z\d]/)) strength++;
      
      if (strength <= 2) {
        strengthBar.className = 'strength-bar strength-weak';
      } else if (strength === 3) {
        strengthBar.className = 'strength-bar strength-medium';
      } else {
        strengthBar.className = 'strength-bar strength-strong';
      }
    });

    // Password confirmation checker
    document.getElementById('confirmPassword').addEventListener('input', function() {
      const password = document.getElementById('password').value;
      const confirmPassword = this.value;
      
      if (confirmPassword && password !== confirmPassword) {
        this.style.borderColor = '#dc3545';
      } else {
        this.style.borderColor = '#e0e6ed';
      }
    });
  </script>
</body>
</html>