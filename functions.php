<?php
function checkLogin()
{
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function isDono()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'dono';
}
