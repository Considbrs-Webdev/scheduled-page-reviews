<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="co-plugin-header">
    <div class="co-plugin-header-bar">
        <div class="co-plugin-header-brand">
            <div class="co-plugin-header-logo" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="36" height="36" aria-hidden="true" focusable="false">
                    <rect width="512" height="512" rx="108" fill="#1E3A8A"/>
                    <path d="M382.06 233.77A128 128 0 1 1 282.61 130.8" fill="none" stroke="#FFFFFF" stroke-width="36" stroke-linecap="round"/>
                    <polygon points="331.52,141.19 290.85,92.06 274.38,169.53" fill="#FFFFFF"/>
                    <polyline points="188,268 236,316 378,152" fill="none" stroke="#F0A93C" stroke-width="34" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h4 class="co-plugin-header-title">
                <?php esc_html_e('Renewpage - Scheduled Page Reviews', 'renewpage-scheduled-page-reviews'); ?>
            </h4>
        </div>
        <div
            id="<?php echo esc_attr(\Renewpage\Admin\Header::INTERACTIVE_MOUNT_ID); ?>"
            class="co-plugin-header-interactive"
        ></div>
    </div>
</div>
