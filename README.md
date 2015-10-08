Yii2 Cron Job
=============

[![Latest Stable Version](https://poser.pugx.org/fedemotta/yii2-cronjob/v/stable)](https://packagist.org/packages/fedemotta/yii2-cronjob) [![Total Downloads](https://poser.pugx.org/fedemotta/yii2-cronjob/downloads)](https://packagist.org/packages/fedemotta/yii2-cronjob) [![Latest Unstable Version](https://poser.pugx.org/fedemotta/yii2-cronjob/v/unstable)](https://packagist.org/packages/fedemotta/yii2-cronjob) [![License](https://poser.pugx.org/fedemotta/yii2-cronjob/license)](https://packagist.org/packages/fedemotta/yii2-cronjob)

Yii2 extension to help in the creation of automated console scripts. It helps to manage the execution of console scripts, for example avoiding the execution if the previous cron is already running. It generates a history of cron executed, with the time spent and helps to batch processing the script.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist fedemotta/yii2-cronjob "*"
```

or add

```
"fedemotta/yii2-cronjob": "*"
```

to the require section of your `composer.json` file.

This extension needs a database to manage the cron job execution. Run the following migration to create the table structure:

```
yii migrate --migrationPath=@fedemotta/cronjob/migrations
```

Usage
-----

Once the extension is installed, you can use it as a helper in your console controller.

See the following example:

```php
<?php
namespace somenamespace\controllers;

use fedemotta\cronjob\models\CronJob;
use somenamespace\SomeModel;
use yii\console\Controller;

/**
 * SomeContrController controller
 */
class SomeContrController extends Controller {
 
    /**
     * Run SomeModel::some_method for a period of time
     * @param string $from
     * @param string $to
     * @return int exit code
     */
    public function actionInit($from, $to){
        $dates  = CronJob::getDateRange($from, $to);
        $command = CronJob::run($this->id, $this->action->id, 0, CronJob::countDateRange($dates));
        if ($command === false){
            return Controller::EXIT_CODE_ERROR;
        }else{
            foreach ($dates as $date) {
                //this is the function to execute for each day
                SomeModel::some_method((string) $date);
            }
            $command->finish();
            return Controller::EXIT_CODE_NORMAL;
        }
    }
    /**
     * Run SomeModel::some_method for today only as the default action
     * @return int exit code
     */
    public function actionIndex(){
        return $this->actionInit(date("Y-m-d"), date("Y-m-d"));
    }
    /**
     * Run SomeModel::some_method for yesterday
     * @return int exit code
     */
    public function actionYesterday(){
        return $this->actionInit(date("Y-m-d", strtotime("-1 days")), date("Y-m-d", strtotime("-1 days")));
    }
}
```

Run the SomeModel::some_method for today:
```
./yii some-contr
```
For yesterday:
```
./yii some-contr/yesterday
```
For some custom dates like this:
```
./yii some-contr/init 2010-10-10 2012-10-10
```

And you can add a cron job to run every 10 minutes with some controller action like this:
```
*/10 * * * * /path/to/yii/application/yii some-contr/yesterday >> /var/log/console-app.log 2>&1

```
