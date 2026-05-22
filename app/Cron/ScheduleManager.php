<?php

declare(strict_types=1);

namespace ContentOwnership\Cron;

use ContentOwnership\Domain\GlobalSettings;
use ContentOwnership\Storage\SettingsRepository;

/**
 * Registers or clears the recurring {@see Scheduler::DAILY_HOOK} event
 * based on global schedule settings.
 */
final class ScheduleManager
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {
        add_filter('cron_schedules', [$this, 'addWeeklySchedule']);
        add_action('content_ownership/settings/updated', [$this, 'onSettingsUpdated'], 10, 1);
        add_action('plugins_loaded', [$this, 'runOneTimeScheduleMigration'], 5);
    }

    public function runOneTimeScheduleMigration(): void
    {
        if (get_option('content_ownership_schedule_v2', false)) {
            return;
        }

        wp_clear_scheduled_hook(Scheduler::DAILY_HOOK);
        wp_clear_scheduled_hook(Scheduler::TICK_HOOK);
        $this->maybeScheduleCron($this->settings->get());
        update_option('content_ownership_schedule_v2', true, false);
    }

    /**
     * @param array<string, array<string, mixed>> $schedules
     * @return array<string, array<string, mixed>>
     */
    public function addWeeklySchedule(array $schedules): array
    {
        if (! isset($schedules['weekly'])) {
            $schedules['weekly'] = [
                'interval' => WEEK_IN_SECONDS,
                'display'  => __('Once Weekly', 'content-ownership'),
            ];
        }

        return $schedules;
    }

    public function onSettingsUpdated(GlobalSettings $settings): void
    {
        $this->maybeScheduleCron($settings);
    }

    public function maybeScheduleCron(GlobalSettings $settings): void
    {
        wp_clear_scheduled_hook(Scheduler::DAILY_HOOK);

        if (! $settings->autoScanEnabled) {
            return;
        }

        $frequency = $settings->scanFrequency === 'weekly' ? 'weekly' : 'daily';
        $timestamp = $this->nextRunTimestamp($settings->scanTime);

        wp_schedule_event($timestamp, $frequency, Scheduler::DAILY_HOOK);
    }

    /**
     * @return array<string, mixed>
     */
    public function scheduleInfo(GlobalSettings $settings): array
    {
        $next = wp_next_scheduled(Scheduler::DAILY_HOOK);

        return [
            'auto_scan_enabled' => $settings->autoScanEnabled,
            'scan_frequency'    => $settings->scanFrequency,
            'scan_time'         => $settings->scanTime,
            'next_scheduled'    => $next !== false ? (int) $next : null,
            'next_scheduled_iso' => $next !== false ? gmdate('c', (int) $next) : null,
            'wp_cron_disabled'  => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
        ];
    }

    private function nextRunTimestamp(string $scanTime): int
    {
        [$hour, $minute] = $this->parseScanTime($scanTime);
        $today           = strtotime(sprintf('today %02d:%02d:00', $hour, $minute));
        if ($today === false) {
            return time() + 60;
        }

        if ($today < time()) {
            $tomorrow = strtotime(sprintf('tomorrow %02d:%02d:00', $hour, $minute));
            if ($tomorrow !== false) {
                return $tomorrow;
            }
        }

        return $today;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseScanTime(string $scanTime): array
    {
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim($scanTime), $matches) !== 1) {
            return [3, 0];
        }

        $hour   = max(0, min(23, (int) $matches[1]));
        $minute = max(0, min(59, (int) $matches[2]));

        return [$hour, $minute];
    }
}
