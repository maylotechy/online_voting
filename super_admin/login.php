<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Login | AdminLTE</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        .login-page {
            background: url('../asssets/super_admin/bg.png') no-repeat fixed 100% center;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;

        }
        .login-logo {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .login-box {
            width: 400px;
        }
        .login-card {
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-card-body {
            padding: 2rem;
        }
        .cf-turnstile {
            width: 100% !important;
            margin: 15px 15px;
        }
        /* Match AdminLTE input height */
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
        .gradient-text {
            background: linear-gradient(45deg, #008080, #20c997); /* teal gradient */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }


    </style>
</head>
<body class="login-page">
<div class="login-box">
    <!-- Login Logo -->
    <div class="login-logo text-center mb-4">
        <a href="#" class="gradient-text"><b>SUPER</b>admin</a>
    </div>

    <!-- Login Card -->
    <div class="card login-card">
        <div class="card-body login-card-body">
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
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
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
                    Swal.fire({
                        title: 'Success',
                        text: response.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = "dashboard.php";
                    });
                } else {
                    Swal.fire({
                        title: 'Failed',
                        text: response.message,
                        icon: 'error'
                    });

                    // Reset Turnstile if available
                    if (typeof turnstile !== 'undefined') {
                        turnstile.reset();
                    }
                }
                btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt mr-2"></i> Sign In');
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Connection error. Please try again.',
                    confirmButtonColor: '#3085d6',
                });
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