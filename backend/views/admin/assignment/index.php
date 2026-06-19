<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel mdm\admin\models\searchs\Assignment */ // Model search
/* @var $usernameField string */
/* @var $extraColumns string[] */

$this->title = Yii::t('rbac-admin', 'Phân quyền người dùng');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="assignment-index">
    <?php Pjax::begin(); ?>
    <?=
    GridView::widget([
            'dataProvider' => $dataProvider,
            'id' => 'grid1Data',
            'tableOptions' => ['class' => 'table table-striped table-bordered responsive-table'],
            'emptyText' => Yii::t('app', 'No Data'),
            'columns' => [
                    [
                            'class' => 'yii\grid\SerialColumn'
                    ],
                    'username',
                    'full_name',
                    'email',
                    [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'header' => Yii::t('app', 'Gán quyền')
                    ]
            ],
            'summaryOptions' => ['class' => 'summary mb-2'],
            'pager' => [
                    'class' => 'yii\bootstrap4\LinkPager',
            ]
    ]);
    ?>
    <?php Pjax::end(); ?>

</div>