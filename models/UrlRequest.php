<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "url_requests".
 *
 * @property string $hash_string MD5 hash of the URL
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string $url
 * @property int $status_code
 * @property int|null $query_count
 * @property int|null $failed_attempts The number of failed attempts
 */
class UrlRequest extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'url_requests';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['hash_string', 'url', 'status_code'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['url'], 'string'],
            [['status_code', 'query_count', 'failed_attempts'], 'integer'],
            [['hash_string'], 'string', 'max' => 32],
            [['hash_string'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'hash_string' => 'MD5 hash of the URL',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'url' => 'Url',
            'status_code' => 'Status Code',
            'query_count' => 'Query Count',
            'failed_attempts' => 'The number of failed attempts',
        ];
    }
}
