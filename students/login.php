<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Voting Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .voting-card {
            max-width: 450px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-top: 5px solid #20c997;
        }
        .code-input {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 15px 0;
        }
        .code-input input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            border: 2px solid #ced4da;
            border-radius: 6px;
        }
        .code-input input:focus {
            border-color: #20c997;
            box-shadow: 0 0 0 0.25rem rgba(32, 201, 151, 0.25);
        }
        .btn-teal {
            background-color: #20c997;
            border-color: #20c997;
        }
        .btn-teal:hover {
            background-color: #1aa179;
            border-color: #1aa179;
        }
        .turnstile-container {
            margin: 15px 0;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card voting-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-vote-yea fa-3x text-teal mb-3" style="color: #20c997;"></i>
                        <h2>Student Voting Portal</h2>
                        <p class="text-muted">Enter your student ID and access code</p>
                    </div>

                    <form id="votingLoginForm">
                        <!-- Student ID Field -->
                        <div class="mb-3">
                            <label for="studentId" class="form-label">Student ID</label>
                            <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-id-card"></i>
                                    </span>
                                <input type="text" class="form-control" id="studentId"
                                       name="student_id" placeholder="e.g. 20230001" required>
                            </div>
                        </div>

                        <!-- 4-Digit Access Code -->
                        <div class="mb-3">
                            <label class="form-label">4-Digit Access Code</label>
                            <div class="code-input">
                                <input type="text" maxlength="1" class="form-control"
                                       pattern="\d" required name="code1">
                                <input type="text" maxlength="1" class="form-control"
                                       pattern="\d" required name="code2">
                                <input type="text" maxlength="1" class="form-control"
                                       pattern="\d" required name="code3">
                                <input type="text" maxlength="1" class="form-control"
                                       pattern="\d" required name="code4">
                            </div>
                            <input type="hidden" id="accessCode" name="access_code">
                        </div>

                        <!-- Cloudflare Turnstile -->
                        <div class="turnstile-container text-center">
                            <div class="cf-turnstile"
                                 data-sitekey="0x4AAAAAABXBqVxZ77CX3Fs3"
                                 data-theme="light"
                                 data-size="normal"></div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-teal btn-lg w-100 py-2 mt-3">
                            <i class="fas fa-sign-in-alt me-2"></i> Enter Voting Booth
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        // Auto-focus between code inputs
        $('.code-input input').keyup(function(e) {
            // Only allow numbers
            if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                $(this).val('');
                return false;
            }

            if (this.value.length === this.maxLength) {
                $(this).next('.code-input input').focus();
            }
        });

        // Combine code digits before submission
        $('#votingLoginForm').submit(function(e) {
            e.preventDefault();

            // Combine the 4-digit code
            let accessCode = '';
            $('.code-input input').each(function() {
                accessCode += $(this).val();
            });
            $('#accessCode').val(accessCode);

            // Validate code length
            if (accessCode.length !== 4) {
                alert('Please enter a complete 4-digit access code');
                return;
            }

            // Show loading state
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span> Verifying...');

            // Submit via AJAX
            $.ajax({
                type: 'POST',
                url: 'voting_login.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect || 'voting_booth.php';
                    } else {
                        alert(response.message || 'Login failed. Please check your credentials.');
                        // Reset Turnstile
                        if (typeof turnstile !== 'undefined') {
                            turnstile.reset();
                        }
                    }
                    btn.prop('disabled', false)
                        .html('<i class="fas fa-sign-in-alt me-2"></i> Enter Voting Booth');
                },
                error: function() {
                    alert('Connection error. Please try again.');
                    btn.prop('disabled', false)
                        .html('<i class="fas fa-sign-in-alt me-2"></i> Enter Voting Booth');
                }
            });
        });
    });
</script>
</body>
</html>