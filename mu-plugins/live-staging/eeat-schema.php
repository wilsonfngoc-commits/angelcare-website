<?php
/**
 * Plugin Name: AngelCare E-E-A-T Schemas
 * Description: Organization (site-wide) + Person (背景故事, David anchor) JSON-LD.
 * Schema-only — NO visual changes (Wilson-approved 2026-08-04).
 */
if (!defined('ABSPATH')) { exit; }

add_action('wp_head', 'ac_eeat_schemas', 99);
function ac_eeat_schemas() {
    $home = home_url();

    // Organization schema — site-wide
    $org = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'AngelCare 宅天使',
        'url' => $home . '/',
        'logo' => $home . '/wp-content/uploads/2024/11/ANGELCARE-logo-final-20240506-03-1000X350.png',
        'founder' => [
            '@type' => 'Person',
            'name' => 'David Wong',
            'jobTitle' => '共同創辦人',
            'sameAs' => ['https://hk.linkedin.com/in/david-wong-b9b2a6ba'],
            'worksFor' => [
                '@type' => 'Organization',
                'name' => 'DoctorNow Group',
            ],
        ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";

    // Person schema — 背景故事 page (524), the David anchor page
    if (is_page(524)) {
        $person = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => 'David Wong',
            'jobTitle' => '共同創辦人',
            'image' => $home . '/wp-content/uploads/2025/02/david.jpg',
            'sameAs' => ['https://hk.linkedin.com/in/david-wong-b9b2a6ba'],
            'worksFor' => [
                '@type' => 'Organization',
                'name' => 'DoctorNow Group',
                'url' => 'https://www.doctornowhome.com/',
            ],
            'affiliation' => [
                '@type' => 'Organization',
                'name' => '香港在宅醫療協會',
            ],
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($person, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}
