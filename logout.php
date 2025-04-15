<?php
session_start();
define('BASE_URL', '/');
session_unset();
session_destroy();
header("Location: " . BASE_URL . "index.php");
exit();
?>