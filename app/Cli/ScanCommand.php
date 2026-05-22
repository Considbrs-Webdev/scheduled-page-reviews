<?php

declare(strict_types=1);

namespace ContentOwnership\Cli;

use ContentOwnership\Cron\Scheduler;
use RuntimeException;
use WP_CLI;

if (! defined('ABSPATH')) {
    exit;
}

final class ScanCommand
{
    /**
     * Scan pages for review reminders and send digest emails.
     *
     * ## OPTIONS
     *
     * [--background]
     * : Schedule batched ticks via WP-Cron instead of running synchronously.
     *
     * ## EXAMPLES
     *
     *     wp content-ownership scan
     *     wp content-ownership scan --background
     *
     * @param list<string>        $args
     * @param array<string, mixed> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $scheduler  = \ContentOwnership\di(Scheduler::class);
        $background = isset($assocArgs['background']);

        try {
            if ($background) {
                $result = $scheduler->startBackgroundRun();
                WP_CLI::success(
                    sprintf(
                        /* translators: %s: run id */
                        __('Background scan scheduled (run %s). Execute due WP-Cron events to process batches.', 'content-ownership'),
                        $result->runId
                    )
                );
                return;
            }

            $result = $scheduler->runToCompletion();
            WP_CLI::success(
                sprintf(
                    /* translators: 1: pages processed, 2: notifications queued, 3: emails sent */
                    __('Scan complete — %1$d pages processed, %2$d queued for notification, %3$d emails sent.', 'content-ownership'),
                    $result->stats['processed'],
                    $result->stats['queued'],
                    $result->emailsSent
                )
            );
        } catch (RuntimeException $exception) {
            WP_CLI::error($exception->getMessage());
        }
    }
}
