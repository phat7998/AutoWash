<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var string|null $flashSuccess */
?>
<section class="landing-hero" aria-labelledby="landing-title">
    <div class="landing-hero-copy">
        <p class="eyebrow">Đặt lịch chăm sóc xe trực tuyến</p>
        <h1 id="landing-title">Chăm sóc phương tiện,<br><span>chủ động từng khung giờ</span></h1>
        <p class="lead">Đặt lịch rửa xe trước, tránh chờ đợi và tích điểm thành viên sau mỗi lần sử dụng dịch vụ.</p>
        <div class="hero-actions">
            <a class="button button-primary button-large" href="/dat-lich">Đặt lịch ngay</a>
            <a class="button button-light button-large" href="/dich-vu">Xem dịch vụ</a>
        </div>
        <ul class="trust-list" aria-label="Tiện ích chính">
            <li>Chọn giờ phù hợp</li><li>Giá hiển thị rõ ràng</li><li>Tích điểm thành viên</li>
        </ul>
    </div>
    <div class="hero-visual" aria-hidden="true">
        <svg viewBox="0 0 520 330" focusable="false">
            <path class="hero-water" d="M36 258c72-45 134-31 203 2 89 42 170 33 245-14"/>
            <path class="hero-car" d="M95 210h330l-34-91c-7-20-24-33-45-33H190c-20 0-38 12-46 30l-49 94Z"/>
            <path class="hero-window" d="M166 117h173c13 0 24 7 29 19l17 45H129l27-50c3-8 7-14 10-14Z"/>
            <circle cx="154" cy="220" r="32"/><circle cx="369" cy="220" r="32"/>
            <path class="hero-detail" d="M84 210h352v30H84zM124 181h260M258 117v64"/>
            <path class="hero-spark" d="M443 63v38M424 82h38M70 73v28M56 87h28"/>
        </svg>
        <div class="hero-appointment"><span>Lịch hẹn của bạn</span><strong>Chủ động · Dễ theo dõi</strong></div>
    </div>
</section>

<?php if (is_string($flashSuccess ?? null) && $flashSuccess !== ''): ?>
    <div class="notification notification-success" role="status"><strong>Đã hoàn tất</strong><span><?= $e($flashSuccess) ?></span></div>
<?php endif; ?>

<section class="landing-section" aria-labelledby="services-title">
    <div class="section-heading centered-heading"><p class="eyebrow dark-eyebrow">Gói dịch vụ</p><h2 id="services-title">Chọn cách chăm sóc phù hợp với xe của bạn</h2><p>Danh mục gồm gói rửa chính và dịch vụ bổ sung; giá được hiển thị theo từng loại phương tiện.</p></div>
    <div class="feature-card-grid">
        <article class="feature-card"><span class="feature-icon" aria-hidden="true">01</span><h3>Gói rửa tiêu chuẩn</h3><p>Giải pháp chăm sóc định kỳ, phù hợp nhu cầu làm sạch thường xuyên.</p><a href="/dich-vu">Xem giá theo loại xe <span aria-hidden="true">→</span></a></article>
        <article class="feature-card feature-card-highlight"><span class="feature-icon" aria-hidden="true">02</span><h3>Gói rửa cao cấp</h3><p>Lựa chọn chăm sóc kỹ hơn với thời lượng được công bố rõ trước khi đặt.</p><a href="/dich-vu">Khám phá gói dịch vụ <span aria-hidden="true">→</span></a></article>
        <article class="feature-card"><span class="feature-icon" aria-hidden="true">+</span><h3>Dịch vụ bổ sung</h3><p>Chọn thêm dịch vụ phù hợp sau khi đã chọn một gói rửa chính.</p><a href="/dat-lich">Bắt đầu đặt lịch <span aria-hidden="true">→</span></a></article>
    </div>
</section>

<section class="landing-section vehicle-section" aria-labelledby="vehicles-title">
    <div class="section-heading"><p class="eyebrow dark-eyebrow">Phương tiện hỗ trợ</p><h2 id="vehicles-title">Một trải nghiệm đặt lịch cho bốn nhóm phương tiện</h2></div>
    <div class="vehicle-type-grid">
        <article><span aria-hidden="true">01</span><h3>Xe máy</h3><p>Gọn nhẹ, linh hoạt</p></article>
        <article><span aria-hidden="true">02</span><h3>Ô tô con</h3><p>Chăm sóc tiện lợi</p></article>
        <article><span aria-hidden="true">03</span><h3>Xe tải</h3><p>Khung giờ phù hợp</p></article>
        <article><span aria-hidden="true">04</span><h3>Xe khách</h3><p>Chủ động sức chứa</p></article>
    </div>
