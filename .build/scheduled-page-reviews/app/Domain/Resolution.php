<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

/**
 * Immutable per-field resolution result.
 *
 * Carries the resolved value, its {@see FieldSource}, and (when source is
 * Inherited) the page id the value was inherited from. The frontend uses
 * this triple to render badges like "inherited from /about" or
 * "set here (applies to subtree)".
 */
final class Resolution
{
    public function __construct(
        public readonly mixed $value,
        public readonly FieldSource $source,
        public readonly ?int $fromPageId = null,
    ) {
    }

    public static function defaulted(mixed $value): self
    {
        return new self($value, FieldSource::GlobalDefault);
    }

    public static function inheritedFrom(mixed $value, int $ancestorPageId): self
    {
        return new self($value, FieldSource::Inherited, $ancestorPageId);
    }

    public static function local(mixed $value, int $pageId): self
    {
        return new self($value, FieldSource::Local, $pageId);
    }

    public static function localPropagated(mixed $value, int $pageId): self
    {
        return new self($value, FieldSource::LocalPropagated, $pageId);
    }

    /**
     * @return array{value: mixed, source: string, from: ?int}
     */
    public function toArray(): array
    {
        return [
            'value'  => $this->value,
            'source' => $this->source->value,
            'from'   => $this->fromPageId,
        ];
    }
}
