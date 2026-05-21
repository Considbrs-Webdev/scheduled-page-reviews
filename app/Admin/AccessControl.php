<?php

declare(strict_types=1);

namespace ContentOwnership\Admin;

use ContentOwnership\Application\Capabilities;

/**
 * Maps the plugin settings meta-capability through {@see Capabilities::userCanManageSettings()}.
 */
final class AccessControl
{
    public function __construct()
    {
        add_filter('map_meta_cap', [$this, 'mapSettingsCapability'], 10, 4);
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
        if ($cap !== Capabilities::menu()) {
            return $caps;
        }

        if (!Capabilities::userCanManageSettings($userId)) {
            return ['do_not_allow'];
        }

        return [Capabilities::admin()];
    }
}
