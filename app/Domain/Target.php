<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable "thing we can notify or pin ownership to".
 *
 * A target is one of:
 *  - User  (a WP user ID — for owner-style notifications)
 *  - Role  (a WP role slug — expanded to its current member user IDs at run-time;
 *           reference, not snapshot)
 *  - Email (a free-form email address — for external CC / mailing-list addresses)
 *
 * Targets are used everywhere `owners` and `recipients` flow:
 * {@see Rule}, {@see EffectiveSettings},
 * {@see \ScheduledPageReviews\Cron\ReviewScanner},
 * {@see \ScheduledPageReviews\Notifications\NotificationDispatcher}.
 */
final class Target implements JsonSerializable
{
    /**
     * @param int|string $value int when {@see $type} is {@see TargetType::User},
     *                          non-empty string for Role and Email.
     */
    public function __construct(
        public readonly TargetType $type,
        public readonly int|string $value,
    ) {
        $this->assertValid();
    }

    /**
     * @return array{type: string, value: int|string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function user(int $id): self
    {
        return new self(TargetType::User, $id);
    }

    public static function role(string $slug): self
    {
        return new self(TargetType::Role, $slug);
    }

    public static function email(string $email): self
    {
        return new self(TargetType::Email, $email);
    }

    public function isUser(): bool
    {
        return $this->type === TargetType::User;
    }

    public function isRole(): bool
    {
        return $this->type === TargetType::Role;
    }

    public function isEmail(): bool
    {
        return $this->type === TargetType::Email;
    }

    public function userId(): ?int
    {
        return $this->isUser() && is_int($this->value) ? $this->value : null;
    }

    public function roleSlug(): ?string
    {
        return $this->isRole() && is_string($this->value) ? $this->value : null;
    }

    public function emailValue(): ?string
    {
        return $this->isEmail() && is_string($this->value) ? $this->value : null;
    }

    /**
     * Stable key for dedupe, e.g. "user:7", "role:editor", "email:foo@bar.com".
     */
    public function key(): string
    {
        return $this->type->value . ':' . (string) $this->value;
    }

    /**
     * @return array{type: string, value: int|string}
     */
    public function toArray(): array
    {
        return [
            'type'  => $this->type->value,
            'value' => $this->value,
        ];
    }

    /**
     * Parse a single target from raw input. Returns null on anything malformed.
     *
     * @param mixed $raw
     */
    public static function fromArray(mixed $raw): ?self
    {
        if (!is_array($raw)) {
            return null;
        }
        $type = TargetType::tryParse($raw['type'] ?? null);
        if ($type === null) {
            return null;
        }
        $rawValue = $raw['value'] ?? null;

        return match ($type) {
            TargetType::User  => self::tryUser($rawValue),
            TargetType::Role  => self::tryRole($rawValue),
            TargetType::Email => self::tryEmail($rawValue),
        };
    }

    /**
     * Parse a list of targets, dropping any malformed entries and deduping by {@see key()}.
     *
     * @param mixed $raw
     * @return list<self>
     */
    public static function listFromArray(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $byKey = [];
        foreach ($raw as $item) {
            $target = self::fromArray($item);
            if ($target === null) {
                continue;
            }
            $byKey[$target->key()] = $target;
        }
        return array_values($byKey);
    }

