<?php

namespace Hexlet\Code\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class PageAnalyzer
{
    public static function analyze(string $url): array
    {
        $client = new Client([
            'base_uri' => $url,
            'timeout' => 2.0,
        ]);

        $response = $client->request('GET');

        $crawler = new Crawler(
            $response->getBody()->getContents()
        );

        $meta = $crawler->filter('meta[name="description"]');

        return [
            'statusCode' => $response->getStatusCode(),
            'h1' => $crawler->filter('h1')->count()
                ? $crawler->filter('h1')->text()
                : null,
            'title' => $crawler->filter('title')->count()
                ? $crawler->filter('title')->text()
                : null,
            'description' => $meta->count()
                ? $meta->attr('content')
                : null,
        ];
    }
}
