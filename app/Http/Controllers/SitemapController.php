<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate XML sitemap
     */
    public function index(): Response
    {
        $baseUrl = config('app.url');
        $now = now()->toIso8601String();

        $urls = [
            [
                'loc' => $baseUrl,
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => $baseUrl . '/login',
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => $baseUrl . '/register',
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;

            // Add alternate language versions
            $xml .= '    <xhtml:link rel="alternate" hreflang="en" href="' . htmlspecialchars($url['loc'] . '?lang=en') . '" />' . PHP_EOL;
            $xml .= '    <xhtml:link rel="alternate" hreflang="ar" href="' . htmlspecialchars($url['loc'] . '?lang=ar') . '" />' . PHP_EOL;

            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
