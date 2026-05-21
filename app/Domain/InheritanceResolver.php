<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

use ContentOwnership\Domain\Contracts\PageHierarchy;
use ContentOwnership\Domain\Contracts\RuleSource;
use Generator;

/**
 * Resolves per-page effective settings by walking the page tree.
 *
 * Two modes, both deterministic:
 *
 *  1. {@see resolveForPage()} — bottom-up consumer view. Used by REST,
 *     editor sidebar and dashboard widget. O(depth) rule reads.
 *
 *  2. {@see walkTree()} — top-down DFS generator. Used by the cron
 *     scanner. Yields one {@see EffectiveSettings} per page in a single
 *     pre-order pass, threading the inheritance context down so each
 *     page's rule is read exactly once.
 *
 * The resolver depends on contracts ({@see RuleSource}, {@see PageHierarchy})
 * rather than concrete repositories so it can be unit-tested without
 * bootstrapping WordPress.
 */
final class InheritanceResolver
{
    public function __construct(
        private readonly RuleSource $rules,
        private readonly PageHierarchy $hierarchy,
    ) {
    }

    /**
     * Resolve effective settings for a single page by walking ancestors
     * top-down then applying the page's own rule last.
     */
    public function resolveForPage(int $pageId, GlobalSettings $defaults): EffectiveSettings
    {
        $context = $this->initialContext($defaults);

        foreach ($this->hierarchy->ancestorsTopDown($pageId) as $ancestorId) {
            $context = $this->applySubtreeOnly($context, $ancestorId);
        }

        $context = $this->applyLocalAndSubtree($context, $pageId);

        return $this->buildEffective($context);
    }

    /**
     * Top-down DFS over the page tree, yielding effective settings per page.
     *
     * Yields page-id => EffectiveSettings. Pre-order: a parent is yielded
     * before its children. Each page's rule is read at most once.
     *
     * @return Generator<int, EffectiveSettings>
     */
    public function walkTree(GlobalSettings $defaults, int $rootParentId = 0): Generator
    {
        $rootContext = $this->initialContext($defaults);

        foreach ($this->hierarchy->childIds($rootParentId) as $rootId) {
            yield from $this->dfs($rootId, $rootContext);
        }
    }

    /**
     * @param array<string, Resolution> $inherited
     * @return Generator<int, EffectiveSettings>
     */
    private function dfs(int $pageId, array $inherited): Generator
    {
        $rule = $this->rules->getForPage($pageId);

        $forPage = $this->applyRuleAsLocal($inherited, $rule, $pageId);
        yield $pageId => $this->buildEffective($forPage);

        $forDescendants = $this->applyRuleSubtreeOnly($inherited, $rule, $pageId);
        foreach ($this->hierarchy->childIds($pageId) as $childId) {
            yield from $this->dfs($childId, $forDescendants);
        }
    }

    /**
     * Initial context: global defaults applied to every field.
     *
     * @return array<string, Resolution>
     */
    private function initialContext(GlobalSettings $defaults): array
    {
        return [
            RuleField::IntervalDays->value => Resolution::defaulted($defaults->defaultIntervalDays),
            RuleField::Recipients->value   => Resolution::defaulted($defaults->defaultRecipients),
            RuleField::NotifyBefore->value => Resolution::defaulted($defaults->notifyDaysBefore),
        ];
    }

    /**
     * Layer an ancestor's subtree-scope fields onto an inheritance context.
     *
     * @param array<string, Resolution> $context
     * @return array<string, Resolution>
     */
    private function applySubtreeOnly(array $context, int $ancestorId): array
    {
        $rule = $this->rules->getForPage($ancestorId);
        if ($rule === null) {
            return $context;
        }
        return $this->applyRuleSubtreeOnly($context, $rule, $ancestorId);
    }

    /**
     * @param array<string, Resolution> $context
     * @return array<string, Resolution>
     */
    private function applyRuleSubtreeOnly(array $context, ?Rule $rule, int $ancestorId): array
    {
        if ($rule === null) {
            return $context;
        }
        foreach (RuleField::cases() as $field) {
            $scoped = $rule->get($field);
            if ($scoped === null || !$scoped->isSubtree()) {
                continue;
            }
            $context[$field->value] = Resolution::inheritedFrom($scoped->value, $ancestorId);
        }
        return $context;
    }

    /**
     * Apply this page's own rule (both scopes) as the final layer.
     *
     * @param array<string, Resolution> $context
     * @return array<string, Resolution>
     */
    private function applyLocalAndSubtree(array $context, int $pageId): array
    {
        $rule = $this->rules->getForPage($pageId);
        return $this->applyRuleAsLocal($context, $rule, $pageId);
    }

    /**
     * @param array<string, Resolution> $context
     * @return array<string, Resolution>
     */
    private function applyRuleAsLocal(array $context, ?Rule $rule, int $pageId): array
    {
        if ($rule === null) {
            return $context;
        }
        foreach (RuleField::cases() as $field) {
            $scoped = $rule->get($field);
            if ($scoped === null) {
                continue;
            }
            $context[$field->value] = $scoped->isSubtree()
                ? Resolution::localPropagated($scoped->value, $pageId)
                : Resolution::local($scoped->value, $pageId);
        }
        return $context;
    }

    /**
     * @param array<string, Resolution> $context
     */
    private function buildEffective(array $context): EffectiveSettings
    {
        return new EffectiveSettings(
            intervalDays: $context[RuleField::IntervalDays->value],
            recipients:   $context[RuleField::Recipients->value],
            notifyBefore: $context[RuleField::NotifyBefore->value],
        );
    }
}
