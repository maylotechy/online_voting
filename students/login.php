<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Login | AdminLTE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Satoshi:wght@400;600&display=swap" rel="stylesheet">
    <!-- Add Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        body {
            font-family: 'Satoshi', sans-serif;
        }
        .login-page {
            background: linear-gradient(135deg, #ffffff, #f0f0f0);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-box {
            width: 400px;
        }
        .login-card {
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
        }
        .login-card-header {
            background: linear-gradient(45deg, #008080, #20c997);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        .admin-title {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .admin-title i {
            margin-right: 10px;
        }
        .login-card-body {
            padding: 2rem;
        }
        .university-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 1rem;
        }
        .btn-teal {
            background-color: #20c997;
            border-color: #20c997;
            color: white;
        }
        .btn-teal:hover {
            background-color: #1aa179;
            border-color: #1aa179;
        }
        .cf-turnstile {
            width: 100% !important;
            margin: 15px 15px;
        }
        .cf-turnstile iframe {
            height: 38px !important;
        }
        .code-input {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 15px 0;
        }
        .code-input input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #ced4da;
            border-radius: 6px;
        }
        .code-input input:focus {
            border-color: #20c997;
            box-shadow: 0 0 0 0.25rem rgba(32, 201, 151, 0.25);
        }
    </style>
</head>
<body class="login-page">
<div class="login-box">
    <div class="card login-card">
        <div class="card-header login-card-header">
            <h3 class="admin-title">
                <i class="fas fa-user-graduate"></i> STUDENT
            </h3>
        </div>
        <div class="card-body login-card-body">
            <div class="university-title">UNIVERSITY OF SOUTHERN MINDANAO</div>
            <div class="logo">
                <img src="../asssets/super_admin/login-removebg-preview.png" alt="Student Logo" style="width: 100px;">
            </div>
            <p class="login-box-msg">Sign in using your student credentials</p>

            <form id="loginForm">
                <label class="form-label">Student ID</label>
                <div class="input-group mb-3">
                    <input type="text" name="student_id" class="form-control" placeholder="Student ID" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-id-card"></span>
                        </div>
                    </div>
                </div>

                <label class="form-label">6-Digit Access Code</label>
                <div class="code-input">
                    <input type="text" maxlength="1" class="form-control" required name="code1">
                    <input type="text" maxlength="1" class="form-control" required name="code2">
                    <input type="text" maxlength="1" class="form-control" required name="code3">
                    <input type="text" maxlength="1" class="form-control" required name="code4">
                    <input type="text" maxlength="1" class="form-control" required name="code5">
                    <input type="text" maxlength="1" class="form-control" required name="code6">
                </div>
                <input type="hidden" id="accessCode" name="access_code">

                <!-- Keep original Turnstile div -->
                <div class="cf-turnstile mb-3"
                     data-sitekey="0x4AAAAAABXBqVxZ77CX3Fs3"
                     data-theme="light"
                     data-size="normal"></div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-teal btn-block">
                            <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-4 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt mr-1"></i> Secured by Cloudflare Turnstile
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Add Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    // Configure Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 5000,
        extendedTimeOut: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };

    $(document).ready(function () {
        // Move focus on digit entry
        $('.code-input input').keyup(function (e) {
            // Allow only digits and backspace
            if ((e.which < 48 || e.which > 57) && e.which !== 8) {
                $(this).val('');
                return false;
            }

            // If a digit is entered and we're at the max length, move to next input
            if (this.value.length === this.maxLength && e.which >= 48 && e.which <= 57) {
                $(this).next('input').focus();
            }

            // If backspace is pressed on empty input, move to previous input
            if (e.which === 8 && this.value.length === 0) {
                $(this).prev('input').focus();
            }
        });

        // Handle pasting into code fields
        $('.code-input').on('paste', function(e) {
            e.preventDefault();
            var pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');

            // If it's 6 digits, distribute to the inputs
            if (/^\d{6}$/.test(pastedData)) {
                $('.code-input input').each(function(index) {
                    $(this).val(pastedData.charAt(index));
                });
            }
        });

        $('#loginForm').submit(function (e) {
            e.preventDefault();

            let accessCode = '';
            $('.code-input input').each(function () {
                accessCode += $(this).val();
            });
            $('#accessCode').val(accessCode);

            if (accessCode.length !== 6) {
                toastr.warning('Please enter a complete 6-digit access code', 'Incomplete Code');
                return;
            }

            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Authenticating...');
            console.log($(this).serialize());
            $.ajax({
                type: 'POST',
                url: 'student_login_process.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, 'Success');
                        setTimeout(function() {
                            window.location.href = "voting_page.php";
                        }, 1500);
                    } else {
                        toastr.error(response.message, 'Login Failed');
                        if (typeof turnstile !== 'undefined') turnstile.reset();
                    }
                    btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt mr-2"></i> Sign In');
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Connection error. Please try again.', 'Error');
                    btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt mr-2"></i> Sign In');
                    if (typeof turnstile !== 'undefined') turnstile.reset();
                }
            });
        });
    });
</script>
</body>
</html>