</section>

<section class="landing-section process-section" aria-labelledby="process-title">
    <div class="section-heading centered-heading"><p class="eyebrow dark-eyebrow">Cách hoạt động</p><h2 id="process-title">Đặt lịch chỉ với ba bước rõ ràng</h2></div>
    <ol class="process-grid">
        <li><span>1</span><div><h3>Chọn phương tiện và dịch vụ</h3><p>Thêm xe, chọn một gói rửa chính và dịch vụ bổ sung nếu cần.</p></div></li>
        <li><span>2</span><div><h3>Chọn khung giờ</h3><p>Xem tình trạng còn chỗ và chọn thời gian phù hợp với hạng thành viên.</p></div></li>
        <li><span>3</span><div><h3>Theo dõi lịch hẹn</h3><p>Kiểm tra trạng thái, lịch sử sử dụng và điểm thưởng trong tài khoản.</p></div></li>
    </ol>
</section>

<section class="landing-section loyalty-banner" aria-labelledby="loyalty-title">
    <div><p class="eyebrow">Thành viên AutoWash Pro</p><h2 id="loyalty-title">Mỗi lần chăm sóc xe là một lần tích lũy quyền lợi</h2><p>Điểm, hạng thành viên, thời hạn điểm và quà tặng đều được theo dõi minh bạch trong tài khoản.</p><a class="button button-light" href="/dang-ky">Tạo tài khoản</a></div>
    <ul><li><strong>Điểm thưởng dễ theo dõi</strong><span>Xem số dư, lịch sử và điểm sắp hết hạn.</span></li><li><strong>Quà tặng phù hợp</strong><span>Đổi quà khi đủ điều kiện và sử dụng trong lịch đặt.</span></li><li><strong>Quyền đặt trước theo hạng</strong><span>Chủ động hơn khi hạng thành viên tăng.</span></li></ul>
</section>

<section class="landing-section" aria-labelledby="reasons-title">
    <div class="section-heading centered-heading"><p class="eyebrow dark-eyebrow">Vì sao chọn chúng tôi</p><h2 id="reasons-title">Trải nghiệm rõ ràng từ lúc chọn giờ đến khi hoàn thành</h2></div>
    <div class="reason-grid"><article><h3>Chủ động thời gian</h3><p>Xem và chọn khung giờ trước khi đến.</p></article><article><h3>Thông tin minh bạch</h3><p>Giá và thời lượng theo đúng loại phương tiện.</p></article><article><h3>Quản lý tập trung</h3><p>Lịch hẹn, phương tiện và quyền lợi trong một tài khoản.</p></article><article><h3>An toàn và riêng tư</h3><p>Chỉ bạn mới xem được dữ liệu phương tiện và lịch đặt của mình.</p></article></div>
</section>

<section class="landing-section faq-section" aria-labelledby="faq-title">
    <div class="section-heading"><p class="eyebrow dark-eyebrow">Câu hỏi thường gặp</p><h2 id="faq-title">Thông tin trước khi đặt lịch</h2></div>
    <div class="faq-list">
        <details><summary>Tôi có cần tài khoản để đặt lịch không?</summary><p>Có. Tài khoản giúp hệ thống xác nhận phương tiện, lưu lịch sử và ghi nhận điểm thưởng đúng cho bạn.</p></details>
        <details><summary>Tôi có thể chọn nhiều gói rửa cùng lúc không?</summary><p>Mỗi lịch chọn một gói rửa chính. Bạn có thể chọn thêm các dịch vụ bổ sung đang hỗ trợ phương tiện.</p></details>
        <details><summary>Làm sao biết khung giờ còn chỗ?</summary><p>Trang khung giờ và form đặt lịch hiển thị tình trạng khả dụng để bạn lựa chọn.</p></details>
        <details><summary>Tôi có thể hủy lịch đã đặt không?</summary><p>Bạn có thể hủy trong thời hạn cho phép; trang chi tiết lịch sẽ hiển thị hành động khi còn hợp lệ.</p></details>
    </div>
</section>

<section class="landing-cta" aria-labelledby="final-cta-title"><div><h2 id="final-cta-title">Sẵn sàng chăm sóc phương tiện theo lịch của bạn?</h2><p>Tạo tài khoản, thêm phương tiện và chọn khung giờ phù hợp.</p></div><a class="button button-primary button-large" href="/dat-lich">Đặt lịch ngay</a></section>
