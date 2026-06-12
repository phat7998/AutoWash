<?php

use yii\helpers\Html;
use yii\grid\GridView;
use mdm\admin\components\Helper;

/* @var $this yii\web\View */
/* @var $searchModel mdm\admin\models\searchs\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('rbac-admin', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">
    <?=
    GridView::widget([
            'dataProvider' => $dataProvider,
            'id' => 'grid1Data',
            'tableOptions' => ['class' => 'table table-striped table-bordered responsive-table'],
            'emptyText' => Yii::t('app', 'No Data'),
            'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'username',
                    'email:email',
                    'name',
                    'full_name',
            ],
            'summaryOptions' => ['class' => 'summary mb-2'],
            'pager' => [
                    'class' => 'yii\bootstrap4\LinkPager',
            ]
    ]);
    ?>
</div>
