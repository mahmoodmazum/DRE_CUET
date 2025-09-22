<?php
require __DIR__ . '/src/lib/Auth.php';
Auth::logout();
header('Location: /portal');
exit;
