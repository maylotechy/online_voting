function Logout() {
    console.log("Logout");
    Swal.fire({
        title: "Logout Confirmation",
        text: "Are you sure you want to log out?",
        icon: "warning",
        showCancelButton: true, // Ensures the "No" button is shown
        confirmButtonText: "Yes", // Text for the "Yes" button
        cancelButtonText: "No", // Text for the "No" button
        dangerMode: true // Highlights the "Yes" button as dangerous
    }).then((result) => {
        if (result.isConfirmed) { // Checks if "Yes" was clicked
            $.ajax({
                type: "POST",
                url: '/authentication/destroy-session.php',
                success: function (data) {
                    const obj = JSON.parse(data);
                    if (obj.response === "success") {
                        Swal.fire("Logged out successfully!", "", "success");
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 2000);
                    } else {
                        Swal.fire("Something went wrong. Please try again later.", "", "error");
                    }
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.error("XHR Response:", xhr.responseText);
                    Swal.fire("Error: " + errorThrown, "", "error");
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            console.log("User chose not to log out.");
        }
    });
}
