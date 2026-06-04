<?php

declare(strict_types=1);

namespace ContentOwnership\Admin;

use ContentOwnership\Application\Capabilities;
use WP_User;

/**
 * Maps and grants the plugin settings meta-capability via {@see Capabilities::userCanManageSettings()}.
 *
 * {@see user_has_cap} grants {@see Capabilities::MENU} when the filter allows it, so
 * {@see current_user_can()} stays consistent even when the user lacks the configured
 * admin primitive capability.
 */
final class AccessControl
{
    public function __construct()
    {
        add_filter('map_meta_cap', [$this, 'mapSettingsCapability'], 10, 4);
        add_filter('user_has_cap', [$this, 'grantSettingsCapability'], 10, 4);
    }

    /**
     * @param string[]          $caps
     * @param string            $cap
     * @param int               $userId
     * @param array<int, mixed> $args
     * @return string[]
     */
    public function mapSettingsCapability(array $caps, string $cap, int $userId, array $args): array
    {
        return self::mapMetaCap($caps, $cap, $userId);
    }

    /**
     * @param array<string, bool> $allcaps
     * @param string[]            $caps
     * @param array<int, mixed>   $args
     * @return array<string, bool>
     */
    public function grantSettingsCapability(array $allcaps, array $caps, array $args, WP_User $user): array
    {
        if (!in_array(Capabilities::menu(), $caps, true)) {
            return $allcaps;
        }

        if (self::userMayManageSettings((int) $user->ID)) {
            $allcaps[Capabilities::menu()] = true;
        }

        return $allcaps;
    }

    /**
     * @param string[] $caps
     * @return string[]
     */
    public static function mapMetaCap(array $caps, string $cap, int $userId): array
    {
        if ($cap !== Capabilities::menu()) {
            return $caps;
        }

        if (!self::userMayManageSettings($userId)) {
            return ['do_not_allow'];
        }

        return [Capabilities::menu()];
    }

    public static function userMayManageSettings(int $userId): bool
    {
        return Capabilities::userCanManageSettings($userId);
    }
}
