<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\CatalogResourceNotFoundException;
use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\ValidationException;
use App\Services\ServiceCatalogService;

final readonly class AdminServiceController
{
    public function __construct(
        private ServiceCatalogService $catalog,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('admin/services/index', $this->commonData() + [
            'title' => 'Quản lý dịch vụ',
            'services' => $this->catalog->adminServices(),
            'flashSuccess' => $this->session->get('success'),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->formResponse('create');
    }

    public function store(Request $request): Response
    {
        $values = $this->inputValues($request);

        try {
            $this->catalog->create(
                $values['code'],
                $values['name'],
                $values['description'],
                $values['prices'],
                $this->adminId()
            );
        } catch (ValidationException $exception) {
            return $this->formResponse('create', $values, $exception->errors(), 422);
        } catch (DuplicateCatalogException $exception) {
            return $this->formResponse('create', $values, ['code' => $exception->getMessage()], 422);
        }

        $this->session->flash('success', 'Đã tạo dịch vụ và cấu hình theo loại phương tiện.');

        return Response::redirect('/admin/dich-vu');
    }

    public function edit(Request $request): Response
    {
        $serviceId = $this->resourceId($request);
        $service = $this->findService($serviceId);

        return $this->formResponse('edit', $this->valuesFromService($service), [], 200, $serviceId);
    }

    public function update(Request $request): Response
    {
        $serviceId = $this->resourceId($request);
        $this->findService($serviceId);
        $values = $this->inputValues($request);

        try {
            $this->catalog->update(
                $serviceId,
                $values['code'],
                $values['name'],
                $values['description'],
                $values['prices'],
                $this->adminId()
            );
        } catch (ValidationException $exception) {
            return $this->formResponse('edit', $values, $exception->errors(), 422, $serviceId);
        } catch (DuplicateCatalogException $exception) {
            return $this->formResponse('edit', $values, ['code' => $exception->getMessage()], 422, $serviceId);
        }

        $this->session->flash('success', 'Đã cập nhật dịch vụ và cấu hình giá.');

        return Response::redirect('/admin/dich-vu');
    }

    public function deactivate(Request $request): Response
    {
        $serviceId = $this->resourceId($request);
        $this->findService($serviceId);
        $this->catalog->deactivate($serviceId, $this->adminId());
        $this->session->flash(
            'success',
            'Dịch vụ đã được ngừng hoạt động và lịch sử vẫn được giữ nguyên.'
        );

        return Response::redirect('/admin/dich-vu');
    }

    public function activate(Request $request): Response
    {
        $serviceId = $this->resourceId($request);
        $this->findService($serviceId);
        $this->catalog->activate($serviceId, $this->adminId());
        $this->session->flash('success', 'Dịch vụ đã được kích hoạt trở lại.');

        return Response::redirect('/admin/dich-vu');
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private function formResponse(
        string $mode,
        array $values = [],
        array $errors = [],
        int $status = 200,
        ?int $serviceId = null
    ): Response {
        $defaults = ['code' => '', 'name' => '', 'description' => '', 'prices' => []];

        return Response::html($this->view->render('admin/services/form', $this->commonData() + [
            'title' => $mode === 'create' ? 'Thêm dịch vụ' : 'Sửa dịch vụ',
            'vehicleTypes' => $this->catalog->vehicleTypes(),
            'values' => $values + $defaults,
            'errors' => $errors,
            'mode' => $mode,
            'serviceId' => $serviceId,
        ]), $status);
    }

    /** @return array<string, mixed> */
    private function inputValues(Request $request): array
    {
        $prices = $request->input('prices', []);

        return [
            'code' => $this->stringInput($request, 'code'),
            'name' => $this->stringInput($request, 'name'),
            'description' => $this->stringInput($request, 'description'),
            'prices' => is_array($prices) ? $this->stringPriceInputs($prices) : [],
        ];
    }

    /**
     * @param array<array-key, mixed> $prices
     * @return array<string, array<string, string>>
     */
    private function stringPriceInputs(array $prices): array
    {
        $result = [];

        foreach ($prices as $typeId => $row) {
            if ((!is_string($typeId) && !is_int($typeId)) || !is_array($row)) {
                continue;
            }

            $result[(string) $typeId] = [];

            foreach (['price', 'duration_minutes', 'capacity_units_override', 'is_supported', 'is_active'] as $field) {
                $result[(string) $typeId][$field] = is_string($row[$field] ?? null) ? $row[$field] : '';
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function valuesFromService(array $service): array
    {
        $prices = [];

        foreach ($service['prices'] as $typeId => $price) {
            $prices[(string) $typeId] = [
                'price' => (string) ($price['price'] ?? ''),
                'duration_minutes' => (string) ($price['duration_minutes'] ?? ''),
                'capacity_units_override' => (string) ($price['capacity_units_override'] ?? ''),
                'is_supported' => (bool) $price['is_supported'] ? '1' : '',
                'is_active' => (bool) $price['is_active'] ? '1' : '',
            ];
        }

        return [
            'code' => (string) $service['code'],
            'name' => (string) $service['name'],
            'description' => (string) ($service['description'] ?? ''),
            'prices' => $prices,
        ];
    }

    /** @return array<string, mixed> */
    private function findService(int $serviceId): array
    {
        try {
            return $this->catalog->service($serviceId);
        } catch (ValidationException) {
            throw new CatalogResourceNotFoundException('Không tìm thấy dịch vụ được yêu cầu.');
        }
    }

    private function resourceId(Request $request): int
    {
        $value = $request->route('id', '');

        if (!is_string($value) || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new CatalogResourceNotFoundException('Không tìm thấy dịch vụ được yêu cầu.');
        }

        return (int) $value;
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

    private function adminId(): int
    {
        return (int) ($this->session->get('auth_user')['id'] ?? 0);
    }
}
