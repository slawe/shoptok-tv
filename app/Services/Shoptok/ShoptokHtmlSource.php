<?php

namespace App\Services\Shoptok;

interface ShoptokHtmlSource
{
    /**
     * Return raw HTML for the given URL / resource.
     *
     * @param string $url
     * @return string
     */
    public function fetch(string $url): string;
}
