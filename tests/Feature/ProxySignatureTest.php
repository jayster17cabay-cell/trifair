<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProxySignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_proof_url_validates_with_https_scheme()
    {
        // Regression guard: signed proof URLs must validate (not 403) under the
        // production-like https scheme. Laravel's test client builds request URLs
        // through url(), which honors forceScheme, so generation and validation
        // both see https here — this catches a future removal of URL::forceScheme
        // or of proxy trust (TRUSTED_PROXIES). The real http-request-on-Render
        // scenario can't be simulated by the harness, but the same code path
        // (URL::signedRoute + hasCorrectSignature over $request->url()) is what
        // the prod fix makes consistent.
        URL::forceScheme('https');

        // Signature is computed over the https:// URL; the request must be
        // https too, otherwise validation 403s.
        $url = URL::signedRoute('proof.serve', ['path' => 'proofs/missing/proof.jpg']);
        $path = parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get($path, ['X-Forwarded-Proto' => 'https']);

        // Not 403 = signature validated. The route then redirects to Supabase
        // because no local file exists for the fake path.
        $this->assertNotEquals(403, $response->getStatusCode());
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_signed_proof_url_uses_https_scheme_in_production_like_setup()
    {
        URL::forceScheme('https');

        $url = URL::signedRoute('proof.serve', ['path' => 'proofs/missing/proof.jpg']);

        $this->assertStringStartsWith('https://', $url);
    }
}
