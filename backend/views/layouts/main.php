<?php

/**
 * @var $this View
 * @var $content string
 * @var User $user
 */

use backend\assets\AppAsset;
use common\models\User;
use hail812\adminlte3\assets\FontAwesomeAsset;
use hail812\adminlte3\assets\PluginAsset;
use yii\bootstrap4\Modal;
use yii\helpers\Html;
use yii\web\View;

FontAwesomeAsset::register($this);
PluginAsset::register($this)->add([
        'jquery-ui',
        'sweetalert2',
        'toastr',
        'fontawesome',
        'icheck-bootstrap',
        'chart',
        'pace-progress',
        'bs-custom-file-input',
        'summernote'
]);
AppAsset::register($this);
$this->registerCssFile('https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback');
$user = Yii::$app->user->identity;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/images/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <link rel="icon" href="/images/favicon.ico" type="image/x-icon">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="layout-top-nav">
<?php $this->beginBody() ?>
<div class="wrapper">
    <!-- Navbar -->
    <?= $this->render('navbar', ['user' => $user]) ?>
    <?php
    if (Yii::$app->session->has('original_admin_id')) {
        echo '<div class="alert alert-warning text-center" style="margin-bottom: 0; padding: 0;">';
        echo 'Bạn đang đăng nhập với tư cách <b>' . Yii::$app->user->identity->name . " - ".Yii::$app->user->identity->full_name.'</b>. ';
        echo Html::a(
                'Quay lại tài khoản Admin',
                ['/user/revert'],
                ['class' => 'btn btn-danger btn-xs', 'data-method' => 'post']
        );
        echo '</div>';
    }
    ?>
    <!-- /.navbar -->
    <!-- Content Wrapper. Contains page content -->
    <?= $this->render('content', ['content' => $content, 'user' => $user]) ?>
    <!-- /.content-wrapper -->
    <!-- Main Footer -->
    <?= $this->render('footer') ?>

</div>
<?php
Modal::begin([
        'id' => 'modal',
        'title' => 'Modal',
        'headerOptions' => [
                'class' => 'bg-violet'
        ],
        'dialogOptions' => [
                'class' => 'modal-dialog-scrollable'
        ],
        'options' => [
                'tabindex' => false
        ],
]); ?>
<div id='modal-body'>
</div>
<?php
Modal::end();
?>
<?php
$this->registerJs(<<< EOT_JS_CODE
    $(document).ready(function () {
    
        $('input[type="checkbox"]').attr('title','Chọn để xóa');
        $('.select-on-check-all').attr('title','Chọn để xóa tất cả');

       $("body").on("click", ".open-modal", function(event){
            event.preventDefault();
            let url = $(this).attr('data-url') || $(this).attr('url');
            let title = $(this).attr('data-title') ||  $(this).attr('title');
            let size = $(this).attr('data-size') ||  $(this).attr('size');
            
            if (!url) {
                toastr.error('Error URL');
                return;
            }
        
            $('.pjax-spinner').show();
            $("#modal-body").html('');
            $(".modal-dialog").removeClass('modal-sm modal-lg modal-xl');
        
            $.ajax({
                url: url,
                type: 'GET',
                data: {},
                success: function (result) {
                    $('.pjax-spinner').hide();
                    $("#modal-label").html(title);
                    $(".modal-dialog").addClass(size);
                    $("#modal-body").html(result);
                    $("#modal").modal("show"); 
                },
                error: function (xhr, jqXHR, errMsg) {
                    $('.pjax-spinner').hide();
                    $("#modal").modal("hide"); 
                    let errorMessage = 'Lỗi không xác định.';
                    if (xhr.status === 403) {
                        errorMessage = 'Lỗi 403: Bạn không có quyền thực hiện hành động này.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Lỗi 404: Không tìm thấy tài nguyên.';
                    } 
                    toastr.error(errorMessage);
                }
            });
        });
        
        $("body").on("click", ".fa-clone", function() {
            const valueToCopy = $(this).data('value');
            if (valueToCopy) {
                navigator.clipboard.writeText(valueToCopy).then(() => {
                    toastr.success('Đã sao chép: ' + valueToCopy);
                }).catch(err => {
                    toastr.error('Lỗi sao chép:', err);
                });
            }
        });
        
        $('body').on('click', '.select-all-mobile', function() {
            var targetGrid = $(this).data('target');
            $(targetGrid).find("input[name='selection[]']").prop('checked', true);
        });
        $('body').on('click', '.deselect-all-mobile', function() {
            var targetGrid = $(this).data('target');
            $(targetGrid).find("input[name='selection[]']").prop('checked', false);
        });
        
        $(document).on('pjax:send', function() {
            $('.pjax-spinner').show();
        });
        $(document).on('pjax:complete', function() {
            $('.pjax-spinner').hide();
        });
        
        $(document).on('click', '#btn-check-all-mobile', function() {
        var selectAllCheckbox = $('.grid-view .select-on-check-all');
        if(selectAllCheckbox.length > 0) {
            selectAllCheckbox.trigger('click');
        } else {
            var allInputs = $('input[name="selection[]"]');
            var isChecked = !$(this).data('checked');
            allInputs.prop('checked', isChecked);
            $(this).data('checked', isChecked); 
        }
    });
        
    });
EOT_JS_CODE
);
?>
<?php $this->endBody() ?>
<div class="spinner-border text-primary pjax-spinner" role="status" style="display: none;">
    <span class="sr-only">Loading...</span>
</div>
</body>
</html>
<?php $this->endPage() ?>

