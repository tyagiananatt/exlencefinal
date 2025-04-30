<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_to'])) {
    $_SESSION['return_to'] = $_POST['return_to'];
    echo "Return URL set successfully";
}
?> 