<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Pig Farm Login</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom Login Page Styles */
        body {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .login-container {
            width: 100%;
            padding: 1rem;
        }

        .login-card {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideUp 0.5s ease;
            background: #ffffff;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .login-header p {
            margin: 0;
            opacity: 0.95;
            font-size: 1rem;
        }

        .login-body {
            padding: 2rem 1.5rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating label {
            color: #6c757d;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15);
        }

        .btn-login {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(25, 135, 84, 0.3);
            background: linear-gradient(135deg, #157347 0%, #1aa179 100%);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 0.5rem;
            border: none;
            padding: 1rem 1.25rem;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .login-footer {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .login-footer p {
            margin: 0;
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 767.98px) {
            body {
                background: linear-gradient(135deg, #198754 0%, #20c997 100%);
                padding: 1rem;
            }

            .login-header h1 {
                font-size: 2rem;
            }

            .login-header p {
                font-size: 0.9rem;
            }

            .login-body {
                padding: 1.5rem 1rem;
            }

            .login-footer {
                padding: 1rem;
            }
        }

        @media (max-width: 575.98px) {
            .login-header {
                padding: 1.5rem 1rem;
            }

            .login-header h1 {
                font-size: 1.75rem;
            }

            .btn-login {
                padding: 0.75rem 1.25rem;
                font-size: 0.95rem;
            }
        }

        /* Additional enhancements */
        .input-group-icon {
            position: relative;
        }

        .input-group-icon .form-control {
            padding-left: 3rem;
        }

        .input-group-icon .icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
            font-size: 1.25rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            font-size: 1.25rem;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #198754;
        }
    </style>
</head>
<body>

<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">

            <div class="card login-card">
                
                <!-- Login Header -->
                <div class="login-header">
                    <h1 class="h3 mb-1">Pig Farm</h1>
                    <p>Management System</p>
                </div>

                <!-- Login Body -->
                <div class="login-body">

                    <h4 class="text-center mb-4">Welcome Back!</h4>

                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>⚠️ Error!</strong> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="process_login.php" id="loginForm">
                        
                        <!-- Email Field -->
                        <div class="input-group-icon mb-4">
                            <div class="form-floating">
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Email address"
                                       required
                                       autofocus>
                                <label for="email">Email Address</label>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="input-group-icon mb-4 position-relative">
                            <div class="form-floating">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Password"
                                       required>
                                <label for="password">Password</label>
                            </div>
                            <span class="password-toggle" id="togglePassword">
                                👁️
                            </span>
                        </div>

                        <!-- Remember Me (Optional) -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="rememberMe" 
                                       name="remember">
                                <label class="form-check-label" for="rememberMe">
                                    Remember me
                                </label>
                            </div>
                        </div>

                        <!-- Login Button -->
                        <button type="submit" class="btn btn-login w-100">
                            Login to Farm
                        </button>

                    </form>

                </div>

                <!-- Login Footer -->
                <div class="login-footer">
                    <p>&copy; <?= date('Y') ?> Pig Farm Management System</p>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>

<!-- Custom Login JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    }

    // Form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }

            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }

            // Show loading state on button
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Logging in...';
            }
        });
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Add animation on input focus
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.2s ease';
        });

        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
});
</script>

</body>
</html>