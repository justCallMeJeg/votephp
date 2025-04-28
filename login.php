<?php
session_start();
require_once 'classes/VotingSystem.php';

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
        exit;
    } else {
        header('Location: voter.php');
        exit;
    }
}

$votingSystem = new VotingSystem();
$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $user = $votingSystem->authenticateUser($username, $password);
    
    if ($user) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: admin.php');
            exit;
        } else {
            header('Location: voter.php');
            exit;
        }
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PHP Voting App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">PHP Voting Application</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title mb-4">Login</h4>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <p class="text-muted">Don't have an account? Contact administrator.</p>
                            <div class="small text-muted mt-3">
                                <strong>Demo accounts:</strong><br>
                                Admin: admin / admin123<br>
                                Voter: voter / voter123
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
