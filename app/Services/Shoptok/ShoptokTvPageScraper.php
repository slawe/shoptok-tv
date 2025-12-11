<?php

declare(strict_types=1);

namespace App\Services\Shoptok;

use App\DTO\TvProductData;
use App\Enums\TvCategory;
use App\ValueObjects\Money;
use Deprecated;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

final class ShoptokTvPageScraper
{
    private const string BASE_URL = 'https://www.shoptok.si';

    /**
     * ShoptokTvPageScraper constructor.
     *
     * @param HttpFactory $http
     */
    public function __construct(private readonly HttpFactory $http) {}

    /**
     * HTTP variant (currently it serves us only as a "stub" - it is actually blocked by 403).
     * It can remain for code-review, but in practice we will use scrapeHtml().
     *
     * @param string $url
     * @param string|null $category
     * @return ShoptokPageResult
     * @throws RequestException
     */
    #[Deprecated(message:
        "Attention Required! | Cloudflar
         Shoptok.si is using a security service to protect itself from online attacks.
         The action you just performed triggered the security solution."
    )]
    public function scrapePage(string $url, ?string $category = null): ShoptokPageResult
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

        $html = $response->body();

        return $this->parseDocument($html, $category, $url);
    }

    public function scrapeHtml(string $html, ?string $category = null, ?string $currentUrl = null): ShoptokPageResult
    {
        return $this->parseDocument($html, $category, $currentUrl);
    }

    private function defaultCategory(): string
    {
        return TvCategory::TELEVIZORJI->value;
    }

    /**
     * If the fixture is saved from "View Source", the original HTML is actually
     *  in <td class="line-content"> as escaped text.
     *
     *  Here:
     *  - extract the text of all td.line-content
     *  - let's combine into one string
     *  - html_entity_decode → real HTML
     *
     * @param string $html
     * @return string
     */
    private function normalizeHtmlForCrawler(string $html): string
    {
        if (
            str_contains($html, 'class="line-content"')
            && str_contains($html, 'class="html-tag"')
        ) {
            // this is view-source format (with line numbers)
            $tmpCrawler = new Crawler($html);

            $lines = $tmpCrawler
                ->filter('td.line-content')
                ->each(static fn (Crawler $n): string => $n->text(''));

            $rawSource = implode("\n", $lines);

            return html_entity_decode($rawSource, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $html;
    }

    /**
     * Common logic for parsing HTML
     * (whether it arrived via HTTP or from a local fixture).
     *
     * @param string $html
     * @param string|null $category
     * @param string|null $currentUrl
     * @return ShoptokPageResult
     */
    private function parseDocument(string $html, ?string $category, ?string $currentUrl): ShoptokPageResult
    {
        // since the HTML is saved from "view-source", we first normalize it
        $normalizedHtml = $this->normalizeHtmlForCrawler($html);
        // create a Crawler over the real DOM
        $crawler = new Crawler($normalizedHtml);
        // main selector for product cards
        $productNodes = $crawler->filter('div.b-paging-product');

        // fallback in case of class change on the site
        if ($productNodes->count() === 0) {
            $productNodes = $crawler->filter('[data-entity="product"], .product');
        }

        $products = [];

        foreach ($productNodes as $domElement) {
            $node = new Crawler($domElement);

            try {
                $products[] = $this->mapNodeToDto($node, $category);
            } catch (Throwable $e) {
                // in production: logger would go here.
                // for the task: a silent file is ok, we just skip the problematic cards.
                continue;
            }
        }

        $nextPageUrl = null;

        if ($currentUrl !== null) {
            $nextPageUrl = $this->extractNextPageUrl($crawler, $currentUrl);
        }

        return new ShoptokPageResult($products, $nextPageUrl);
    }

    /**
     * Maps one <div class="b-paging-product"> to TvProductData.
     *
     * @param Crawler $node
     * @param string|null $category
     * @return TvProductData
     */
    private function mapNodeToDto(Crawler $node, ?string $category): TvProductData
    {
        $title = $this->extractTitle($node);
        $brand = $this->guessBrandFromTitle($title);
        $shop  = $this->extractShopName($node);
        $productUrl = $this->extractProductUrl($node);
        $imageUrl   = $this->extractImageUrl($node);
        $priceText  = $this->extractPriceText($node);

        $price = null;
        if ($priceText !== null) {
            try {
                $price = Money::fromLocalizedString($priceText);
            } catch (InvalidArgumentException) {
                $price = null;
            }
        }

        $externalId = $this->extractExternalIdFromTitle($title);
        if ($externalId === null) {
            $externalId = $this->extractExternalIdFromNode($node);
        }

        return new TvProductData(
            title: $title,
            brand: $brand,
            shop: $shop,
            productUrl: $productUrl,
            imageUrl: $imageUrl,
            price: $price,
            category: $category ?? $this->defaultCategory(),
            externalId: $externalId,
        );
    }

    /**
     * Title extraction: from .b-paging-product__cta h3.l3-product-title,
     * fallback to trimmed text from node if needed.
     *
     * @param Crawler $node
     * @return string
     */
    private function extractTitle(Crawler $node): string
    {
        $titleNode = $node->filter('.b-paging-product__cta h3.l3-product-title');

        if ($titleNode->count() === 0) {
            $text = $node->text('');
        } else {
            $text = $titleNode->text('');
        }

        return $this->normalizeWhitespace($text);
    }

    /**
     * Brand: if there is <b>...</b> in the title, take that,
     * otherwise the first token from the name.
     *
     * @param string $title
     * @return string|null
     */
    private function guessBrandFromTitle(string $title): ?string
    {
        $trimmed = trim($title);
        if ($trimmed === '') {
            return null;
        }

        // brand is the first word, eg "Samsung 50NANO..."
        $parts = preg_split('/\s+/u', $trimmed);
        if (!is_array($parts) || count($parts) === 0) {
            return null;
        }

        $brand = $parts[0];

        // the minimum length so that we don't take something completely unusable
        return mb_strlen($brand) >= 2 ? $brand : null;
    }

    /**
     * Shop name / info on the number of shops, e.g. "v 3 shops", "v ECE shop".
     *
     * @param Crawler $node
     * @return string|null
     */
    private function extractShopName(Crawler $node): ?string
    {
        $infoNode = $node->filter('.b-paging-product__num');

        if ($infoNode->count() === 0) {
            return null;
        }

        $text = $this->normalizeWhitespace($infoNode->text(''));

        return $text !== '' ? $text : null;
    }

    /**
     * Extraction of the raw price string (e.g. "€329.00" or "€1,499.00").
     *
     * @param Crawler $node
     * @return string|null
     */
    private function extractPriceText(Crawler $node): ?string
    {
        $priceNode = $node->filter('.b-paging-product__price b');

        if ($priceNode->count() === 0) {
            return null;
        }

        $text = $this->normalizeWhitespace($priceNode->text(''));

        return $text !== '' ? $text : null;
    }

    /**
     * Product URL extraction (relative -> absolute).
     *
     * @param Crawler $node
     * @return string
     */
    private function extractProductUrl(Crawler $node): string
    {
        $linkNode = $node->filter('.b-paging-product__cta a')->first();

        if ($linkNode->count() === 0) {
            return self::BASE_URL;
        }

        $href = $linkNode->attr('href') ?? '';

        return $this->normalizeUrl($href);
    }

    /**
     * Extract the image URL, if exists.
     *
     * @param Crawler $node
     * @return string|null
     */
    private function extractImageUrl(Crawler $node): ?string
    {
        $imgNode = $node->filter('.b-paging-product__media img')->first();

        if ($imgNode->count() === 0) {
            return null;
        }

        $src = $imgNode->attr('src') ?? '';

        if ($src === '') {
            return null;
        }

        return $this->normalizeUrl($src);
    }

    /**
     * Tries to extract the external ID from the name, for example,
     * "Hisense Television 50A6Q, (5000003761) ..." -> "5000003761"
     *
     * @param string $title
     * @return string|null
     */
    private function extractExternalIdFromTitle(string $title): ?string
    {
        if (preg_match('/\((\d{4,})\)/u', $title, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Alternatively, the ID can also be found in the
     * event-viewitem-id attribute on Shoptok.
     *
     * @param Crawler $node
     * @return string|null
     */
    private function extractExternalIdFromNode(Crawler $node): ?string
    {
        $attr = $node->attr('event-viewitem-id');

        return $attr !== null && $attr !== '' ? $attr : null;
    }

    /**
     * Extracting the next-page link (when doing live scraping).
     *
     *  Try:
     *  - <a rel="next" ...>
     *  - a link with a query parameter page=N greater than the current one.
     *
     * @param Crawler $crawler
     * @param string $currentUrl
     * @return string|null
     */
    private function extractNextPageUrl(Crawler $crawler, string $currentUrl): ?string
    {
        // try rel="next"
        $link = $crawler->filter('a[rel="next"]')->first();

        if ($link->count() > 0) {
            $href = $link->attr('href') ?? null;

            return $href ? $this->normalizeUrl($href) : null;
        }

        // fallback – find link with page= parameter
        $currentPage = $this->extractPageFromUrl($currentUrl);

        $candidates = $crawler->filter('a[href*="page="]');
        $best = null;
        $bestPage = null;

        foreach ($candidates as $domElement) {
            $node = new Crawler($domElement);
            $href = $node->attr('href') ?? null;

            if ($href === null) {
                continue;
            }

            $page = $this->extractPageFromUrl($href);

            if ($page === null) {
                continue;
            }

            if ($page > $currentPage && ($bestPage === null || $page < $bestPage)) {
                $bestPage = $page;
                $best = $href;
            }
        }

        if ($best === null) {
            return null;
        }

        return $this->normalizeUrl($best);
    }

    /**
     * @param string $url
     * @return int|null
     */
    private function extractPageFromUrl(string $url): ?int
    {
        if (preg_match('/[?&]page=(\d+)/', $url, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Converts relative links ("/televisions/...") to absolute URL.
     *
     * @param string $href
     * @return string
     */
    private function normalizeUrl(string $href): string
    {
        $href = trim($href);

        if ($href === '') {
            return self::BASE_URL;
        }

        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        return rtrim(self::BASE_URL, '/') . '/' . ltrim($href, '/');
    }

    /**
     * Normalization of whitespace (collapse into one space, trim).
     *
     * @param string $value
     * @return string
     */
    private function normalizeWhitespace(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
