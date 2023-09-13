<?php
declare(strict_types=1);

namespace app\commands;

use app\controllers\UrlController;
use app\models\UrlRequest;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

class StatisticsController extends Controller
{
    public const INTERVAL = "-24 hours";

    public function actionStatistics(): int
    {
        $timeAgo = strtotime(self::INTERVAL);

        $requests = UrlRequest::find()
            ->where(['>', 'updated_at', date('Y-m-d H:i:s', $timeAgo)])
            ->andWhere(['<>', 'status_code', UrlController::OK_STATUS_CODE])
            ->select(['url', 'status_code'])
            ->asArray()
            ->all();

        if (!empty($requests)) {
            $this->stdout("Failed requests in the last 24 hours:\n", BaseConsole::FG_RED);
            foreach ($requests as $request) {
                $this->stdout("URL: {$request['url']}, Status Code: {$request['status_code']}\n");
            }
        } else {
            $this->stdout("No failed requests in the last 24 hours.\n", BaseConsole::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
