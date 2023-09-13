<?php
declare(strict_types=1);

namespace app\commands;

use app\controllers\UrlController;
use app\models\UrlRequest;
use yii\console\Controller;

class RetryFailedUrlsController extends Controller
{
    public const LIMIT_FAILED_ATTEMPTS = 5;

    public function actionIndex()
    {
        $failedUrls = UrlRequest::find()
            ->where(['is_disabled' => 0])
            ->andWhere(['status_code' => ['!=', UrlController::OK_STATUS_CODE]])
            ->andWhere(['failed_attempts' => self::LIMIT_FAILED_ATTEMPTS])
            ->all();

        foreach ($failedUrls as $urlRequest) {
            $urlRequest->is_disabled = 1;
            $urlRequest->save();
        }

        echo "Processed " . count($failedUrls) . " failed URLs.\n";
    }
}
