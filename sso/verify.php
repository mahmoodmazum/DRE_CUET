<?php
require __DIR__ . '/../vendor/autoload.php'; // include firebase/php-jwt
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('PORTAL_JWT_SECRET', 'jhgsajf#^%#%#765'); // must match portal
define('EXPECTED_AUD', 'dre'); // module slug

session_start();

$jwt = $_GET['sso'] ?? '';
if (!$jwt) { http_response_code(400); exit('Missing SSO token'); }

try {
  $decoded = JWT::decode($jwt, new Key(PORTAL_JWT_SECRET, 'HS256'));
  $claims = (array)$decoded;

  if (($claims['aud'] ?? null) !== EXPECTED_AUD) throw new Exception('Invalid audience');
  if (empty($claims['email'])) throw new Exception('Invalid claims');

  // Create app session
  // $_SESSION['user'] = [
  //   'id'    => $claims['sub'] ?? null,
  //   'email' => $claims['email'],
  //   'name'  => $claims['name'] ?? '',
  //   'role'  => $claims['role'] ?? '',
  // ];

  $_SESSION['user'] = $claims;

  // Redirect to module home
  header('Location: /DRE/index.php');
} catch (Exception $e) {
  http_response_code(403);
  echo 'SSO verification failed: ' . htmlspecialchars($e->getMessage());
}
