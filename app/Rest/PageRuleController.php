<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

use ContentOwnership\Application\Config;
use ContentOwnership\Domain\InheritanceResolver;
use ContentOwnership\Domain\ReviewDateCalculator;
use ContentOwnership\Domain\Rule;
use ContentOwnership\Storage\RuleRepository;
use ContentOwnership\Storage\SettingsRepository;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class PageRuleController
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly RuleRepository $rules,
        private readonly InheritanceResolver $resolver,
        private readonly ReviewDateCalculator $calculator,
    ) {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::NAMESPACE,
            '/pages/(?P<id>\d+)/rule',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getRule'],
                    'permission_callback' => [$this, 'canReadRule'],
                    'args'                => $this->idArgs(),
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'putRule'],
                    'permission_callback' => [$this, 'canEditRule'],
                    'args'                => $this->idArgs(),
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function getRule(WP_REST_Request $request): WP_REST_Response
    {
        $pageId = (int) $request['id'];

        return new WP_REST_Response($this->buildResponse($pageId), 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function putRule(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            return new WP_Error(
                'content_ownership_invalid_body',
                __('Request body must be a JSON object.', 'content-ownership'),
                ['status' => 400]
            );
        }

        $pageId = (int) $request['id'];
        $rule   = Rule::fromArray($params);
        $this->rules->save($pageId, $rule);

        return new WP_REST_Response($this->buildResponse($pageId), 200);
    }

    public function canReadRule(WP_REST_Request $request): bool
    {
        $pageId = (int) $request['id'];

        if ($pageId <= 0 || get_post_type($pageId) !== 'page') {
            return false;
        }

        return current_user_can('edit_post', $pageId);
    }

    public function canEditRule(WP_REST_Request $request): bool
    {
        return $this->canReadRule($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponse(int $pageId): array
    {
        $rule      = $this->rules->getForPage($pageId);
        $effective = $this->resolver->resolveForPage($pageId, $this->settings->get());

        $keys              = (array) Config::get('settings', 'meta_keys', []);
        $atKey             = (string) ($keys['last_reviewed_at'] ?? '_content_ownership_last_reviewed_at');
        $byKey             = (string) ($keys['last_reviewed_by'] ?? '_content_ownership_last_reviewed_by');
        $lastReviewedRaw   = (string) get_post_meta($pageId, $atKey, true);
        $lastReviewedById  = (int) get_post_meta($pageId, $byKey, true);
        $lastReviewedAt    = $this->parseDate($lastReviewedRaw);

        $utc            = new DateTimeZone('UTC');
        $post           = get_post($pageId);
        $modifiedRaw    = $post !== null ? (string) $post->post_modified_gmt : '';
        $postModifiedAt = $this->parseDate($modifiedRaw, $utc) ?? new DateTimeImmutable('now', $utc);
        $now            = new DateTimeImmutable('now', $utc);

        $nextReviewAt = $this->calculator->nextReviewAt($effective, $lastReviewedAt, $postModifiedAt);
        $bucket       = $this->calculator->bucket($effective, $lastReviewedAt, $postModifiedAt, $now);

        return [
            'page_id'          => $pageId,
            'rule'             => $rule !== null ? $rule->toArray() : [],
            'effective'        => $effective->toArray(),
            'last_reviewed_at' => $lastReviewedAt !== null ? $lastReviewedAt->format('c') : null,
            'last_reviewed_by' => $lastReviewedById > 0 ? $lastReviewedById : null,
            'next_review_at'   => $nextReviewAt->format('c'),
            'bucket'           => $bucket->value,
        ];
    }

    private function parseDate(string $raw, ?DateTimeZone $assumeTz = null): ?DateTimeImmutable
    {
        if ($raw === '' || $raw === '0000-00-00 00:00:00') {
            return null;
        }
        try {
            return $assumeTz !== null
                ? new DateTimeImmutable($raw, $assumeTz)
                : new DateTimeImmutable($raw);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function idArgs(): array
    {
        return [
            'id' => [
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => static function ($v): bool {
                    return is_numeric($v) && (int) $v > 0;
                },
            ],
        ];
    }
}
