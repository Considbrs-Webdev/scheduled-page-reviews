<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

/**
 * Fully-resolved settings for a single page.
 *
 * Each field carries both its value and a {@see Resolution} describing
 * where the value came from. Owners and recipients are returned as typed
 * lists of {@see Target}s so callers (cron, REST, dashboard) can act on
 * mixed user/role/email targeting without re-parsing.
 */
final class EffectiveSettings
{
    public function __construct(
        public readonly Resolution $intervalDays,
        public readonly Resolution $owners,
        public readonly Resolution $recipients,
        public readonly Resolution $notifyBefore,
    ) {
    }

    public function get(RuleField $field): Resolution
    {
        return match ($field) {
            RuleField::IntervalDays => $this->intervalDays,
            RuleField::Owners       => $this->owners,
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
     * Typed list of owner targets (user|role).
     *
     * @return list<Target>
     */
    public function ownersValue(): array
    {
        $raw = $this->owners->value;
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
     * Just the user IDs among the owner targets (excluding role targets,
     * which are expanded at run-time by the scanner, not here).
     *
     * @return list<int>
     */
    public function ownerUserIds(): array
    {
        return Target::pluckUserIds($this->ownersValue());
    }

    /**
     * Just the role slugs among the owner targets.
     *
     * @return list<string>
     */
    public function ownerRoleSlugs(): array
    {
        return Target::pluckRoleSlugs($this->ownersValue());
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
     * @return array<string, array{value: mixed, source: string, from: ?int}>
     */
    public function toArray(): array
    {
        return [
            RuleField::IntervalDays->value => $this->intervalDays->toArray(),
            RuleField::Owners->value       => $this->ownersResolutionAsArray(),
            RuleField::Recipients->value   => $this->recipientsResolutionAsArray(),
            RuleField::NotifyBefore->value => $this->notifyBefore->toArray(),
        ];
    }

    /**
     * @return array{value: mixed, source: string, from: ?int}
     */
    private function ownersResolutionAsArray(): array
    {
        $arr = $this->owners->toArray();
        $arr['value'] = Target::listToArray($this->ownersValue());
        return $arr;
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
