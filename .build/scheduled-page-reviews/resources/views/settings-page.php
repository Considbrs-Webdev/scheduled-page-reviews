<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap scheduled-page-reviews-app scheduled-page-reviews-wrap">
    <h1 class="screen-reader-text">
        <?php esc_html_e('Scheduled Page Reviews', 'scheduled-page-reviews'); ?>
    </h1>
    <div id="scheduled-page-reviews-root" class="scheduled-page-reviews-root"></div>
    <noscript>
        <div class="notice notice-error">
            <p><?php esc_html_e('This page requires JavaScript.', 'scheduled-page-reviews'); ?></p>
        </div>
    </noscript>
</div>
