<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$renewpage_plugin_root = dirname(__DIR__);

return [
    'plugin_root'         => $renewpage_plugin_root,
    'plugin_file'         => $renewpage_plugin_root . '/renewpage-scheduled-page-reviews.php',
    'dist_dir'            => $renewpage_plugin_root . '/dist',
    'views_dir'           => $renewpage_plugin_root . '/resources/views',
    'emails_dir'          => $renewpage_plugin_root . '/resources/views/emails',
    'languages_dir'       => $renewpage_plugin_root . '/resources/languages',
    'asset_handle_prefix' => 'renewpage-scheduled-page-reviews',
];
