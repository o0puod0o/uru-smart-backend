<?php

$emails = array_map('trim', explode(',', (string) env('WEB_ADMIN_EMAILS', '')));

return [
    'emails' => array_values(array_filter($emails)),
];
