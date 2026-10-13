<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pages = ['owner' => 'owner-gallery', 'client' => 'client-gallery', 'hardcopy-owner' => 'hardcopy-owner', 'hardcopy-client' => 'hardcopy-client'];
$name = $pages[substr($path, strlen('/phase9-ui/'))] ?? null;
if (str_starts_with($path, '/phase9-ui/') && $name) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(getenv('PHASE9_UI_DIR').'/'.$name.'.html');

    return true;
}
if (preg_match('#^/(owner|client)/bookings/1/hardcopy$#', $path, $matches)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(getenv('PHASE9_UI_DIR').'/hardcopy-'.$matches[1].'-panel.html');

    return true;
}
if (str_starts_with($path, '/assets/') && is_file(dirname(__DIR__, 2).'/public'.$path)) {
    return false;
}
http_response_code(404);
echo 'Isolated fixture server: no application routes.';
