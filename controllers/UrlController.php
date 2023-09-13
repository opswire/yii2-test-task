<?php
declare(strict_types=1);

namespace app\controllers;

use app\models\UrlRequest;
use Yii;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\rest\Controller;
use yii\web\Response;

class UrlController extends Controller
{
    public const REQUEST_TIMEOUT_SECONDS = 5;
    public const DEFAULT_STATUS_CODE = 0;
    public const OK_STATUS_CODE = 200;
    public const SECONDS_PER_MINUTE = 60;
    public const LAST_UPDATE_TIMEOUT = self::SECONDS_PER_MINUTE * 10;

    public function actionCheckStatus(): array
    {
        $requestData = Yii::$app->getRequest()->getRawBody();
        $urls = json_decode($requestData);

        $hashes = array_map('md5', $urls);

        $existingRecords = UrlRequest::find()->where(['hash_string' => $hashes])->indexBy('hash_string')->all();

        Yii::$app->response->statusCode = self::OK_STATUS_CODE;
        Yii::$app->response->format = Response::FORMAT_JSON;

        return $this->processUrls($urls, $existingRecords);
    }

    private function processUrls(array $urls, array $existingRecords): array
    {
        $results = [];

        foreach ($urls as $url) {
            $code = $this->processUrl($url, $existingRecords);
            $results[] = ['url' => $url, 'code' => $code];
        }

        return ['codes' => $results];
    }

    private function processUrl(string $url, array $existingRecords): int
    {
        $hash = md5($url);

        return isset($existingRecords[$hash]) ?
            $this->updateRecordAndReturnStatusCode($existingRecords[$hash]) :
            $this->createRecordAndReturnStatusCode($url, $hash);
    }

    private function createRecordAndReturnStatusCode(string $url, string $hash): int
    {
        $newStatusCode = $this->fetchStatusCode($url);

        $newRecord = new UrlRequest();
        $newRecord->hash_string = $hash;
        $newRecord->url = $url;
        $newRecord->status_code = $newStatusCode;
        $newRecord->updated_at = date('Y-m-d H:i:s');
        $newRecord->created_at = date('Y-m-d H:i:s');

        if ($this->checkFailedStatusCode($newStatusCode)) {
            $newRecord->failed_attempts = 1;
        }

        $newRecord->save(); // query_count = 1, failed_attempts = 0 - default values

        $this->saveCacheUpdatedAt($hash, $newRecord->updated_at);

        return $newStatusCode;
    }

    private function updateRecordAndReturnStatusCode(UrlRequest $existingRecord): int
    {
        $currentTime = time();
        $lastUpdateTime = strtotime($this->getCacheUpdatedAt($existingRecord->hash_string));

        if ($currentTime - $lastUpdateTime > self::LAST_UPDATE_TIMEOUT) {
            $newStatusCode = $this->fetchStatusCode($existingRecord->url);
            $existingRecord->status_code = $newStatusCode;
            $existingRecord->updated_at = date('Y-m-d H:i:s');
            $this->saveCacheUpdatedAt($existingRecord->hash_string, $existingRecord->updated_at);

            if ($this->checkFailedStatusCode($newStatusCode)) {
                $existingRecord->failed_attempts++;
            }
        } else {
            $newStatusCode = $existingRecord->status_code;
        }

        $existingRecord->query_count++;
        $existingRecord->save();

        return $newStatusCode;
    }

    private function saveCacheUpdatedAt(string $hash, $updatedAt): void
    {
        Yii::$app->redis->set($hash, $updatedAt);
    }

    private function getCacheUpdatedAt(string $hash): string
    {
        return Yii::$app->redis->get($hash);
    }

    private function checkFailedStatusCode(int $code): bool
    {
        return $code === self::DEFAULT_STATUS_CODE;
    }

    private function fetchStatusCode(string $url): int
    {
        $client = new Client();
        try {
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl($url)
                ->setOptions([
                    'timeout' => self::REQUEST_TIMEOUT_SECONDS,
                ])
                ->send();
            $statusCode = (int)$response->statusCode;
        } catch (InvalidConfigException $e) {
            echo 'Critical error: ' . $e;
            exit($e->getCode());
        } catch (Exception $e) {
            $statusCode = self::DEFAULT_STATUS_CODE;
        }

        return $statusCode;
    }
}