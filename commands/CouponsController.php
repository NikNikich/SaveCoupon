<?php
/**
 * Парсинг чисто по купонам без магазинов
 * Created by PhpStorm.
 * User: Benjamin_King_I
 * Date: 12.08.2019
 * Time: 0:22
 */

namespace app\commands;

use app\models\Coupon;
use yii\helpers\Console;
use yii\console\Controller;
use app\models\Store;
use app\models\Process;
use GuzzleHttp\Client; // подключаем Guzzle
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class CouponsController extends Controller
{
    const URL_SITE_STORES = 'https://www.coupons.com/store-loyalty-card-coupons/';//адрес со списком магазиов
    const URL_SITE = 'https://www.coupons.com';//префикс адреса для страниц с купонами
    const DIFF_DATE = 3;//период в часах, после которого предидущий незаконченный процесс считается устаревшим
    private $coupon_req;//внешняя переменная для хранения данных парсинга купонов

    /**
     * Основная функция для начала парсинга
     */

    public function actionParseCoupon()
    {
       // $this->stdout("Запускаем работу  \n", Console::BG_GREEN);
        $step=0;
        $stores_main=Store::find()
            ->asArray()
            ->all();
        if(!empty($stores_main)){
            $process_options=self::GetProcessId($stores_main);
            self::SaveCoupons($stores_main,$process_options);
            $day_now =  new \DateTime('now');
            $process_save= Process::findOne(['id' =>$process_options['id']]);
            $process_save->date_end=$day_now->format("Y-m-d H:i:s");
            $process_save->save();
        }
    }
    private function getCoupon($url){
       // $this->stdout("поиск по купонам ".$url."  \n", Console::BG_GREEN);
        $client = new Client();
        $res = $client->requestAsync('GET', $url);
        $res->then(
            function (ResponseInterface $res) {
                $body = $res->getBody();
                $document = \phpQuery::newDocumentHTML($body);
                $a_a_e = $document->find('div[class=\'media\']');
                $this->coupon_req=$a_a_e;
            },
            function (RequestException $e) {
                //$this->stdout("не смог подключиться к странице купонов".$url . $e->getMessage() . "\n", Console::BG_RED);
                $this->coupon_req=false;
            }
        );
        $res->wait();
        $store_coupon=$this->coupon_req;
        if($store_coupon){
            $coupons=[];
            foreach ($store_coupon as $store_coupon_one) {
                $coupons_one=[];
                $pg_coupon_pod = pq($store_coupon_one); //pq делает объект phpQuery
                $coupons_one['logo']='http:'.$pg_coupon_pod->find('img')->attr('src');
                $coupons_one['title']=$pg_coupon_pod->find('p[class=\'pod_summary\']')->text()."  ".$pg_coupon_pod->find('p[class=\'pod_brand\']')->text();
                $coupons_one['text']=$pg_coupon_pod->find('p[class=\'pod_description\']')->text();
                $coupons_one['period']=$pg_coupon_pod->find('p[class=\'pod_expiry\']')->text();
                $coupons[]=$coupons_one;
            }
            return $coupons;
        } else{
            return false;
        }
    }
    /**
     * проверяем предидущий процесс и возвращаем id текущего процесса и флаг новый ли он
     */
    private function GetProcessId($stores){
        $processes=Process::find()
            ->orderby(['id'=>SORT_DESC])
            ->asArray()
            ->one();
        if (empty($processes)){
            return $this->ProcessNew();
        } else {
            if (!empty($processes['date_end'])){
                return $this->ProcessNew();
            }
            $day_now =  new \DateTime('now');
            $day_start=  \DateTime::createFromFormat("Y-m-d H:i:s",$processes['date_start']);
            $diff=$day_now->diff($day_start);
            $hours = $diff->h + ($diff->days * 24);
            if($hours>=self::DIFF_DATE){
                return $this->ProcessNew();
            }
            $fl_new=true;
            foreach ($stores as $store) {
                if (($store['id_process_coupon']!=$processes['id'])){
                    $fl_new=false;
                }
            }
            if($fl_new){
                return $this->ProcessNew();
            } else{
                $proc['is_new']=false;
                $proc['id']=$processes['id'];
                return $proc;
            }
        }
    }
    /**
     * Создаём новы процесс
     */
    private function ProcessNew(){
        $day_now =  new \DateTime('now');
        $proc_new= new Process();
        $proc_new->date_start=$day_now->format("Y-m-d H:i:s");
        $proc_new->save();
        $proc['is_new']=true;
        $proc['id']=$proc_new->id;
        return $proc;
    }
    /**
     * Сохраняем найденные купоны и удаляем неактуальные
     */
    private function SaveCoupons($stores,$proc_option){
        //$this->stdout("сохраняем купоны" . "\n", Console::BG_GREEN);
        foreach ($stores as $store) {
           // $this->stdout("купоны магазина ".$store['id'] . "\n", Console::BG_GREEN);
            if((int)$store['id']>0) {
                $step = 0;
                $coupons_main = false;
                while ((!$coupons_main) && ($step < 3)) {
                    $coupons_main = $this->getCoupon($store['href']);
                    $step++;
                }
                if ($coupons_main) {
                    $coupons_id=[];
                    foreach ($coupons_main as $kyy=>$coupon) {
                        $coupons_bd = Coupon::find()
                            ->where(['title' => $coupon['title']])
                            ->andWhere(['text'=>$coupon['text']])
                            ->andWhere(['id_store' => $store['id']])
                            ->asArray()
                            ->one();
                        if (empty($coupons_bd)){
                            $coupon_new= new Coupon();
                            $coupon_new->title=$coupon['title'];
                            $coupon_new->text=$coupon['text'];
                            $coupon_new->period=$coupon['period'];
                            $coupon_new->id_store=$store['id'];
                            $coupon_new->logo=$coupon['logo'];
                            $coupon_new->save();
                            $coupons_id[]=$coupon_new->id;
                        } else{
                            $coupons_id[]=$coupons_bd['id'];
                            $coupon_save= Coupon::findOne(['id' =>$coupons_bd['id']]);
                            $coupon_save->period=$coupon['period'];
                            $coupon_save->save();
                        }
                    }
                    if (count($coupons_id)>0){
                        $coupon_del=Coupon::find()
                            ->where(['id_store' => $store['id']])
                            ->andWhere(['not in', 'id', $coupons_id])
                            ->all();
                        foreach ($coupon_del as $del) {
                            $del->delete();
                        }
                    }
                    $store_save= Store::findOne(['id' =>$store['id']]);
                    $store_save->id_process_coupon=$proc_option['id'];
                    $store_save->save();
                }
            }
        }
    }
}