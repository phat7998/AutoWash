<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Html;
use RuntimeException;
use Throwable;

final class View
{
    public function __construct(private readonly string $viewPath)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = $this->renderFile($template, $data);

        if ($layout === null) {
            return $content;
        }

        return $this->renderFile($layout, $data + ['content' => $content]);
    }

    public static function escape(mixed $value): string
    {
        return Html::escape($value);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $template, array $data): string
    {
        $file = $this->viewPath . '/' . ltrim($template, '/') . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('Không tìm thấy view được yêu cầu.');
        }

        $e = Html::escape(...);
        extract($data, EXTR_SKIP);
        ob_start();

        try {
            require $file;

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
