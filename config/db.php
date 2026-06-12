<?php
$host = "127.0.0.1";
$user = "root";
$pass = "Hacker@3121A"; 
$db   = "PakhtoonCommunityDB";

$con = mysqli_connect($host, $user, $pass, $db);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    // Non-blocking notification using Bootstrap Toast
    // echo '
    // <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    //     <div id="connToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    //         <div class="d-flex">
    //             <div class="toast-body">
    //                 <i class="bi bi-database-check me-2"></i> 
    //                 Connected to <strong>PakhtoonCommunityDB</strong>
    //             </div>
    //             <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    //         </div>
    //     </div>
    // </div>
    // <script>
    //     document.addEventListener("DOMContentLoaded", function() {
    //         var toastEl = document.getElementById("connToast");
    //         var toast = new bootstrap.Toast(toastEl, { delay: 3000 }); // Visible for 3 seconds
    //         toast.show();
    //     });
    // </script>';
}

mysqli_set_charset($con, "utf8mb4");
?>