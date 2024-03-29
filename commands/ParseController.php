<?php
/**
 * Парсер магазинов и купонов
 * Оставил закоментировано то что считаю можно раскоментировать при запуске из консоли для информативности
 * Created by PhpStorm.
 * User: Benjamin_King_I
 * Date: 10.08.2019
 * Time: 17:48
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


class ParseController extends Controller
{
    const URL_SITE_STORES = 'https://www.coupons.com/store-loyalty-card-coupons/';//адрес со списком магазиов
    const URL_SITE = 'https://www.coupons.com';//префикс адреса для страниц с купонами
    const DIFF_DATE = 3;//период в часах, после которого предидущий незаконченный процесс считается устаревшим
    private $store_req;//внешняя переменная для хранения данных парсинга магазинов
    private $coupon_req;//внешняя переменная для хранения данных парсинга купонов

    /**
     * Основная функция для начала парсинга
     */

    public function actionParseSite()
    {
        //   $this->stdout("Запускаем работу  \n", Console::BG_GREEN);
        $step=0;
        $stores_main=false;
        while((!$stores_main)&&($step<3)){
            $stores_main=$this->getStores();
            $step++;
        }
        if($stores_main){
            $process_options=self::GetProcessId($stores_main);
            $stores_new=self::SaveStores($stores_main,$process_options);
            self::SaveCoupons($stores_new,$process_options);
            $day_now =  new \DateTime('now');
            $process_save= Process::findOne(['id' =>$process_options['id']]);
            $process_save->date_end=$day_now->format("Y-m-d H:i:s");
            $process_save->save();
        }
    }
    /**
     * Поиск по магазинам
     */
    private function getStores(){
        //  $this->stdout("Ишем магазины  \n", Console::BG_GREEN);
        $client = new Client();
        $res = $client->requestAsync('GET', self::URL_SITE_STORES);
        $res->then(
            function (ResponseInterface $res) {
                $body = $res->getBody();
                $document = \phpQuery::newDocumentHTML($body);
                $a_a_e = $document->find('a[class=\'store-pod\']');
                $this->store_req=$a_a_e;
            },
            function (RequestException $e) {
                //     $this->stdout("Не подключился к странице магазинов " . $e->getMessage() . "\n", Console::BG_RED);
                $this->store_req=false;
            }
        );
        $res->wait();
        $store_pod=$this->store_req;
        if($store_pod){
            $stores=[];
            foreach ($store_pod as $store_pod_one) {
                $stores_one=[];
                $pg_store_pod = pq($store_pod_one); //pq делает объект phpQuery
                $stores_one['logo']='http:'.$pg_store_pod->find('img')->attr('src');
                $stores_one['href'] = self::URL_SITE.$pg_store_pod->attr('href');
                $div = $pg_store_pod->find('.store-browse');
                $stores_one['name']=$div->find('div:first')->html();
                $stores[]=$stores_one;
            }
            return $stores;
        } else{
            return false;
        }
    }
    private function getCoupon($url){
        //  $this->stdout("поиск по купонам ".$url."  \n", Console::BG_GREEN);
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
                // $this->stdout("не смог подключиться к странице купонов".$url . $e->getMessage() . "\n", Console::BG_RED);
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
            $stores_bd=Store::find()
                ->asArray()
                ->all();
            $fl_new=true;
            foreach ($stores as $store) {
                $key=array_search($store['name'], array_column( $stores_bd, 'name'));
                if($key){
                    if (($stores_bd[$key]['id_process_coupon']!=$processes['id'])){
                        $fl_new=false;
                    }
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
     * Сохраняем спарсенные магазины в БД
     */
    private function SaveStores($stores,$proc_option){
        foreach ($stores as $key_store=>$store) {
            $stores_bd=Store::find()
                ->where(['name'=>$store['name']])
                ->asArray()
                ->one();
            if (empty($stores_bd)){
                $store_new= new Store();
                $store_new->name=$store['name'];
                $store_new->href=$store['href'];
                $store_new->id_process_store=$proc_option['id'];
                $store_new->save();
                $stores[$key_store]['id_store']=$store_new->id;
            } else{
                if($proc_option['is_new']){
                    $stores[$key_store]['id_store']=$stores_bd['id'];
                } else{
                    if($stores_bd['id_process_coupon']!=$proc_option['id']){
                        $stores[$key_store]['id_store']=$stores_bd['id'];
                    } else{
                        unset($stores[$key_store]);
                    }
                }
            }
        }
        return $stores;
    }
    /**
     * Сохраняем найденные купоны и удаляем неактуальные
     */
    private function SaveCoupons($stores,$proc_option){
        //  $this->stdout("сохраняем купоны" . "\n", Console::BG_GREEN);
        foreach ($stores as $store) {
            //     $this->stdout("купоны магазина ".$store['id_store'] . "\n", Console::BG_GREEN);
            if((int)$store['id_store']>0) {
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
                            ->andWhere(['id_store' => $store['id_store']])
                            ->asArray()
                            ->one();
                        if (empty($coupons_bd)){
                            $coupon_new= new Coupon();
                            $coupon_new->title=$coupon['title'];
                            $coupon_new->text=$coupon['text'];
                            $coupon_new->period=$coupon['period'];
                            $coupon_new->id_store=$store['id_store'];
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
                            ->where(['id_store' => $store['id_store']])
                            ->andWhere(['not in', 'id', $coupons_id])
                            ->all();
                        foreach ($coupon_del as $del) {
                            $del->delete();
                        }
                    }
                    $store_save= Store::findOne(['id' =>$store['id_store']]);
                    $store_save->id_process_coupon=$proc_option['id'];
                    $store_save->save();
                }
            }
        }
    }
}

