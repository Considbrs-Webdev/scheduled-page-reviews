<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap content-ownership-app content-ownership-wrap">
    <h1 class="screen-reader-text">
        <?php esc_html_e('Content Ownership', 'content-ownership'); ?>
    </h1>
    <div id="content-ownership-root" class="content-ownership-root"></div>
    <noscript>
        <div class="notice notice-error">
            <p><?php esc_html_e('This page requires JavaScript.', 'content-ownership'); ?></p>
        </div>
    </noscript>
</div>
