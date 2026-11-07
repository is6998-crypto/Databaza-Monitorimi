<?php

/*
 * Reverse proxies or load balancers in front of the application. Illuminate\Http\Middleware\
 * TrustProxies (in the global middleware) reads this on every request, after .env is loaded,
 * so HTTPS, the host name and client addresses (rate limits, audit log) are detected
 * correctly behind a proxy: "*" trusts the proxy that connects, or list its addresses
 * separated by commas. The development container sets "*" for GitHub Codespaces, whose port
 * forwarding terminates HTTPS in front of the app (docker/dev/entrypoint.sh).
 */
return [
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
