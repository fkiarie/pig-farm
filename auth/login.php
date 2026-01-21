<?php 
session_start(); 
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login | Pig Farm Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --farm-primary: #198754;
            --farm-secondary: #20c997;
        }

        body {
            background: #f4f7f6;
            background-image: linear-gradient(135deg, rgba(25, 135, 84, 0.9) 0%, rgba(32, 201, 151, 0.8) 100%), 
                              url('https://images.unsplash.com/photo-1516467508483-a7212febe31a?auto=format&fit=crop&q=80&w=1000');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Inter', sans-serif;
        }

        .login-card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            backdrop-filter: blur(5px);
            background: rgba(255, 255, 255, 0.95);
            overflow: hidden;
        }

        .login-header {
            background: var(--farm-primary);
            padding: 2.5rem 1rem;
            text-align: center;
            color: white;
        }

        .farm-logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-floating > .form-control:focus ~ label {
            color: var(--farm-primary);
        }

        .btn-login {
            background: var(--farm-primary);
            color: white;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 0.75rem;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #157347;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            cursor: pointer;
            color: #adb5bd;
            transition: color 0.2s;
        }

        .password-toggle:hover { color: var(--farm-primary); }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4 fade-in">
            
            <div class="card login-card">
                <div class="login-header">
                    <i class="bi bi-piggy-bank-fill farm-logo"></i>
                    <h2 class="h4 fw-bold mb-0">Pig Farm Manager</h2>
                    <small class="opacity-75">Secure Staff Portal</small>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="process_login" method="POST" id="loginForm">
                        
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required autofocus>
                            <label for="email"><i class="bi bi-envelope me-2"></i>Email Address</label>
                        </div>

                        <div class="form-floating mb-4 position-relative">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                            <span class="password-toggle" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4 small">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                                <label class="form-check-label text-muted" for="rememberMe">Remember me</label>
                            </div>
                            <a href="forgot_password.php" class="text-decoration-none text-success">Forgot?</a>
                        </div>

                        <button type="submit" class="btn btn-login w-100 mb-3" id="submitBtn">
                            Sign In <i class="bi bi-arrow-right-short ms-1"></i>
                        </button>

                    </form>
                </div>
                
                <div class="card-footer bg-light border-0 py-3 text-center">
                    <p class="text-muted small mb-0">&copy; <?= date('Y') ?> Vemico Tech. All rights reserved.</p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const loginForm = document.querySelector('#loginForm');
    const submitBtn = document.querySelector('#submitBtn');

    // Password visibility toggle
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });

    // Submit state handling
    loginForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Authenticating...`;
    });
});
</script>

</body>
</html>