<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$scheduled_page_reviews_plugin_root = dirname(__DIR__);

return [
    'plugin_root'         => $scheduled_page_reviews_plugin_root,
    'plugin_file'         => $scheduled_page_reviews_plugin_root . '/scheduled-page-reviews.php',
    'dist_dir'            => $scheduled_page_reviews_plugin_root . '/dist',
    'views_dir'           => $scheduled_page_reviews_plugin_root . '/resources/views',
    'emails_dir'          => $scheduled_page_reviews_plugin_root . '/resources/views/emails',
    'languages_dir'       => $scheduled_page_reviews_plugin_root . '/resources/languages',
    'asset_handle_prefix' => 'scheduled-page-reviews',
];
