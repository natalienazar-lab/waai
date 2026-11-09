<?php
// Temporary: shows which IP this server uses for outgoing connections (to fix SPF). Delete after use.
header('Content-Type: text/plain');
$s = @stream_socket_client('tcp://wisely-applied-ai.com:80', $e, $m, 5);
echo "Outgoing IP (local socket): " . ($s ? stream_socket_get_name($s, false) : "n/a") . "\n";
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
echo "Outgoing IP (ipify): " . (@file_get_contents('https://api.ipify.org', false, $ctx) ?: 'n/a') . "\n";
$mx = []; getmxrr('wisely-applied-ai.com', $mx);
$t = @stream_socket_client('tcp://' . ($mx[0] ?? 'x') . ':25', $e2, $m2, 8);
echo "Port 25 to " . ($mx[0] ?? '?') . ": " . ($t ? 'open, local ' . stream_socket_get_name($t, false) : "blocked ($m2)") . "\n";
