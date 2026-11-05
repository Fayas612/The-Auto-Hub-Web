<?php
require __DIR__.'/app.php';
header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']!=='POST'){
 header('Allow: POST');http_response_code(405);exit('Use the Sign out button to sign out.');
}
verify_csrf();
unset($_SESSION['user_id']);
session_regenerate_id(true);
header('Location: login.php',true,303);
exit;
