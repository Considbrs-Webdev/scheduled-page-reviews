<?php

declare(strict_types=1);

namespace ContentOwnership\Storage;

use ContentOwnership\Application\Config;
use ContentOwnership\Domain\Contracts\RuleSource;
use ContentOwnership\Domain\Rule;
use ContentOwnership\Domain\RuleField;
use ContentOwnership\Domain\ScopedValue;

/**
 * Persists per-page {@see Rule}s as a single JSON post_meta row.
 *
 * Single-row layout (one meta key per page) means:
 *  - Reading a page's rule is one get_post_meta() call.
 *  - Saving is one update_post_meta() call.
 *  - Pages without overrides have no meta row at all (no row pollution
 *    on default-everything sites).
 *
 * Reads go through the persistent object cache with a sentinel value for
 * the "no rule" case to prevent repeated misses during a single request.
 * Cache is busted on save/delete and on WP's deleted_post hook.
 */
final class RuleRepository implements RuleSource
{
    private const CACHE_GROUP    = 'content_ownership';
    private const CACHE_SENTINEL = '__none__';

    public function __construct()
    {
        add_action('deleted_post', [$this, 'onPostDeleted'], 10, 1);
        add_action('content_ownership/rule/save_completed', [$this, 'onPostSaved'], 10, 1);
    }

    public function getForPage(int $pageId): ?Rule
    {
        if ($pageId <= 0) {
            return null;
        }

        $cacheKey = $this->cacheKey($pageId);
        $cached   = wp_cache_get($cacheKey, self::CACHE_GROUP);

        if ($cached === self::CACHE_SENTINEL) {
            return null;
        }
        if ($cached instanceof Rule) {
            return $cached;
        }

        $raw = get_post_meta($pageId, $this->metaKey(), true);
        if (!is_string($raw) || $raw === '') {
            wp_cache_set($cacheKey, self::CACHE_SENTINEL, self::CACHE_GROUP);
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            wp_cache_set($cacheKey, self::CACHE_SENTINEL, self::CACHE_GROUP);
            return null;
        }

        $rule = Rule::fromArray($decoded);

        if ($rule->isEmpty()) {
            wp_cache_set($cacheKey, self::CACHE_SENTINEL, self::CACHE_GROUP);
            return null;
        }

        wp_cache_set($cacheKey, $rule, self::CACHE_GROUP);
        return $rule;
    }

    /**
     * Save a rule, or delete it if it has no fields.
     *
     * Sanitization of free-form text (e.g. recipient email addresses) is
     * applied here at the storage boundary so caller-facing code can stay
     * declarative.
     */
    public function save(int $pageId, Rule $rule): void
    {
        if ($pageId <= 0) {
            return;
        }

        $sanitized = $this->sanitize($rule);

        if ($sanitized->isEmpty()) {
            $this->delete($pageId);
            return;
        }

        $json = wp_json_encode($sanitized->toArray());
        if (!is_string($json)) {
            return;
        }

        update_post_meta($pageId, $this->metaKey(), wp_slash($json));
        $this->invalidate($pageId);

        do_action('content_ownership/rule/save_completed', $pageId);
    }

    public function delete(int $pageId): void
    {
        if ($pageId <= 0) {
            return;
        }
        delete_post_meta($pageId, $this->metaKey());
        $this->invalidate($pageId);
    }

    public function onPostDeleted(int $pageId): void
    {
        $this->invalidate($pageId);
    }

    public function onPostSaved(int $pageId): void
    {
        $this->invalidate($pageId);
    }

    private function invalidate(int $pageId): void
    {
        wp_cache_delete($this->cacheKey($pageId), self::CACHE_GROUP);
    }

    /**
     * Apply WP-aware sanitization without changing rule shape.
     *
     * Owners are constrained to existing user IDs; recipients are validated
     * as emails. Anything else falls through unchanged because the domain
     * layer has already enforced shape.
     */
    private function sanitize(Rule $rule): Rule
    {
        $owners = $rule->owners;
        if ($owners !== null && is_array($owners->value)) {
            $clean = [];
            foreach ($owners->value as $userId) {
                if (!is_int($userId) || $userId <= 0) {
                    continue;
                }
                if (function_exists('get_userdata') && !get_userdata($userId)) {
                    continue;
                }
                $clean[] = $userId;
            }
            $owners = $clean === []
                ? null
                : new ScopedValue(array_values(array_unique($clean)), $owners->scope);
            $rule = $rule->with(RuleField::Owners, $owners);
        }

        $recipients = $rule->recipients;
        if ($recipients !== null && is_array($recipients->value)) {
            $clean = [];
            foreach ($recipients->value as $email) {
                if (!is_string($email)) {
                    continue;
                }
                $email = sanitize_email($email);
                if ($email !== '' && is_email($email)) {
                    $clean[] = $email;
                }
            }
            $recipients = $clean === []
                ? null
                : new ScopedValue(array_values(array_unique($clean)), $recipients->scope);
            $rule = $rule->with(RuleField::Recipients, $recipients);
        }

        return $rule;
    }

    private function metaKey(): string
    {
        /** @var array<string, string> $keys */
        $keys = Config::get('settings', 'meta_keys', []);
        return $keys['rule'] ?? '_content_ownership_rule';
    }

    private function cacheKey(int $pageId): string
    {
        return 'rule:' . $pageId;
    }
}
