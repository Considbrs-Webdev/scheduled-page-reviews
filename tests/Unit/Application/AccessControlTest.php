<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Application;

use ContentOwnership\Admin\AccessControl;
use ContentOwnership\Application\Capabilities;
use PHPUnit\Framework\TestCase;

final class AccessControlTest extends TestCase
{
    protected function tearDown(): void
    {
        if (function_exists('remove_all_filters')) {
            remove_all_filters('content_ownership/can_manage_settings');
        }
        parent::tearDown();
    }

    public function test_map_meta_cap_denies_when_user_id_invalid(): void
    {
        $result = AccessControl::mapMetaCap([], Capabilities::menu(), 0);

        $this->assertSame(['do_not_allow'], $result);
    }

    public function test_map_meta_cap_returns_meta_cap_when_filter_grants_without_admin_primitive(): void
    {
        add_filter(
            'content_ownership/can_manage_settings',
            static fn (): bool => true,
            10,
            2
        );

        $result = AccessControl::mapMetaCap([], Capabilities::menu(), 42);

        $this->assertSame([Capabilities::menu()], $result);
    }

    public function test_map_meta_cap_denies_when_filter_revokes(): void
    {
        add_filter(
            'content_ownership/can_manage_settings',
            static fn (): bool => false,
            10,
            2
        );

        $result = AccessControl::mapMetaCap([], Capabilities::menu(), 42);

        $this->assertSame(['do_not_allow'], $result);
    }

    public function test_map_meta_cap_passthrough_for_other_caps(): void
    {
        $incoming = ['edit_posts'];

        $result = AccessControl::mapMetaCap($incoming, 'edit_posts', 1);

        $this->assertSame($incoming, $result);
    }
}
