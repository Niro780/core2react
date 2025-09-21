<?php
session_start();
require_once "config.php";

// Session timeout duration (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Check if session has timed out
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Session expired
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    } else {
        // Update last activity
        $_SESSION['last_activity'] = time();
        header("Location: homes.php");
        exit();
    }
}

// Generate CSRF token once per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (!isset($_POST["csrf_token"]) || $_POST["csrf_token"] !== $_SESSION["csrf_token"]) {
        header("Location: login.php");
        exit();
    }

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields!";
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, name, email, password FROM sellers WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Save user session
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['name']        = $user['name'];
                $_SESSION['role']        = 'seller';
                $_SESSION['seller_id']   = $user['id'];
                $_SESSION['last_activity'] = time(); // Start session timer

                header("Location: homes.php");
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg p-4">
                <h3 class="text-center mb-4">Login</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if(isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning">Your session has expired due to inactivity. Please log in again.</div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <p class="text-center mt-3">Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
