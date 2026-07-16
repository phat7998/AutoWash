<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\ValidationException;
use App\Services\ServiceCatalogService;

final readonly class CatalogController
{
    public function __construct(
        private ServiceCatalogService $catalog,
        private View $view,
        private Session $session
    ) {
    }

    public function index(Request $request): Response
    {
        $selected = $this->stringInput($request, 'vehicle_type_id');
        $errors = [];

        try {
            $catalog = $this->catalog->customerCatalog($selected);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $catalog = $this->catalog->customerCatalog('');
        }

        return Response::html($this->view->render('customer/catalog/index', [
            'title' => 'Danh mục dịch vụ',
            'authUser' => $this->session->get('auth_user'),
            'vehicleTypes' => $catalog['vehicle_types'],
            'selectedType' => $catalog['selected_type'],
            'services' => $catalog['services'],
            'errors' => $errors,
        ]), $errors === [] ? 200 : 422);
    }

    private function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }
}
