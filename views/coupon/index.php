<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\Search\CouponSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Coupons';
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="coupon-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Перейти к мгазинам', ['/store/index'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Запустить сканирование  купонов', ['/coupon/process'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'id_store',
            'title',
            'text',
            'logo',
            //'period',

          //  ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
