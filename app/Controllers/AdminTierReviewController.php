<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\TierReviewService;

final readonly class AdminTierReviewController
{
    public function __construct(
        private TierReviewService $reviews,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function index(Request $request): Response
    {
        $results = $this->reviews->adminResults();

        return Response::html($this->view->render('admin/tier_reviews/index', [
            'title' => 'Kết quả xét hạng hàng tháng',
            'authUser' => $this->session->get('auth_user'),
            'csrfToken' => $this->tokens->token(),
            'runs' => $results['runs'],
            'histories' => $results['histories'],
        ]));
    }
}
