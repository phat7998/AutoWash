<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\ValidationException;
use App\Services\AdminReportService;

final readonly class AdminReportController
{
    public function __construct(
        private AdminReportService $reports,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $status = 200;

        try {
            $data = $this->reports->report($fromDate, $toDate);
            $errors = [];
        } catch (ValidationException $exception) {
            $data = $this->emptyMetrics();
            $errors = $exception->errors();
            $status = 422;
        }

        return Response::html($this->view->render('admin/reports/index', $data + [
            'title' => 'Báo cáo vận hành',
            'authUser' => $this->session->get('auth_user'),
            'csrfToken' => $this->tokens->token(),
            'currentPath' => $request->path(),
            'from_date' => is_string($fromDate) ? $fromDate : ($data['from_date'] ?? ''),
            'to_date' => is_string($toDate) ? $toDate : ($data['to_date'] ?? ''),
            'errors' => $errors,
        ]), $status);
    }

    /** @return array<string, mixed> */
    private function emptyMetrics(): array
    {
        return [
            'revenue' => [],
            'booking_status' => [],
            'vehicle_types' => [],
            'services' => [],
            'tiers' => [],
            'points' => [],
            'usage' => [],
        ];
    }
}
