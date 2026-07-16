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
use App\Services\PromotionService;

final readonly class AdminPromotionController
{
    public function __construct(
        private PromotionService $promotions,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('admin/promotions/index', $this->common() + [
            'title' => 'Quản lý promotion', 'promotions' => $this->promotions->adminPromotions(),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function store(Request $request): Response
    {
        return $this->save(null, $request);
    }

    public function edit(Request $request): Response
    {
        $id = $this->id($request);
        $values = $this->promotions->promotion($id);
        $values['start_at'] = str_replace(' ', 'T', substr((string) $values['start_at'], 0, 16));
        $values['end_at'] = str_replace(' ', 'T', substr((string) $values['end_at'], 0, 16));
        return $this->form($id, $values);
    }

    public function update(Request $request): Response
    {
        return $this->save($this->id($request), $request);
    }

    public function toggle(Request $request, bool $active): Response
    {
        $this->promotions->setPromotionActive($this->id($request), $active, $this->adminId());
        $this->session->flash('success', $active ? 'Đã kích hoạt promotion.' : 'Đã ngừng promotion.');
        return Response::redirect('/admin/promotion');
    }

    private function save(?int $id, Request $request): Response
    {
        $values = [];
        foreach (
            [
            'code', 'name', 'description', 'discount_type', 'discount_value', 'max_discount',
            'minimum_order_value', 'start_at', 'end_at', 'usage_limit', 'per_user_limit',
            ] as $key
        ) {
            $value = $request->input($key, '');
            $values[$key] = is_string($value) ? $value : '';
        }
        foreach (['tier_ids', 'service_ids', 'vehicle_type_ids'] as $key) {
            $value = $request->input($key, []);
            $values[$key] = is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
        }
        try {
            $this->promotions->savePromotion($id, $this->adminId(), $values);
        } catch (ValidationException $exception) {
            return $this->form($id, $values, $exception->errors(), 422);
        } catch (DuplicateCatalogException $exception) {
            return $this->form($id, $values, ['code' => $exception->getMessage()], 422);
        }
        $this->session->flash('success', 'Đã lưu promotion và ghi audit log.');
        return Response::redirect('/admin/promotion');
    }

    private function form(?int $id, array $values = [], array $errors = [], int $status = 200): Response
    {
        $defaults = [
            'code' => '', 'name' => '', 'description' => '', 'discount_type' => 'fixed',
            'discount_value' => '', 'max_discount' => '', 'minimum_order_value' => '0',
            'start_at' => '', 'end_at' => '', 'usage_limit' => '', 'per_user_limit' => '',
            'tier_ids' => [], 'service_ids' => [], 'vehicle_type_ids' => [],
        ];
        return Response::html($this->view->render('admin/promotions/form', $this->common() + [
            'title' => $id === null ? 'Thêm promotion' : 'Sửa promotion', 'promotionId' => $id,
            'values' => $values + $defaults, 'errors' => $errors,
            'options' => $this->promotions->formOptions(),
        ]), $status);
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
