<?php

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'OPTIONS') {
    http_response_code(404);
    exit;
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Requested-With, X-HTTP-Method-Override');
header('Access-Control-Max-Age: 86400');
header('Content-Length: 0');
http_response_code(204);
