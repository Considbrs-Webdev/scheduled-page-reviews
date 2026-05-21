<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

/**
 * Immutable pairing of a per-field value with its propagation {@see RuleScope}.
 *
 * The value is typed as mixed because each {@see RuleField} has its own
 * underlying PHP type (int, list<int>, list<string>, etc.). Per-field type
 * checking is performed at the storage boundary in
 * {@see \ContentOwnership\Storage\RuleRepository}.
 */
final class ScopedValue
{
    public function __construct(
        public readonly mixed $value,
        public readonly RuleScope $scope,
    ) {
    }

    public static function local(mixed $value): self
    {
        return new self($value, RuleScope::Local);
    }

    public static function subtree(mixed $value): self
    {
        return new self($value, RuleScope::Subtree);
    }

    public function isSubtree(): bool
    {
        return $this->scope === RuleScope::Subtree;
    }

    /**
     * @return array{value: mixed, scope: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'scope' => $this->scope->value,
        ];
    }
}
