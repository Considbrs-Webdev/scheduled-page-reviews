<?php

declare(strict_types=1);

$pluginRoot = dirname(__DIR__);

return [
    'plugin_root'         => $pluginRoot,
    'plugin_file'         => $pluginRoot . '/scheduled-page-reviews.php',
    'dist_dir'            => $pluginRoot . '/dist',
    'views_dir'           => $pluginRoot . '/resources/views',
    'emails_dir'          => $pluginRoot . '/resources/views/emails',
    'languages_dir'       => $pluginRoot . '/resources/languages',
    'asset_handle_prefix' => 'scheduled-page-reviews',
];