    /**
     * Parse a list that may be in the legacy (flat) format OR the new (tagged) format.
     *
     * Legacy formats:
     *  - Owners:     a list of integer user IDs (or numeric strings).
     *  - Recipients: a list of email strings.
     *
     * The {@see $kindIfLegacy} argument tells the parser how to interpret
     * the legacy flat items it encounters. Mixed lists (some tagged, some
     * legacy) are tolerated so existing post meta keeps loading.
     *
     * @param mixed       $raw
     * @param TargetType  $kindIfLegacy What legacy bare scalars should be treated as.
     * @return list<self>
     */
    public static function listFromMixed(mixed $raw, TargetType $kindIfLegacy): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $byKey = [];
        foreach ($raw as $item) {
            $target = null;
            if (is_array($item)) {
                $target = self::fromArray($item);
            } elseif ($kindIfLegacy === TargetType::User) {
                $target = self::tryUser($item);
            } elseif ($kindIfLegacy === TargetType::Email) {
                $target = self::tryEmail($item);
            } elseif ($kindIfLegacy === TargetType::Role) {
                $target = self::tryRole($item);
            }
            if ($target === null) {
                continue;
            }
            $byKey[$target->key()] = $target;
        }
        return array_values($byKey);
    }

    /**
     * Convenience: extract the user IDs from a target list (excluding any
     * Role or Email entries). Roles are NOT expanded here — that happens in
     * the cron scanner where {@see get_users()} is available.
     *
     * @param list<self> $targets
     * @return list<int>
     */
    public static function pluckUserIds(array $targets): array
    {
        $ids = [];
        foreach ($targets as $target) {
            $id = $target->userId();
            if ($id !== null && $id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Convenience: extract email strings from a target list.
     *
     * @param list<self> $targets
     * @return list<string>
     */
    public static function pluckEmails(array $targets): array
    {
        $emails = [];
        foreach ($targets as $target) {
            $email = $target->emailValue();
            if ($email !== null && $email !== '') {
                $emails[] = $email;
            }
        }
        return array_values(array_unique($emails));
    }

    /**
     * Convenience: extract role slugs from a target list.
     *
     * @param list<self> $targets
     * @return list<string>
     */
    public static function pluckRoleSlugs(array $targets): array
    {
        $slugs = [];
        foreach ($targets as $target) {
            $slug = $target->roleSlug();
            if ($slug !== null && $slug !== '') {
                $slugs[] = $slug;
            }
        }
        return array_values(array_unique($slugs));
    }

    /**
     * Merge two target lists, deduping by {@see key()}. Later lists do not
     * override earlier entries with the same key.
     *
     * @param list<self> $first
     * @param list<self> $second
     * @return list<self>
     */
    public static function mergeLists(array $first, array $second): array
    {
        $byKey = [];
        foreach ([$first, $second] as $list) {
            foreach ($list as $target) {
                if (!$target instanceof self) {
                    continue;
                }
                $byKey[$target->key()] ??= $target;
            }
        }

        return array_values($byKey);
    }

    /**
     * @return list<array{type: string, value: int|string}>
     */
    public static function listToArray(array $targets): array
    {
        $out = [];
        foreach ($targets as $target) {
            if ($target instanceof self) {
                $out[] = $target->toArray();
            }
        }
        return $out;
    }

    private static function tryUser(mixed $raw): ?self
    {
        if (is_int($raw) && $raw > 0) {
            return new self(TargetType::User, $raw);
        }
        if (is_string($raw) && ctype_digit($raw) && (int) $raw > 0) {
            return new self(TargetType::User, (int) $raw);
        }
        return null;
    }

    private static function tryRole(mixed $raw): ?self
    {
        if (!is_string($raw)) {
            return null;
        }
        $slug = trim($raw);
        if ($slug === '') {
            return null;
        }
        return new self(TargetType::Role, $slug);
    }

    private static function tryEmail(mixed $raw): ?self
    {
        if (!is_string($raw)) {
            return null;
        }
        $email = trim($raw);
        if ($email === '') {
            return null;
        }
        return new self(TargetType::Email, $email);
    }

    private function assertValid(): void
    {
        match ($this->type) {
            TargetType::User => is_int($this->value) && $this->value > 0
                ? null
                : throw new InvalidArgumentException(
                    'User target requires a positive integer user ID; got ' . wp_json_encode($this->value)
                ),
            TargetType::Role => is_string($this->value) && $this->value !== ''
                ? null
                : throw new InvalidArgumentException('Role target requires a non-empty string slug.'),
            TargetType::Email => is_string($this->value) && $this->value !== ''
                ? null
                : throw new InvalidArgumentException('Email target requires a non-empty string email.'),
        };
    }
}
