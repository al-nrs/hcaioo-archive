<?php
// One immutable issue's request-time switch. No future reader copy or private key here.
declare(strict_types=1);
header('Cache-Control: no-store, no-cache, private, max-age=0, must-revalidate');
header('Pragma: no-cache'); header('Expires: 0'); header('X-Content-Type-Options: nosniff');
header_remove('ETag'); header_remove('Last-Modified');
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method !== 'GET' && $method !== 'HEAD') { http_response_code(405); header('Allow: GET, HEAD'); exit; }
// Query strings, request bodies, client headers and cookies cannot select time or copy.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$routes = ['/' => 'index', '/index.html' => 'index', '/feed.xml' => 'feed', '/sitemap.xml' => 'sitemap', '/issues/clinical-ai-evaluation-gap' => 'article', '/issues/clinical-ai-evaluation-gap/' => 'article', '/issues/clinical-ai-evaluation-gap/index.html' => 'article'];
$route = is_string($path) ? ($routes[$path] ?? null) : null;
if ($route === null) { http_response_code(404); exit; }
$expected = ['index' => 'c6df6c19b7b344a4e43f124efa2efedbad1f1e1ff3493edff58c29110e61aa6a', 'feed' => 'c3d7475323dcc647b2a6682d39e0a04fd72fdbedf2a8523a0cf059394f44d890', 'sitemap' => 'f10c534b73559219b489b7146b600633d811c446cf2b23b9d4927e5a49893bb4', 'article' => 'cc078ed07ad5dd850b1337c52d3cc069cb3616543b5a7f38436a1496e3088b5a'];
$old = ['index' => ['index.html', '3e1009b681100c3b0bae103f8f85212b453a393faadcd0c73aed4bde0fad6201'], 'feed' => ['feed.xml', '99064d3388a6feca8daf599e8025d7f96c46c83f28c4a2184ecd5847a8a840c7'], 'sitemap' => ['sitemap.xml', '054a09a3e481b7bb639a3fa2d666b866bd6232d692ec72f1a308011241b627a7']];
$key = 'sb_publishable_JwfbeuTFY7goTlK_a9RZvA_tIwe-h4p';
$endpoint = 'https://eesujwikgbhmczsjhkuq.supabase.co/rest/v1/rpc/evaluation_oct21_archive';
$context = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\napikey: " . $key . "\r\n", 'content' => json_encode(['p_route' => $route]), 'timeout' => 8, 'ignore_errors' => true, 'follow_location' => 0], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
$raw = @file_get_contents($endpoint, false, $context);
$ok = isset($http_response_header[0]) && preg_match('/^HTTP\/\S+ 200(?: |$)/', $http_response_header[0]);
$reply = $ok && is_string($raw) ? json_decode($raw, true) : null;
$state = is_array($reply) ? ($reply['state'] ?? 'unavailable') : 'unavailable';
header('X-ALHQ-Release-State: ' . (in_array($state, ['locked', 'held', 'released'], true) ? $state : 'unavailable'));
if ($state === 'released') {
    $body = $reply['body'] ?? null;
    $types = ['index' => 'text/html; charset=utf-8', 'article' => 'text/html; charset=utf-8', 'feed' => 'application/rss+xml; charset=utf-8', 'sitemap' => 'application/xml; charset=utf-8'];
    if (!is_string($body) || ($reply['sha256'] ?? '') !== $expected[$route] || hash('sha256', $body) !== $expected[$route] || ($reply['content_type'] ?? '') !== $types[$route] || ($reply['release_at'] ?? '') !== '2026-10-21T16:00:00Z') { http_response_code(503); exit; }
    header('Content-Type: ' . $types[$route]); header('Content-Length: ' . strlen($body));
    if ($method !== 'HEAD') { echo $body; } exit;
}
// Locked, revoked or unavailable: old views remain byte-exact; no future bytes leak.
if ($route !== 'article') {
    $file = __DIR__ . '/' . $old[$route][0];
    $body = @file_get_contents($file);
    if (!is_string($body) || hash('sha256', $body) !== $old[$route][1]) { http_response_code(503); exit; }
    $type = $route === 'index' ? 'text/html; charset=utf-8' : ($route === 'feed' ? 'application/rss+xml; charset=utf-8' : 'application/xml; charset=utf-8');
    header('Content-Type: ' . $type); header('Content-Length: ' . strlen($body));
    if ($method !== 'HEAD') { echo $body; } exit;
}
http_response_code($state === 'locked' ? 404 : 503); header('Content-Type: text/plain; charset=utf-8');
if ($method !== 'HEAD') { echo "Not available\n"; }
