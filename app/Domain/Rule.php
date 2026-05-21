<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

/**
 * Immutable per-page rule.
 *
 * A rule is a sparse collection of {@see ScopedValue}s keyed by
 * {@see RuleField}. Any combination of fields may be present; unset fields
 * mean "inherit from ancestors or fall back to global defaults".
 *
 * Empty rules ({@see Rule::isEmpty()}) must never be persisted; storage
 * deletes the underlying meta row instead.
 */
final class Rule
{
    public function __construct(
        public readonly ?ScopedValue $intervalDays = null,
        public readonly ?ScopedValue $recipients   = null,
        public readonly ?ScopedValue $notifyBefore = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->intervalDays === null
            && $this->recipients === null
            && $this->notifyBefore === null;
    }

    public function get(RuleField $field): ?ScopedValue
    {
        return match ($field) {
            RuleField::IntervalDays => $this->intervalDays,
            RuleField::Recipients   => $this->recipients,
            RuleField::NotifyBefore => $this->notifyBefore,
        };
    }

    public function has(RuleField $field): bool
    {
        return $this->get($field) !== null;
    }

    /**
     * Return a new rule with the given field replaced.
     *
     * Passing null clears the field.
     */
    public function with(RuleField $field, ?ScopedValue $value): self
    {
        return new self(
            intervalDays: $field === RuleField::IntervalDays ? $value : $this->intervalDays,
            recipients:   $field === RuleField::Recipients   ? $value : $this->recipients,
            notifyBefore: $field === RuleField::NotifyBefore ? $value : $this->notifyBefore,
        );
    }

    /**
     * Serialize to the wire / storage shape.
     *
     * @return array<string, array{value: mixed, scope: string}>
     */
    public function toArray(): array
    {
        $out = [];
        foreach (RuleField::cases() as $field) {
            $scoped = $this->get($field);
            if ($scoped !== null) {
                $out[$field->value] = $scoped->toArray();
            }
        }
        return $out;
    }

    /**
     * Hydrate a Rule from a raw associative array.
     *
     * Unknown keys are ignored. Entries with an unparseable scope or missing
     * value are skipped. Per-field type coercion happens here so callers
     * always receive correctly-typed {@see ScopedValue::$value}s.
     *
     * Legacy `owners` entries are merged into `recipients` on load so older
     * persisted rules keep working after the UI was unified into one field.
     *
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $rule        = new self();
        $legacyOwners = null;

        foreach ($raw as $key => $entry) {
            if ($key === 'owners') {
                $legacyOwners = $entry;
                continue;
            }

            $field = RuleField::tryParse($key);
            if ($field === null) {
                continue;
            }
            if (!is_array($entry) || !array_key_exists('value', $entry)) {
                continue;
            }
            $scope = RuleScope::tryParse($entry['scope'] ?? null);
            if ($scope === null) {
                continue;
            }
            $coerced = self::coerceFieldValue($field, $entry['value']);
            if ($coerced === null) {
                continue;
            }
            $rule = $rule->with($field, new ScopedValue($coerced, $scope));
        }

        return self::mergeLegacyOwners($rule, $legacyOwners);
    }

    /**
     * Coerce a raw scalar/array into the per-field PHP type.
     *
     * Returns null when the input cannot be salvaged. Sanitization of
     * external user input (e.g. HTML in emails) is the storage layer's
     * responsibility — this method only enforces shape.
     *
     * Recipients are coerced into lists of {@see Target} value objects
     * (user|role|email). Legacy flat shapes (bare integer user IDs or bare
     * email strings) are tolerated so older persisted rules keep loading.
     */
    private static function coerceFieldValue(RuleField $field, mixed $raw): mixed
    {
        return match ($field) {
            RuleField::IntervalDays, RuleField::NotifyBefore => self::coerceNonNegativeInt($raw),
            RuleField::Recipients                            => self::coerceRecipientTargets($raw),
        };
    }

    private static function coerceNonNegativeInt(mixed $raw): ?int
    {
        if (is_int($raw) && $raw >= 0) {
            return $raw;
        }
        if (is_string($raw) && ctype_digit($raw)) {
            return (int) $raw;
        }
        return null;
    }

    /**
     * Recipients: user|role|email targets all welcome.
     *
     * @return list<Target>|null
     */
    private static function coerceRecipientTargets(mixed $raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }

        $list = Target::listFromMixed($raw, TargetType::Email);
        if ($list === []) {
            return [];
        }

        return $list;
    }

    /**
     * @param mixed $legacyOwners Raw `owners` entry from persisted JSON.
     */
    private static function mergeLegacyOwners(self $rule, mixed $legacyOwners): self
    {
        if (!is_array($legacyOwners) || !array_key_exists('value', $legacyOwners)) {
            return $rule;
        }

        $scope = RuleScope::tryParse($legacyOwners['scope'] ?? null);
        if ($scope === null) {
            return $rule;
        }

        $ownerTargets = Target::listFromMixed($legacyOwners['value'], TargetType::User);
        if ($ownerTargets === []) {
            return $rule;
        }

        $existing = $rule->recipients;
        if ($existing === null) {
            return $rule->with(RuleField::Recipients, new ScopedValue($ownerTargets, $scope));
        }

        /** @var list<Target> $merged */
        $merged = Target::mergeLists(
            is_array($existing->value) ? $existing->value : [],
            $ownerTargets
        );

        return $rule->with(
            RuleField::Recipients,
            new ScopedValue($merged, $existing->scope)
        );
    }
}
