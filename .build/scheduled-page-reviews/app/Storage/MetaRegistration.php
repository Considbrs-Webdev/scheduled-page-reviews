<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Storage;

use ScheduledPageReviews\Application\Capabilities;
use ScheduledPageReviews\Application\Config;
use ScheduledPageReviews\Application\Container;
use ScheduledPageReviews\Domain\PageAuthorization;

/**
 * Registers plugin post meta with restrictive auth callbacks.
 */
final class MetaRegistration
{
    public function __construct()
    {
        add_action('init', [$this, 'register'], 20);
    }

    public function register(): void
    {
        if (!function_exists('register_post_meta')) {
            return;
        }

        $keys = (array) Config::get('settings', 'meta_keys', []);

        $ruleKey = (string) ($keys['rule'] ?? '_scheduled_page_reviews_rule');
        register_post_meta(
            'page',
            $ruleKey,
            [
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => [$this, 'sanitizeRuleJson'],
                'auth_callback'     => [$this, 'canEditRuleMeta'],
            ]
        );

        $atKey = (string) ($keys['last_reviewed_at'] ?? '_scheduled_page_reviews_last_reviewed_at');
        register_post_meta(
            'page',
            $atKey,
            [
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => [$this, 'sanitizeIsoTimestamp'],
                'auth_callback'     => [$this, 'canEditReviewMeta'],
            ]
        );

        $byKey = (string) ($keys['last_reviewed_by'] ?? '_scheduled_page_reviews_last_reviewed_by');
        register_post_meta(
            'page',
            $byKey,
            [
                'type'              => 'integer',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => 'absint',
                'auth_callback'     => [$this, 'canEditReviewMeta'],
            ]
        );

        $notifiedKey = (string) ($keys['last_notified_at'] ?? '_scheduled_page_reviews_last_notified_at');
        register_post_meta(
            'page',
            $notifiedKey,
            [
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => [$this, 'sanitizeIsoTimestamp'],
                'auth_callback'     => [$this, 'canEditReviewMeta'],
            ]
        );
    }

    public function sanitizeRuleJson(mixed $value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $value : '';
    }

    public function sanitizeIsoTimestamp(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $trimmed = trim($value);

        return $trimmed;
    }

    public function canEditRuleMeta(bool $allowed, string $metaKey, int $postId, int $userId): bool
    {
        if ($postId <= 0 || get_post_type($postId) !== 'page') {
            return false;
        }

        if (!Container::has(PageAuthorization::class)) {
            return Capabilities::userCanManageSettings($userId);
        }

        $auth = Container::get(PageAuthorization::class);

        return $auth->canEditRule($userId);
    }

    public function canEditReviewMeta(bool $allowed, string $metaKey, int $postId, int $userId): bool
    {
        if ($postId <= 0 || get_post_type($postId) !== 'page') {
            return false;
        }

        if (!Container::has(PageAuthorization::class)) {
            return false;
        }

        $auth = Container::get(PageAuthorization::class);

        return $auth->canMarkReviewed($postId, $userId);
    }
}
