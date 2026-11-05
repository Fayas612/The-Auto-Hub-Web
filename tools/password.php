<?php
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
fwrite(STDOUT, "Enter your admin password (visible while typing): ");
$password = trim(fgets(STDIN));
if (strlen($password) < 12) { fwrite(STDERR, "Use at least 12 characters.\n"); exit(1); }
echo password_hash($password, PASSWORD_DEFAULT), PHP_EOL;
