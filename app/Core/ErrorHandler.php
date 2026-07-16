<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use ErrorException;
use Throwable;

final class ErrorHandler
{
    public function __construct(
        private readonly View $view,
        private readonly Logger $logger,
        private readonly bool $debug
    ) {
    }

    public function register(): void
    {
        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        );
    }

    public function handle(Throwable $exception, string $requestId): Response
    {
        $statusCode = $exception instanceof HttpException ? $exception->statusCode() : 500;
        $headers = $exception instanceof HttpException ? $exception->headers() : [];
        $publicMessage = $exception instanceof HttpException
            ? $exception->getMessage()
            : 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';

        try {
            $this->logger->error('Yêu cầu HTTP thất bại.', [
                'request_id' => $requestId,
                'status' => $statusCode,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            // Không để lỗi hạ tầng log làm lộ chi tiết hoặc thay thế HTTP response an toàn.
        }

        $template = match ($statusCode) {
            403 => 'errors/403',
            404 => 'errors/404',
            405 => 'errors/405',
            419 => 'errors/419',
            default => 'errors/500',
        };

        $body = $this->view->render($template, [
            'title' => $this->titleFor($statusCode),
            'message' => $publicMessage,
            'requestId' => $requestId,
            'debugMessage' => $this->debug && $statusCode === 500 ? $exception->getMessage() : null,
        ]);

        return Response::html($body, $statusCode, $headers);
    }

    private function titleFor(int $statusCode): string
    {
        return match ($statusCode) {
            403 => 'Bạn không có quyền truy cập',
            404 => 'Không tìm thấy trang',
            405 => 'Phương thức không được hỗ trợ',
            419 => 'Phiên biểu mẫu đã hết hạn',
            default => 'Hệ thống đang gặp sự cố',
        };
    }
}
