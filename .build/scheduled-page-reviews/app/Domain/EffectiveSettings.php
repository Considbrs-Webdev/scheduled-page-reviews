<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

/**
 * Fully-resolved settings for a single page.
 *
 * Each field carries both its value and a {@see Resolution} describing
 * where the value came from. Recipients are returned as typed lists of
 * {@see Target}s so callers (cron, REST, dashboard) can act on mixed
 * user/role/email targeting without re-parsing.
 */
final class EffectiveSettings
{
    public function __construct(
        public readonly Resolution $intervalDays,
        public readonly Resolution $recipients,
        public readonly Resolution $notifyBefore,
    ) {
    }

    public function get(RuleField $field): Resolution
    {
        return match ($field) {
            RuleField::IntervalDays => $this->intervalDays,
            RuleField::Recipients   => $this->recipients,
            RuleField::NotifyBefore => $this->notifyBefore,
        };
    }

    public function intervalDaysValue(): int
    {
        return is_int($this->intervalDays->value) ? $this->intervalDays->value : 0;
    }

    public function notifyBeforeValue(): int
    {
        return is_int($this->notifyBefore->value) ? $this->notifyBefore->value : 0;
    }

    /**
     * Typed list of recipient targets (user|role|email).
     *
     * @return list<Target>
     */
    public function recipientsValue(): array
    {
        $raw = $this->recipients->value;
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if ($item instanceof Target) {
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * Just the email strings among the recipient targets.
     *
     * @return list<string>
     */
    public function recipientEmails(): array
    {
        return Target::pluckEmails($this->recipientsValue());
    }

    /**
     * Just the user IDs among the recipient targets.
     *
     * @return list<int>
     */
    public function recipientUserIds(): array
    {
        return Target::pluckUserIds($this->recipientsValue());
    }

    /**
     * Just the role slugs among the recipient targets.
     *
     * @return list<string>
     */
    public function recipientRoleSlugs(): array
    {
        return Target::pluckRoleSlugs($this->recipientsValue());
    }

    /**
     * Whether a WP user (by ID or role membership) is among the effective
     * recipients. Email-only targets are excluded — they have no WP account.
     *
     * @param list<string> $userRoles
     */
    public function isAssignedToUser(int $userId, array $userRoles): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (in_array($userId, $this->recipientUserIds(), true)) {
            return true;
        }

        foreach ($this->recipientRoleSlugs() as $slug) {
            if (in_array($slug, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array{value: mixed, source: string, from: ?int}>
     */
    public function toArray(): array
    {
        return [
            RuleField::IntervalDays->value => $this->intervalDays->toArray(),
            RuleField::Recipients->value   => $this->recipientsResolutionAsArray(),
            RuleField::NotifyBefore->value => $this->notifyBefore->toArray(),
        ];
    }

    /**
     * @return array{value: mixed, source: string, from: ?int}
     */
    private function recipientsResolutionAsArray(): array
    {
        $arr = $this->recipients->toArray();
        $arr['value'] = Target::listToArray($this->recipientsValue());
        return $arr;
    }
}
