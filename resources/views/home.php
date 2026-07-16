<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var string $csrfToken */
/** @var mixed $flashSuccess */
?>
<section class="hero" aria-labelledby="page-title">
    <div>
        <p class="eyebrow">Xác thực và bảo mật nền tảng</p>
        <h1 id="page-title">Nền tảng AutoWash Pro đã sẵn sàng</h1>
        <p class="lead">
            Đăng ký, đăng nhập, session, CSRF và phân quyền customer/admin đang hoạt động
            trên nền Front Controller và xử lý lỗi an toàn.
        </p>
    </div>
    <span class="status-badge"><span aria-hidden="true">●</span> Hệ thống hoạt động</span>
</section>

<?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="notification notification-success" role="status">
        <strong>Đã xử lý thành công</strong>
        <span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>

<section class="card-grid" aria-label="Thành phần nền tảng">
    <article class="card">
        <h2>Điều hướng rõ ràng</h2>
        <p>URL sạch, phân biệt đúng trang không tồn tại và phương thức không được hỗ trợ.</p>
    </article>
    <article class="card">
        <h2>Bảo vệ biểu mẫu</h2>
        <p>Mọi yêu cầu thay đổi dữ liệu đều phải có token CSRF hợp lệ từ phiên hiện tại.</p>
    </article>
    <article class="card">
        <h2>Lỗi an toàn</h2>
        <p>Người dùng nhận thông báo tiếng Việt; chi tiết kỹ thuật chỉ được ghi vào log nội bộ.</p>
    </article>
</section>

<section class="card prg-demo" aria-labelledby="prg-title">
    <div>
        <h2 id="prg-title">Kiểm tra Post/Redirect/Get</h2>
        <p>Gửi yêu cầu để kiểm tra CSRF, flash message và redirect mà không gửi lại form khi tải lại.</p>
    </div>
    <form method="post" action="/thong-bao-mau">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
        <button class="button button-primary" type="submit">Gửi yêu cầu an toàn</button>
    </form>
</section>
