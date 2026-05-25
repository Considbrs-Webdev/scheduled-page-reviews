<?php

declare(strict_types=1);

namespace ContentOwnership\Application;

use ContentOwnership\Admin\AccessControl;
use ContentOwnership\Admin\DashboardWidget;
use ContentOwnership\Admin\EditorIntegration;
use ContentOwnership\Admin\Header;
use ContentOwnership\Admin\PostStates;
use ContentOwnership\Admin\RowActions;
use ContentOwnership\Admin\SettingsPage;
use ContentOwnership\Assets\Assets;
use ContentOwnership\Assets\ViteManifest;
use ContentOwnership\Cron\Contracts\NotificationQueueInterface;
use ContentOwnership\Cron\NotificationQueue;
use ContentOwnership\Cron\ReviewScanner;
use ContentOwnership\Cron\ScheduleManager;
use ContentOwnership\Cron\Scheduler;
use ContentOwnership\Domain\DashboardLister;
use ContentOwnership\Domain\InheritanceResolver;
use ContentOwnership\Domain\PageAuthorization;
use ContentOwnership\Domain\PageReviewMarker;
use ContentOwnership\Domain\RecipientVisibility;
use ContentOwnership\Domain\ReviewDateCalculator;
use ContentOwnership\Notifications\EmailRenderer;
use ContentOwnership\Notifications\NotificationDispatcher;
use ContentOwnership\Rest\CronController;
use ContentOwnership\Rest\DashboardController;
use ContentOwnership\Rest\MarkReviewedController;
use ContentOwnership\Rest\PageRuleController;
use ContentOwnership\Rest\RolesController;
use ContentOwnership\Rest\ScheduleController;
use ContentOwnership\Rest\SettingsController;
use ContentOwnership\Rest\TreeController;
use ContentOwnership\Rest\UsersController;
use ContentOwnership\Storage\RuleRepository;
use ContentOwnership\Storage\SettingsRepository;
use ContentOwnership\Storage\WpPageHierarchy;

/**
 * Plugin bootstrap.
 *
 * Instantiates each service exactly once into the static {@see Container}.
 * Services self-register their own WordPress hooks in their constructors,
 * keeping this class small and the dependency graph explicit.
 */
final class App
{
    public static function boot(): void
    {
        Container::register(I18n::class, new I18n());

        $settings   = new SettingsRepository();
        $rules      = new RuleRepository();
        $hierarchy  = new WpPageHierarchy();
        $resolver   = new InheritanceResolver($rules, $hierarchy);
        $calculator = new ReviewDateCalculator();

        Container::register(SettingsRepository::class, $settings);
        Container::register(RuleRepository::class, $rules);
        Container::register(WpPageHierarchy::class, $hierarchy);
        Container::register(InheritanceResolver::class, $resolver);
        Container::register(ReviewDateCalculator::class, $calculator);

        $reviewMarker = new PageReviewMarker($settings);
        Container::register(PageReviewMarker::class, $reviewMarker);

        $visibility = RecipientVisibility::fromConfig();
        Container::register(RecipientVisibility::class, $visibility);

        $authorization = new PageAuthorization($settings, $resolver, $visibility);
        Container::register(PageAuthorization::class, $authorization);

        $lister = new DashboardLister($settings, $resolver, $calculator, $visibility);
        Container::register(DashboardLister::class, $lister);

        $queue   = new NotificationQueue();
        $scanner = new ReviewScanner($settings, $resolver, $calculator, $queue, $hierarchy);
        $scheduler = new Scheduler($scanner, $queue);
        $scheduleManager = new ScheduleManager($settings);

        Container::register(NotificationQueueInterface::class, $queue);
        Container::register(NotificationQueue::class, $queue);
        Container::register(ReviewScanner::class, $scanner);
        Container::register(Scheduler::class, $scheduler);
        Container::register(ScheduleManager::class, $scheduleManager);

        $emailsDir = (string) Config::get('paths', 'emails_dir', '');
        $renderer  = new EmailRenderer(
            $emailsDir . '/digest.html.php',
            $emailsDir . '/digest.text.php',
        );
        Container::register(EmailRenderer::class, $renderer);
        Container::register(
            NotificationDispatcher::class,
            new NotificationDispatcher($renderer, $settings)
        );

        Container::register(SettingsController::class, new SettingsController($settings));
        Container::register(DashboardController::class, new DashboardController($lister));
        Container::register(
            PageRuleController::class,
            new PageRuleController($settings, $rules, $resolver, $calculator, $authorization)
        );
        Container::register(
            MarkReviewedController::class,
            new MarkReviewedController($reviewMarker, $authorization)
        );
        Container::register(
            TreeController::class,
            new TreeController($hierarchy, $rules, $resolver, $settings)
        );
        Container::register(CronController::class, new CronController($scheduler));
        Container::register(ScheduleController::class, new ScheduleController($settings, $scheduleManager));
        Container::register(RolesController::class, new RolesController());
        Container::register(UsersController::class, new UsersController());

        Container::register(ViteManifest::class, new ViteManifest());
        Container::register(Assets::class, new Assets());
        Container::register(AccessControl::class, new AccessControl());
        Container::register(SettingsPage::class, new SettingsPage());
        Container::register(Header::class, new Header());

        Container::register(PostStates::class, new PostStates($settings, $resolver, $calculator, $visibility));
        Container::register(RowActions::class, new RowActions($reviewMarker, $authorization));
        Container::register(EditorIntegration::class, new EditorIntegration());
        Container::register(DashboardWidget::class, new DashboardWidget($lister));
    }
}
