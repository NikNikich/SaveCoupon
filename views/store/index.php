<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\Search\StoreSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Stores';
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="store-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Перейти к купонам', ['/coupon/index'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Запустить сканирование магазинов и купонов', ['/store/process'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'id_process_store',
            'id_process_coupon',
            'name',
            'href',
            //'status',

           //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
