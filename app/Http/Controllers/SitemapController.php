<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $sitemap .= 'xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        // Define pages with their priorities and change frequencies
        $pages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/login', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => '/register', 'priority' => '0.8', 'changefreq' => 'monthly'],
        ];

        $locales = ['ar', 'en'];

        foreach ($pages as $page) {
            foreach ($locales as $locale) {
                $url = URL::to($page['url'] . '?lang=' . $locale);

                $sitemap .= '<url>';
                $sitemap .= '<loc>' . htmlspecialchars($url) . '</loc>';

                // Add alternate language links
                foreach ($locales as $altLocale) {
                    $altUrl = URL::to($page['url'] . '?lang=' . $altLocale);
                    $sitemap .= '<xhtml:link rel="alternate" hreflang="' . $altLocale . '" href="' . htmlspecialchars($altUrl) . '" />';
                }

                $sitemap .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
                $sitemap .= '<changefreq>' . $page['changefreq'] . '</changefreq>';
                $sitemap .= '<priority>' . $page['priority'] . '</priority>';
                $sitemap .= '</url>';
            }
        }

        $sitemap .= '</urlset>';

        return response($sitemap, 200)->header('Content-Type', 'application/xml');
    }
}
