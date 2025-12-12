<?php

namespace App\Services\Shoptok;

use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * @deprecated Attention Required! | Cloudflar
 *              Shoptok.si is using a security service to protect itself from online attacks.
 *              The action you just performed triggered the security solution.
 */
final class HttpShoptokHtmlSource implements ShoptokHtmlSource
{
    public function __construct(private readonly HttpFactory $http) {}

    public function fetch(string $url): string
    {
        $headers = [
            'User-Agent'      => Config::get('services.shoptok.user_agent'),
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'sl-SI,sl;q=0.8,en-US;q=0.5,en;q=0.3',
            'Referer'         => Config::get('services.shoptok.referer'),
        ];

        $cookie = Config::get('services.shoptok.cookie');

        if (! empty($cookie)) {
            $headers['Cookie'] = $cookie;
        }

        $response = $this->http
            ->withHeaders($headers)
            ->get($url);

        if ($response->status() === 403) {
            throw new RuntimeException(
                'Live HTTP scraping is blocked with 403 (WAF/anti-bot). ' .
                'For this project use scrapeHtml() with local HTML fixtures.'
            );
        }

        $response->throw(); // if request fails - throw exception

        return $response->body();
    }
}
