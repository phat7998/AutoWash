<?php
/* @var $content string */

use yii\bootstrap4\Breadcrumbs;
use yii\helpers\Html;
use yii\helpers\Inflector;
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h5 class="m-0 text-white">
                        <?php
                        if (!is_null($this->title)) {
                            echo Html::encode($this->title);
                        } else {
                            echo Inflector::camelize($this->context->id);
                        }
                        ?>
                    </h5>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <?php
                    try {
                        echo Breadcrumbs::widget([
                            'links' => $this->params['breadcrumbs'] ?? [],
                            'options' => [
                                'class' => 'breadcrumb float-sm-right'
                            ]
                        ]);
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
                    }
                    ?>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <div class="content">
        <?= $content ?>
    </div>
</div>