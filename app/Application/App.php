<?php

declare(strict_types=1);

namespace Renewpage\Application;

use Renewpage\Admin\AccessControl;
use Renewpage\Admin\BuildNotice;
use Renewpage\Admin\DashboardWidget;
use Renewpage\Admin\EditorIntegration;
use Renewpage\Admin\Header;
use Renewpage\Admin\PostStates;
use Renewpage\Admin\Privacy;
use Renewpage\Admin\RowActions;
use Renewpage\Admin\SettingsPage;
use Renewpage\Assets\Assets;
use Renewpage\Assets\ViteManifest;
use Renewpage\Cron\Contracts\NotificationQueueInterface;
use Renewpage\Cron\NotificationQueue;
use Renewpage\Cron\ReviewScanner;
use Renewpage\Cron\ScheduleManager;
use Renewpage\Cron\Scheduler;
use Renewpage\Domain\DashboardLister;
use Renewpage\Domain\InheritanceResolver;
use Renewpage\Domain\PageAuthorization;
use Renewpage\Domain\PageReviewMarker;
use Renewpage\Domain\RecipientVisibility;
use Renewpage\Domain\ReviewDateCalculator;
use Renewpage\Groups\GroupRegistry;
use Renewpage\Groups\RoleGroupProvider;
use Renewpage\Notifications\EmailRenderer;
use Renewpage\Notifications\NotificationDispatcher;
use Renewpage\Rest\CronController;
use Renewpage\Rest\DashboardController;
use Renewpage\Rest\GroupsController;
use Renewpage\Rest\MarkReviewedController;
use Renewpage\Rest\PageRuleController;
use Renewpage\Rest\RolesController;
use Renewpage\Rest\ScheduleController;
use Renewpage\Rest\SettingsController;
use Renewpage\Rest\TreeController;
use Renewpage\Rest\UsersController;
use Renewpage\Storage\MetaRegistration;
use Renewpage\Storage\RuleRepository;
use Renewpage\Storage\SettingsRepository;
use Renewpage\Storage\WpPageHierarchy;

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

        $roleGroups = new RoleGroupProvider();
        $groups     = new GroupRegistry([$roleGroups]);
        Container::register(RoleGroupProvider::class, $roleGroups);
        Container::register(GroupRegistry::class, $groups);

        $reviewMarker = new PageReviewMarker($settings);
        Container::register(PageReviewMarker::class, $reviewMarker);

        $visibility = RecipientVisibility::fromConfig();
        Container::register(RecipientVisibility::class, $visibility);

        $authorization = new PageAuthorization($settings, $resolver, $visibility, $groups);
        Container::register(PageAuthorization::class, $authorization);

        $lister = new DashboardLister($settings, $resolver, $calculator, $visibility, $groups);
        Container::register(DashboardLister::class, $lister);

        $queue   = new NotificationQueue();
        $scanner = new ReviewScanner($settings, $resolver, $calculator, $queue, $hierarchy, $groups);
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
        Container::register(RolesController::class, new RolesController($roleGroups));
        Container::register(GroupsController::class, new GroupsController($groups));
        Container::register(UsersController::class, new UsersController($groups));

        Container::register(ViteManifest::class, new ViteManifest());
        Container::register(Assets::class, new Assets());
        Container::register(AccessControl::class, new AccessControl());
        Container::register(MetaRegistration::class, new MetaRegistration());
        Container::register(BuildNotice::class, new BuildNotice());
        Container::register(Privacy::class, new Privacy());
        Container::register(SettingsPage::class, new SettingsPage());
        Container::register(Header::class, new Header());

        Container::register(PostStates::class, new PostStates($settings, $resolver, $calculator, $visibility, $groups));
        Container::register(RowActions::class, new RowActions($reviewMarker, $authorization));
        Container::register(EditorIntegration::class, new EditorIntegration());
        Container::register(DashboardWidget::class, new DashboardWidget($lister));
    }
}
