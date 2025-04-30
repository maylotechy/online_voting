<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery -->
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            overflow: hidden; /* Prevent scrolling */
        }

        .container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            width: 100%;
            max-width: 400px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            z-index: 999; /* Ensure the card stays on top */
            position: relative; /* Ensures it doesn't get affected by alert */
        }

        .btn-teal {
            background-color: #008080;
            color: white;
            border: none;
        }

        .btn-teal:hover {
            background-color: #006666;
        }

        /* Ensures alert doesn't push content */
        .swal2-container {
            z-index: 1050 !important; /* Makes sure alert is on top of content */
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card p-4">
        <h3 class="text-center mb-4">Super Admin Login</h3>
        <form>
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
            <button id="logInButton" type="button" class="btn btn-teal w-100" onclick="login()">Log In</button>
        </form>
    </div>
</div>

<script src="../plugins/jquery-validation/jquery.validate.js"></script>
<script src="../plugins/sweetalert2/sweetalert2.all.js"></script>
<script>
    function login() {
        var email = $('input[name="email"]').val();
        var password = $('input[name="password"]').val();

        $.ajax({
            type: "POST",
            url: 'login_process.php',
            dataType: "json",
            data: {
                email: email,
                password: password
            },
            success: function(obj) {
                if (obj.response == "success") {
                    Swal.fire({
                        title: "Success",
                        text: obj.message,
                        icon: "success",
                    }).then(() => {
                        // Redirect after alert closes
                        window.setTimeout(function() {
                            window.location.href = "dashboard.php";
                        }, 2000);
                    });
                } else {
                    Swal.fire({
                        title: "Failed",
                        text: obj.message,
                        icon: "error",
                    });
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                Swal.fire({
                    title: "Error",
                    text: "An error occurred during login",
                    icon: "error",
                });
            }
        });
    }
</script>
</body>
</html>
