<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

/**
 * Compact ownership state for a page tree row.
 *
 * Combines the page's stored rule with its fully resolved effective
 * settings so the admin tree can show local, propagated and inherited
 * states without loading the detail pane.
 */
final class InheritanceSummary
{
    /**
     * @param list<int> $inheritedFrom
     * @param list<string> $localFields
     * @param list<string> $propagatedFields
     * @param list<string> $inheritedFields
     */
    public function __construct(
        public readonly bool $hasInherited,
        public readonly bool $hasDefault,
        public readonly array $inheritedFrom,
        public readonly array $localFields,
        public readonly array $propagatedFields,
        public readonly array $inheritedFields,
    ) {
    }

    public static function fromRuleAndEffective(?Rule $rule, EffectiveSettings $effective): self
    {
        $localFields      = [];
        $propagatedFields = [];

        if ($rule !== null) {
            foreach (RuleField::cases() as $field) {
                $scoped = $rule->get($field);
                if ($scoped === null) {
                    continue;
                }
                if ($scoped->isSubtree()) {
                    $propagatedFields[] = $field->value;
                    continue;
                }
                $localFields[] = $field->value;
            }
        }

        $inheritedFields = [];
        $inheritedFrom   = [];
        $hasDefault      = false;

        foreach (RuleField::cases() as $field) {
            $resolution = $effective->get($field);
            if ($resolution->source === FieldSource::Inherited) {
                $inheritedFields[] = $field->value;
                if ($resolution->fromPageId !== null) {
                    $inheritedFrom[$resolution->fromPageId] = true;
                }
                continue;
            }
            if ($resolution->source === FieldSource::GlobalDefault) {
                $hasDefault = true;
            }
        }

        $sourcePageIds = array_keys($inheritedFrom);
        sort($sourcePageIds, SORT_NUMERIC);

        return new self(
            hasInherited: $inheritedFields !== [],
            hasDefault: $hasDefault,
            inheritedFrom: $sourcePageIds,
            localFields: $localFields,
            propagatedFields: $propagatedFields,
            inheritedFields: $inheritedFields,
        );
    }

    /**
     * @return array{
     *     has_inherited: bool,
     *     has_default: bool,
     *     inherited_from: list<int>,
     *     local_fields: list<string>,
     *     propagated_fields: list<string>,
     *     inherited_fields: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'has_inherited'     => $this->hasInherited,
            'has_default'       => $this->hasDefault,
            'inherited_from'    => $this->inheritedFrom,
            'local_fields'      => $this->localFields,
            'propagated_fields' => $this->propagatedFields,
            'inherited_fields'  => $this->inheritedFields,
        ];
    }
}
