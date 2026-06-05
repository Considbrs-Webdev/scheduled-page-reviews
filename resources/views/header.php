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
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                    <path d="M12 11h4"/>
                    <path d="M12 16h4"/>
                    <path d="M8 11h.01"/>
                    <path d="M8 16h.01"/>
                </svg>
            </div>
            <h4 class="co-plugin-header-title">
                <?php esc_html_e('Scheduled Page Reviews', 'scheduled-page-reviews'); ?>
            </h4>
        </div>
        <div
            id="<?php echo esc_attr(\ScheduledPageReviews\Admin\Header::INTERACTIVE_MOUNT_ID); ?>"
            class="co-plugin-header-interactive"
        ></div>
    </div>
</div>
