<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\RewardNotFoundException;
use App\Exceptions\ValidationException;
use App\Services\RewardService;

final readonly class AdminRewardController
{
    public function __construct(
        private RewardService $rewards,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('admin/rewards/index', $this->commonData() + [
            'title' => 'Quản lý reward',
            'rewards' => $this->rewards->adminRewards(),
            'flashSuccess' => $this->session->get('success'),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->formResponse('create');
    }

    public function store(Request $request): Response
    {
        $values = $this->input($request);

        try {
            $this->rewards->create(...$this->arguments($values));
        } catch (ValidationException $exception) {
            return $this->formResponse('create', $values, $exception->errors(), 422);
        } catch (DuplicateCatalogException $exception) {
            return $this->formResponse('create', $values, ['code' => $exception->getMessage()], 422);
        }

        $this->session->flash('success', 'Reward đã được tạo với cấu hình từ database.');

        return Response::redirect('/admin/reward');
    }

    public function edit(Request $request): Response
    {
        $id = $this->resourceId($request);
        $reward = $this->rewards->reward($id);

        return $this->formResponse('edit', [
            'code' => (string) $reward['code'],
            'name' => (string) $reward['name'],
            'reward_type' => (string) $reward['reward_type'],
            'points_cost' => (string) $reward['points_cost'],
            'value' => (string) $reward['value'],
            'max_discount' => (string) ($reward['max_discount'] ?? ''),
            'service_id' => (string) ($reward['service_id'] ?? ''),
            'minimum_tier_id' => (string) ($reward['minimum_tier_id'] ?? ''),
            'valid_days_after_redeem' => (string) $reward['valid_days_after_redeem'],
            'vehicle_type_ids' => array_map('strval', $reward['vehicle_type_ids']),
        ], [], 200, $id);
    }

    public function update(Request $request): Response
    {
        $id = $this->resourceId($request);
        $values = $this->input($request);

        try {
            $this->rewards->update($id, ...$this->arguments($values));
        } catch (ValidationException $exception) {
            return $this->formResponse('edit', $values, $exception->errors(), 422, $id);
        } catch (DuplicateCatalogException $exception) {
            return $this->formResponse('edit', $values, ['code' => $exception->getMessage()], 422, $id);
        }

        $this->session->flash(
            'success',
            'Reward đã được cập nhật; redemption cũ vẫn giữ snapshot điểm đã trừ.'
        );

        return Response::redirect('/admin/reward');
    }

    public function deactivate(Request $request): Response
    {
        $this->rewards->setActive($this->resourceId($request), false);
        $this->session->flash('success', 'Reward đã ngừng hoạt động và lịch sử được giữ nguyên.');

        return Response::redirect('/admin/reward');
    }

    public function activate(Request $request): Response
    {
        $this->rewards->setActive($this->resourceId($request), true);
        $this->session->flash('success', 'Reward đã được kích hoạt trở lại.');

        return Response::redirect('/admin/reward');
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors */
    private function formResponse(
        string $mode,
        array $values = [],
        array $errors = [],
        int $status = 200,
        ?int $rewardId = null
    ): Response {
        return Response::html($this->view->render('admin/rewards/form', $this->commonData() + [
            'title' => $mode === 'create' ? 'Thêm reward' : 'Sửa reward',
            'mode' => $mode,
            'rewardId' => $rewardId,
            'options' => $this->rewards->formOptions(),
            'values' => $values + [
                'code' => '', 'name' => '', 'reward_type' => 'fixed_discount',
                'points_cost' => '', 'value' => '', 'service_id' => '',
                'max_discount' => '',
                'minimum_tier_id' => '', 'valid_days_after_redeem' => '30',
                'vehicle_type_ids' => [],
            ],
            'errors' => $errors,
        ]), $status);
    }

    /** @return array<string, mixed> */
    private function input(Request $request): array
    {
        $vehicles = $request->input('vehicle_type_ids', []);

        return [
            'code' => $this->stringInput($request, 'code'),
            'name' => $this->stringInput($request, 'name'),
            'reward_type' => $this->stringInput($request, 'reward_type'),
            'points_cost' => $this->stringInput($request, 'points_cost'),
            'value' => $this->stringInput($request, 'value'),
            'max_discount' => $this->stringInput($request, 'max_discount'),
            'service_id' => $this->stringInput($request, 'service_id'),
            'minimum_tier_id' => $this->stringInput($request, 'minimum_tier_id'),
            'valid_days_after_redeem' => $this->stringInput($request, 'valid_days_after_redeem'),
            'vehicle_type_ids' => is_array($vehicles)
                ? array_values(array_filter($vehicles, 'is_string'))
                : [],
        ];
    }

    /** @param array<string, mixed> $values @return array<int, mixed> */
    private function arguments(array $values): array
    {
        return [
            $values['code'], $values['name'], $values['reward_type'], $values['points_cost'],
            $values['value'], $values['service_id'], $values['minimum_tier_id'],
            $values['valid_days_after_redeem'], $values['vehicle_type_ids'],
            $values['max_discount'],
        ];
    }

    private function resourceId(Request $request): int
    {
        $id = $request->route('id', '');

        if (!is_string($id) || preg_match('/^[1-9][0-9]*$/', $id) !== 1) {
            throw new RewardNotFoundException();
        }

        return (int) $id;
    }

    /** @return array<string, mixed> */
    private function commonData(): array
    {
        return [
            'authUser' => $this->session->get('auth_user'),
            'csrfToken' => $this->tokens->token(),
        ];
    }

    private function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }
}
