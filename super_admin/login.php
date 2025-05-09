<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Login | AdminLTE</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <!-- Google Fonts: Satoshi -->
    <link href="https://fonts.googleapis.com/css2?family=Satoshi:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Satoshi', sans-serif;
        }

        .login-page {
            background: linear-gradient(135deg, #ffffff, #f0f0f0); /* White gradient */
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-box {
            width: 400px;
        }

        .login-card {
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0,0,0,0.1);
            overflow: hidden;
            height: auto;
        }

        .login-card-header {
            background: linear-gradient(45deg, #008080, #20c997);
            color: white;
            padding: 1.5rem;
            text-align: center;
            border-bottom: none;
        }

        .login-card-body {
            padding: 2rem;
        }

        .admin-title {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0;
        }

        .admin-title i {
            margin-right: 10px;
            font-size: 1.5rem;
        }

        .cf-turnstile {
            width: 100% !important;
            margin: 15px 0;
        }

        .cf-turnstile iframe {
            height: 38px !important;
        }

        .btn-teal {
            background-color: teal;
            border-color: teal;
            color: #fff;
        }

        .btn-teal:hover {
            background-color: #007777;
            border-color: #006666;
        }

        .logo {
            text-align: center;
            margin-bottom: 1rem;
        }

        .university-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

    </style>
</head>
<body class="login-page">
<div class="login-box">
    <!-- Login Card -->
    <div class="card login-card">
        <!-- Card Header with Super Admin Title -->
        <div class="card-header login-card-header">
            <h3 class="admin-title">
                <i class="fas fa-user-shield"></i>
                SUPER ADMIN
            </h3>
        </div>

        <div class="card-body login-card-body">
            <!-- University Name -->
            <div class="university-title">
                UNIVERSITY OF SOUTHERN MINDANAO
            </div>

            <!-- Logo -->
            <div class="logo">
                <img src="../asssets/super_admin/login-removebg-preview.png" alt="University Logo" style="width: 100px; height: auto;">
            </div>

            <p class="login-box-msg">Sign in to start your session</p>

            <form id="loginForm">
                <!-- Email Field -->
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>

                <!-- Password Field -->
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>

                <!-- Cloudflare Turnstile Widget -->
                <div class="cf-turnstile mb-3"
                     data-sitekey="0x4AAAAAABXBqVxZ77CX3Fs3"
                     data-theme="light"
                     data-size="normal"></div>

                <!-- Login Button -->
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-teal btn-block">
                            <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                        </button>
                    </div>
                </div>
            </form>

            <!-- Security Badges -->
            <div class="mt-4 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt mr-1"></i> Secured by Cloudflare Turnstile
                </small>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 5000,
        escapeHtml: true
    };

    // Handle form submission
$('#loginForm').submit(function(e) {
    e.preventDefault();

    // Show loading state
    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Authenticating...');

    // Submit via AJAX
    $.ajax({
        type: 'POST',
        url: 'login_process.php',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                // Redirect based on role_id
                if (response.role_id === 2) {
                    // College admin (role_id = 2)
                    setTimeout(() => {
                        window.location.href = "../admin/adminDashboard.php";
                    }, 1500);
                } else {
                    // Default redirect (super admin)
                    setTimeout(() => {
                        window.location.href = "dashboard.php";
                    }, 1500);
                }
            } else {
                toastr.error(response.message);
                // Reset Turnstile if available
                if (typeof turnstile !== 'undefined') {
                    turnstile.reset();
                }
            }
            btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt mr-2"></i> Sign In');
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Connection error. Please try again.');
            btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt mr-2"></i> Sign In');

            // Reset Turnstile if available
            if (typeof turnstile !== 'undefined') {
                turnstile.reset();
            }
        }
    });
});
</script>
</body>
</html>