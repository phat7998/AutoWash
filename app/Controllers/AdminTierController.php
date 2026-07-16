<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\ValidationException;
use App\Services\TierConfigurationService;

final readonly class AdminTierController
{
    public function __construct(
        private TierConfigurationService $tiers,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('admin/tiers/index', $this->common() +
            $this->tiers->overview() + ['title' => 'Cấu hình hạng và quyền lợi']));
    }

    public function createTier(Request $request): Response
    {
        return $this->tierForm(null);
    }

    public function storeTier(Request $request): Response
    {
        return $this->saveTier(null, $request);
    }

    public function editTier(Request $request): Response
    {
        $id = $this->id($request);
        return $this->tierForm($id, $this->tiers->tier($id));
    }

    public function updateTier(Request $request): Response
    {
        return $this->saveTier($this->id($request), $request);
    }

    public function createPerk(Request $request): Response
    {
        return $this->perkForm(null);
    }

    public function storePerk(Request $request): Response
    {
        return $this->savePerk(null, $request);
    }

    public function editPerk(Request $request): Response
    {
        $id = $this->id($request);
        return $this->perkForm($id, $this->tiers->perk($id));
    }

    public function updatePerk(Request $request): Response
    {
        return $this->savePerk($this->id($request), $request);
    }

    public function toggleTier(Request $request, bool $active): Response
    {
        $this->tiers->setTierActive($this->id($request), $active, $this->adminId());
        $message = $active ? 'Đã kích hoạt hạng.' : 'Đã ngừng hạng và giữ lịch sử.';
        $this->session->flash('success', $message);
        return Response::redirect('/admin/hang-thanh-vien');
    }

    public function togglePerk(Request $request, bool $active): Response
    {
        $this->tiers->setPerkActive($this->id($request), $active, $this->adminId());
        $this->session->flash('success', $active ? 'Đã kích hoạt quyền lợi.' : 'Đã ngừng quyền lợi.');
        return Response::redirect('/admin/hang-thanh-vien');
    }

    private function saveTier(?int $id, Request $request): Response
    {
        $values = $this->inputs($request, [
            'code', 'name', 'rank_order', 'booking_window_days', 'min_monthly_spend',
            'min_monthly_visits', 'point_rate',
        ]);
        try {
            $this->tiers->saveTier($id, $this->adminId(), $values);
        } catch (ValidationException $exception) {
            return $this->tierForm($id, $values, $exception->errors(), 422);
        } catch (DuplicateCatalogException $exception) {
            return $this->tierForm($id, $values, ['code' => $exception->getMessage()], 422);
        }
        $this->session->flash('success', 'Đã lưu cấu hình hạng và ghi audit log.');
        return Response::redirect('/admin/hang-thanh-vien');
    }

    private function savePerk(?int $id, Request $request): Response
    {
        $values = $this->inputs($request, ['tier_id', 'perk_type', 'value', 'service_id']);
        try {
            $this->tiers->savePerk($id, $this->adminId(), $values);
        } catch (ValidationException $exception) {
            return $this->perkForm($id, $values, $exception->errors(), 422);
        }
        $this->session->flash('success', 'Đã lưu quyền lợi hạng và ghi audit log.');
        return Response::redirect('/admin/hang-thanh-vien');
    }

    private function tierForm(?int $id, array $values = [], array $errors = [], int $status = 200): Response
    {
        $defaults = [
            'code' => '', 'name' => '', 'rank_order' => '', 'booking_window_days' => '',
            'min_monthly_spend' => '0', 'min_monthly_visits' => '0', 'point_rate' => '1.00',
        ];
        return Response::html($this->view->render('admin/tiers/tier_form', $this->common() + [
            'title' => $id === null ? 'Thêm hạng' : 'Sửa hạng', 'tierId' => $id,
            'values' => $values + $defaults, 'errors' => $errors,
        ]), $status);
    }

    private function perkForm(?int $id, array $values = [], array $errors = [], int $status = 200): Response
    {
        $defaults = ['tier_id' => '', 'perk_type' => 'percentage_discount', 'value' => '', 'service_id' => ''];
        return Response::html($this->view->render('admin/tiers/perk_form', $this->common() + [
            'title' => $id === null ? 'Thêm quyền lợi' : 'Sửa quyền lợi', 'perkId' => $id,
            'values' => $values + $defaults, 'errors' => $errors, 'options' => $this->tiers->options(),
        ]), $status);
    }

    /** @param list<string> $keys @return array<string, string> */
    private function inputs(Request $request, array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $value = $request->input($key, '');
            $values[$key] = is_string($value) ? $value : '';
        }
        return $values;
    }

    private function id(Request $request): int
    {
        $id = $request->route('id', '');
        if (!is_string($id) || preg_match('/^[1-9][0-9]*$/', $id) !== 1) {
            throw new \App\Exceptions\CatalogResourceNotFoundException();
        }
        return (int) $id;
    }

    private function adminId(): int
    {
        return (int) ($this->session->get('auth_user')['id'] ?? 0);
    }

    /** @return array<string, mixed> */
    private function common(): array
    {
        return [
            'authUser' => $this->session->get('auth_user'), 'csrfToken' => $this->tokens->token(),
            'flashSuccess' => $this->session->get('success'),
        ];
    }
}
