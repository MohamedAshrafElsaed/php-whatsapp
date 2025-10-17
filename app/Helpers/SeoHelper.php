<?php

namespace App\Helpers;

class SeoHelper
{
    /**
     * Get SEO meta tags for different pages
     */
    public static function getMetaTags(string $page = 'home', string $locale = 'en'): array
    {
        $baseUrl = config('app.url');
        $appName = config('app.name');

        $metas = [
            'home' => [
                'en' => [
                    'title' => "$appName - Professional WhatsApp Bulk Messaging System",
                    'description' => "Send bulk WhatsApp messages professionally. Manage contacts, create campaigns, and grow your business with our powerful messaging platform.",
                    'keywords' => "WhatsApp bulk messaging, mass WhatsApp sender, WhatsApp marketing, bulk SMS WhatsApp, contact management, campaign management",
                ],
                'ar' => [
                    'title' => "$appName - نظام إرسال رسائل واتساب جماعية احترافي",
                    'description' => "أرسل رسائل واتساب جماعية بشكل احترافي. إدارة جهات الاتصال، إنشاء حملات، وتنمية أعمالك باستخدام منصة المراسلة القوية.",
                    'keywords' => "رسائل واتساب جماعية, إرسال واتساب جماعي, تسويق واتساب, إدارة جهات الاتصال, إدارة الحملات",
                ],
            ],
            'dashboard' => [
                'en' => [
                    'title' => "Dashboard - $appName",
                    'description' => "Access your WhatsApp bulk messaging dashboard. View statistics, manage campaigns, and monitor your messaging performance.",
                    'keywords' => "dashboard, WhatsApp statistics, campaign management, message tracking",
                ],
                'ar' => [
                    'title' => "لوحة التحكم - $appName",
                    'description' => "الوصول إلى لوحة تحكم رسائل واتساب الجماعية. عرض الإحصائيات وإدارة الحملات ومراقبة أداء الرسائل.",
                    'keywords' => "لوحة التحكم, إحصائيات واتساب, إدارة الحملات, تتبع الرسائل",
                ],
            ],
            'contacts' => [
                'en' => [
                    'title' => "Contact Management - $appName",
                    'description' => "Manage your WhatsApp contacts efficiently. Import, organize, and segment contacts for targeted messaging campaigns.",
                    'keywords' => "contact management, WhatsApp contacts, import contacts, contact organization",
                ],
                'ar' => [
                    'title' => "إدارة جهات الاتصال - $appName",
                    'description' => "إدارة جهات اتصال واتساب بكفاءة. استيراد وتنظيم وتقسيم جهات الاتصال لحملات رسائل مستهدفة.",
                    'keywords' => "إدارة جهات الاتصال, جهات اتصال واتساب, استيراد جهات الاتصال, تنظيم جهات الاتصال",
                ],
            ],
            'campaigns' => [
                'en' => [
                    'title' => "Campaign Management - $appName",
                    'description' => "Create and manage WhatsApp marketing campaigns. Schedule messages, track delivery, and optimize your outreach.",
                    'keywords' => "WhatsApp campaigns, marketing campaigns, message scheduling, campaign tracking",
                ],
                'ar' => [
                    'title' => "إدارة الحملات - $appName",
                    'description' => "إنشاء وإدارة حملات تسويق واتساب. جدولة الرسائل وتتبع التسليم وتحسين التواصل.",
                    'keywords' => "حملات واتساب, حملات تسويقية, جدولة الرسائل, تتبع الحملات",
                ],
            ],
        ];

        $page = $page ?? 'home';
        $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';

        return $metas[$page][$locale] ?? $metas['home'][$locale];
    }

    /**
     * Get structured data for organization
     */
    public static function getOrganizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => config('app.url'),
            'logo' => asset('android-icon-192x192.png'),
            'description' => 'Professional WhatsApp Bulk Messaging System',
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'EG',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'Customer Service',
                'availableLanguage' => ['English', 'Arabic'],
            ],
        ];
    }

    /**
     * Get structured data for web application
     */
    public static function getWebApplicationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => config('app.name'),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web Browser',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'ratingCount' => '150',
            ],
        ];
    }

    /**
     * Get Open Graph tags
     */
    public static function getOpenGraphTags(string $page = 'home', string $locale = 'en'): array
    {
        $meta = self::getMetaTags($page, $locale);
        $baseUrl = config('app.url');

        return [
            'og:type' => 'website',
            'og:title' => $meta['title'],
            'og:description' => $meta['description'],
            'og:url' => $baseUrl,
            'og:site_name' => config('app.name'),
            'og:locale' => $locale === 'ar' ? 'ar_EG' : 'en_US',
            'og:locale:alternate' => $locale === 'ar' ? 'en_US' : 'ar_EG',
            'og:image' => asset('android-icon-192x192.png'),
            'og:image:secure_url' => asset('android-icon-192x192.png'),
            'og:image:width' => '192',
            'og:image:height' => '192',
            'og:image:type' => 'image/png',
            'og:image:alt' => config('app.name') . ' Logo',
        ];
    }

    /**
     * Generate breadcrumb schema
     */
    public static function getBreadcrumbSchema(array $items): array
    {
        $itemListElement = [];

        foreach ($items as $index => $item) {
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemListElement,
        ];
    }
}
