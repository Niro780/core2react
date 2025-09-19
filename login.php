<?php
// Add your login logic here
$error = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields!";
    } else {
        // Real database authentication
        try {
            require_once 'config.php';
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                echo "<script>alert('Login successful! Welcome " . htmlspecialchars($user['username']) . "'); window.location.href='homes.php';</script>";
                exit;
            } else {
                $error = "Invalid username or password!";
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
  <title>Login</title>
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
    }
    
    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      padding: 2.5rem;
      width: 100%;
      max-width: 400px;
      margin: 1rem;
    }
    
    .brand-logo {
      width: 60px;
      height: 60px;
    
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
      border-color: skyblue;
      box-shadow: 0 0 0 0.2rem rgba(135, 206, 235, 0.15);
    }
    
    .btn-login {
      background: skyblue;
      border: none;
      border-radius: 10px;
      height: 50px;
      font-weight: 600;
      color: white;
      transition: transform 0.3s ease;
    }
    
    .btn-login:hover {
      background: #87ceeb;
      transform: translateY(-2px);
    }
    
    .error-message {
      background: rgba(220, 53, 69, 0.1);
      border: 1px solid rgba(220, 53, 69, 0.2);
      border-radius: 10px;
      color: #dc3545;
      padding: 0.75rem;
      margin-bottom: 1rem;
      text-align: center;
    }
    
    .text-center a {
      color: skyblue;
      text-decoration: none;
    }
    
    .text-center a:hover {
      color: #87ceeb;
    }
    
    /* Mobile responsive */
    @media (max-width: 480px) {
      .login-card {
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
      
      .btn-login {
        height: 45px;
      }
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand-logo">
     <img src="images/logo.png" alt="Logo" class="logo-img">
    </div>
    
    
    
    <?php if (!empty($error)): ?>
      <div class="error-message">
        <i class="bi bi-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <form method="POST" action="">
      <input type="text" class="form-control" name="username" 
             placeholder="Username or Email" 
             value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
             required>
      <input type="password" class="form-control" name="password" 
             placeholder="Password" required>
      <button type="submit" class="btn btn-login w-100">Sign In</button>
    </form>
    
    <div class="text-center mt-3">
      <small>Don't have an account? <a href="register.php">Register here</a></small>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>