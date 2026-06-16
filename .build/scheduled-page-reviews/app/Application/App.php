<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Application;

use ScheduledPageReviews\Admin\AccessControl;
use ScheduledPageReviews\Admin\BuildNotice;
use ScheduledPageReviews\Admin\DashboardWidget;
use ScheduledPageReviews\Admin\EditorIntegration;
use ScheduledPageReviews\Admin\Header;
use ScheduledPageReviews\Admin\PostStates;
use ScheduledPageReviews\Admin\RowActions;
use ScheduledPageReviews\Admin\SettingsPage;
use ScheduledPageReviews\Assets\Assets;
use ScheduledPageReviews\Assets\ViteManifest;
use ScheduledPageReviews\Cron\Contracts\NotificationQueueInterface;
use ScheduledPageReviews\Cron\NotificationQueue;
use ScheduledPageReviews\Cron\ReviewScanner;
use ScheduledPageReviews\Cron\ScheduleManager;
use ScheduledPageReviews\Cron\Scheduler;
use ScheduledPageReviews\Domain\DashboardLister;
use ScheduledPageReviews\Domain\InheritanceResolver;
use ScheduledPageReviews\Domain\PageAuthorization;
use ScheduledPageReviews\Domain\PageReviewMarker;
use ScheduledPageReviews\Domain\RecipientVisibility;
use ScheduledPageReviews\Domain\ReviewDateCalculator;
use ScheduledPageReviews\Notifications\EmailRenderer;
use ScheduledPageReviews\Notifications\NotificationDispatcher;
use ScheduledPageReviews\Rest\CronController;
use ScheduledPageReviews\Rest\DashboardController;
use ScheduledPageReviews\Rest\MarkReviewedController;
use ScheduledPageReviews\Rest\PageRuleController;
use ScheduledPageReviews\Rest\RolesController;
use ScheduledPageReviews\Rest\ScheduleController;
use ScheduledPageReviews\Rest\SettingsController;
use ScheduledPageReviews\Rest\TreeController;
use ScheduledPageReviews\Rest\UsersController;
use ScheduledPageReviews\Storage\MetaRegistration;
use ScheduledPageReviews\Storage\RuleRepository;
use ScheduledPageReviews\Storage\SettingsRepository;
use ScheduledPageReviews\Storage\WpPageHierarchy;

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
        Container::register(MetaRegistration::class, new MetaRegistration());
        Container::register(BuildNotice::class, new BuildNotice());
        Container::register(SettingsPage::class, new SettingsPage());
        Container::register(Header::class, new Header());

        Container::register(PostStates::class, new PostStates($settings, $resolver, $calculator, $visibility));
        Container::register(RowActions::class, new RowActions($reviewMarker, $authorization));
        Container::register(EditorIntegration::class, new EditorIntegration());
        Container::register(DashboardWidget::class, new DashboardWidget($lister));
    }
}
