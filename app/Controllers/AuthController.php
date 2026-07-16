<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\CsrfTokenManager;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Services\AuthService;

final readonly class AuthController
{
    public function __construct(
        private AuthService $auth,
        private View $view,
        private Session $session,
        private CsrfTokenManager $tokens
    ) {
    }

    public function showRegister(Request $request): Response
    {
        return $this->registrationResponse();
    }

    public function register(Request $request): Response
    {
        $phone = $this->stringInput($request, 'phone');
        $fullName = $this->stringInput($request, 'full_name');
        $password = $this->stringInput($request, 'password');

        try {
            $this->auth->register($phone, $fullName, $password);
        } catch (ValidationException $exception) {
            return $this->registrationResponse($exception->errors(), $phone, $fullName, 422);
        }

        $this->session->flash('success', 'Đăng ký thành công. Bạn có thể đăng nhập ngay.');

        return Response::redirect('/dang-nhap');
    }

    public function showLogin(Request $request): Response
    {
        return $this->loginResponse();
    }

    public function login(Request $request): Response
    {
        $phone = $this->stringInput($request, 'phone');
        $password = $this->stringInput($request, 'password');

        try {
            $identity = $this->auth->login($phone, $password);
        } catch (AuthenticationException $exception) {
            return $this->loginResponse($exception->getMessage(), $phone, 422);
        }

        $this->session->flash('success', 'Đăng nhập thành công.');

        return Response::redirect($identity['role'] === 'admin' ? '/admin' : '/tai-khoan');
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();

        return Response::redirect('/dang-nhap');
    }

    /** @param array<string, string> $errors */
    private function registrationResponse(
        array $errors = [],
        string $phone = '',
        string $fullName = '',
        int $status = 200
    ): Response {
        return Response::html($this->view->render('auth/register', [
            'title' => 'Đăng ký tài khoản',
            'csrfToken' => $this->tokens->token(),
            'errors' => $errors,
            'phone' => $phone,
            'fullName' => $fullName,
            'authUser' => $this->session->get('auth_user'),
        ]), $status);
    }

    private function loginResponse(string $error = '', string $phone = '', int $status = 200): Response
    {
        return Response::html($this->view->render('auth/login', [
            'title' => 'Đăng nhập',
            'csrfToken' => $this->tokens->token(),
            'error' => $error !== '' ? $error : $this->session->get('error'),
            'flashSuccess' => $this->session->get('success'),
            'phone' => $phone,
            'authUser' => $this->session->get('auth_user'),
        ]), $status);
    }

    private function stringInput(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? $value : '';
    }
}
