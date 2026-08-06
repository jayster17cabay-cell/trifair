<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * No proxies are trusted by default. Configure specific proxy IPs/CIDRs
     * via the TRUSTED_PROXIES env var (see config/trustedproxy.php). Trusting
     * "*" is intentionally avoided because it allows any client to spoof their
     * IP through X-Forwarded-For.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = null;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Resolve the trusted proxies from configuration so the value is available
     * even when config is cached (env() would otherwise return null).
     */
    protected function proxies()
    {
        $value = config('trustedproxy.proxies', null);

        if (is_string($value)) {
            $value = trim($value);
        }

        return ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Guard against an empty REMOTE_ADDR (CLI, misconfigured web servers,
     * some proxy edge cases). Laravel's default passes the raw value into
     * setTrustedProxies() and Symfony's IpUtils::checkIp() throws a
     * TypeError when it is null, turning every request into a 500.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function setTrustedProxyIpAddressesToTheCallingIp(Request $request)
    {
        $ip = $request->server->get('REMOTE_ADDR');

        if (empty($ip)) {
            $request->setTrustedProxies([], $this->getTrustedHeaderNames());
            return;
        }

        $request->setTrustedProxies([$ip], $this->getTrustedHeaderNames());
    }
}
