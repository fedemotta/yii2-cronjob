<?php
/**
 * @copyright Federico Nicolás Motta
 * @author Federico Nicolás Motta <fedemotta@gmail.com>
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 * @package yii2-cronjob
 */
namespace fedemotta\cronjob\models;

use DateTime;
use DateInterval;
use DatePeriod;
use yii\helpers\Console;
use Yii;

/**
 * This is the model class for table "{{%cron_job}}".
 * @author Federico Nicolás Motta <fedemotta@gmail.com>
 * @property integer $id_cron_job
 * @property string $controller
 * @property string $action
 * @property integer $limit
 * @property integer $offset
 * @property integer $running
 * @property integer $success
 * @property integer $started_at
 * @property integer $ended_at
 * @property float $last_execution_time
 */
class CronJob extends \yii\db\ActiveRecord
{
    /* used in the calculation of the execution time */
    private static $execution_time = 0;
   
    /**
     * Get and set execution time
     * @return int The execution time
     */
    private static function execution_time(){
        if (self::$execution_time === 0){
            self::$execution_time = -microtime(true);
        }else{
            self::$execution_time += microtime(true);
        }
        return self::$execution_time;
    }
    
    /**
     * Generate dates in range
     * @param type $start_date
     * @param type $end_date
     * @param type $daysDifference
     * @return array of dates
     */
    public static function initDatesInRange($start_date, $end_date, $daysDifference = 1){
        $dates_in_range = [];
        $begin = new DateTime($start_date);
        $end = new DateTime($end_date);
        $endModify = $end->modify( '+'.$daysDifference.' day' ); 

        $interval = new DateInterval('P'.$daysDifference.'D');
        $daterange = new DatePeriod($begin, $interval ,$endModify);

        foreach($daterange as $date){
            $dates_in_range[$date->getTimestamp()] = $date->format("Y-m-d");
        }
        return $dates_in_range;
    }
    

    /**
     * Entire range date.
     * @param string $from
     * @param string $to
     * @param int $daysDifference
     * @return array
     */
    public static function getDateRange($from, $to, $daysDifference = 1) {
        return self::initDatesInRange($from, $to, $daysDifference);
    }

    /**
     * Generate datetimes in range
     * @param type $start_datetime
     * @param type $end_datetime
     * @param type $hoursDifference
     * @return array of datetimes
     */
    public static function initDatetimesInRange($start_datetime, $end_datetime, $hoursDifference = 1){
        $dates_in_range = [];
        $begin = new DateTime($start_datetime);
        $end = new DateTime($end_datetime);
        $endModify = $end->modify( '+'.$hoursDifference.' hour' ); 

        $interval = new DateInterval('PT'.$hoursDifference.'H');
        $daterange = new DatePeriod($begin, $interval ,$endModify);

        foreach($daterange as $date){
            $dates_in_range[$date->getTimestamp()] = $date->format("Y-m-d H:i:s");
        }
        return $dates_in_range;
    }
    

    /**
     * Entire range datetime.
     * @param string $from
     * @param string $to
     * @param int $hoursDifference
     * @return array
     */
    public static function getDatetimeRange($from, $to, $hoursDifference = 1) {
        return self::initDatetimesInRange($from, $to, $hoursDifference);
    }

    /**
     * Count entire range date
     * @param array $range
     * @return int
     */
    public static function countDateRange($range) {
        return count($range);
    }

    /**
     * Get a cron or generates a new one
     * @param type $controller
     * @param type $action
     * @return CronJob The cronjob
     */
    public static function get_cron($controller,$action){
        $cron = self::find()->where(['controller'=>$controller,'action'=>$action, 'success' => 0])->orderBy('id_cron_job DESC')->one();
        if (is_null($cron)){
            $cron = new CronJob();
            $cron->controller = $controller;
            $cron->action = $action;
        }
        return $cron;
    }
    /**
     * Get previous successful ran cron
     * @param type $controller
     * @param type $action
     * @return CronJob The cron job
     */
    public static function get_previous_cron($controller,$action){
        return self::find()->where(['controller'=>$controller,'action'=>$action, 'success' => 1])->orderBy('id_cron_job DESC')->one();
    }
    /**
     * Starts a cron job
     * 
     * @param type $controller
     * @param type $action
     * @param type $limit
     * @param type $max
     * @return boolean Success
     */
    public static function run($controller, $action, $limit, $max){
        
        $current = self::get_cron($controller, $action);
        $current::execution_time();
        $current->limit = $limit;
        $current->running = 1;
        $current->success = 0;
        
        //get previous cron to set offset
        $previous = self::get_previous_cron($controller, $action);
        if (is_null($previous)){
            $current->offset = 0;
        }else{
            if (($previous->offset  + $limit) >= $max){
                $current->offset = 0;
            }else{
               $current->offset = $previous->offset + $previous->limit;
            }
        }
        
        if ($current->save()){
            Console::stdout(Console::ansiFormat("*** running ".$current->controller."/".$current->action."\n", [Console::FG_YELLOW]));
            return $current;
        }else{
            Console::stdout(Console::ansiFormat("*** failed to run ".$current->controller."/".$current->action."\n", [Console::FG_RED]));
            return false;
        }    
    }
    /**
     * Ends a cron job
     * @return boolean Success
     */
    public function finish(){
        $this->running = 0;
        $this->success = 1;
        $this->last_execution_time = self::execution_time();
        
        if ($this->save()){
            Console::stdout(Console::ansiFormat("*** finished ".$this->controller."/".$this->action." (time: " . sprintf("%.3f", $this->last_execution_time) . "s)\n\n", [Console::FG_GREEN]));
            return true;
        }else{
            Console::stdout(Console::ansiFormat("*** failed to finish ".$this->controller."/".$this->action." (time: " . sprintf("%.3f", $this->last_execution_time) . "s)\n\n", [Console::FG_RED]));
            return false;
        }
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cron_job}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['controller', 'action','running', 'success'], 'required'],
            [['limit', 'offset', 'running', 'success', 'started_at', 'ended_at'], 'integer'],
            [['last_execution_time'], 'number'],
            [['controller', 'action'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_cron_job' => Yii::t('app', 'ID'),
            'controller' => Yii::t('app', 'Controller'),
            'action' => Yii::t('app', 'Action'),
            'limit' => Yii::t('app', 'Limit'),
            'offset' => Yii::t('app', 'Offset'),
            'running' => Yii::t('app', 'Running'),
            'success' => Yii::t('app', 'Success'),
            'started_at' => Yii::t('app', 'Started At'),
            'ended_at' => Yii::t('app', 'Ended At'),
            'last_execution_time' => Yii::t('app', 'Last Execution Time'),
        ];
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['started_at'],
                    self::EVENT_BEFORE_UPDATE => ['ended_at'],
                ],
            ]
        ];
    }
}

