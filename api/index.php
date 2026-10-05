<?php

$tmp = '/tmp/storage';
foreach (['app', 'framework/cache', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
    if (!is_dir("$tmp/$dir")) {
        mkdir("$tmp/$dir", 0777, true);
    }
}

$overrides = [
    'LARAVEL_STORAGE_PATH' => $tmp,
    'VIEW_COMPILED_PATH'   => "$tmp/framework/views",
    'APP_SERVICES_CACHE'   => "$tmp/services.php",
    'APP_PACKAGES_CACHE'   => "$tmp/packages.php",
    'APP_CONFIG_CACHE'     => "$tmp/config.php",
    'APP_ROUTES_CACHE'     => "$tmp/routes.php",
    'APP_EVENTS_CACHE'     => "$tmp/events.php",
];
foreach ($overrides as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__ . '/../public/index.php';