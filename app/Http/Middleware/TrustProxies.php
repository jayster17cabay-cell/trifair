<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

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
