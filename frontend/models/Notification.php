<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "notification".
 *
 * @property int $notifix_id
 * @property int $user_id
 * @property string $message
 * @property int|null $is_read
 * @property string|null $created_at
 * @property string|null $Cust_email
 *
 * @property User $user
 */
class Notification extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'message'], 'required'],
            [['user_id', 'is_read'], 'integer'],
            [['message', 'Cust_email'], 'string'],
            [['created_at'], 'safe'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'notifix_id' => Yii::t('app', 'Notification ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'message' => Yii::t('app', 'Message'),
            'is_read' => Yii::t('app', 'Is Read'),
            'created_at' => Yii::t('app', 'Created At'),
            'Cust_email' => Yii::t('app', 'Customer Email'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * {@inheritdoc}
     * @return NotificationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new NotificationQuery(get_called_class());
    }

    /**
     * Create a new notification for a status update.
     *
     * @param string $status The new status of the transaction.
     * @param int $userId The ID of the user to notify.
     * @param string $txn_id The transaction ID related to the notification.
     * @param string $userEmail The email of the customer associated with the transaction.
     */
    public static function newnotifyStatus($status, $userId, $txn_id, $userEmail)
    {
        $messageTemplate = 'Transaction #' . $txn_id . ' Updated to'.$status;
        
        // Correctly replace {status} in the template
        $message = $messageTemplate;

        // Save the notification
        $notification = new Notification();
        $notification->user_id = $userId;
        $notification->Cust_email = $userEmail;
        $notification->message = $message;
        $notification->is_read = 0; // Mark as unread

        if (!$notification->save()) {
            Yii::error('Failed to save notification: ' . print_r($notification->errors, true));
        }
    }

    /**
     * Get the count of unread notifications for a user.
     *
     * @param int $userId The ID of the user.
     * @return int The number of unread notifications.
     */
    public static function getUnreadCount($userId)
    {
        return self::find()
            ->where(['user_id' => $userId, 'is_read' => 0])
            ->count();
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id The notification ID.
     * @return int Returns 1 if successful, 0 otherwise.
     */
    public static function MarkAsRead($id)
    {
        $notification = Notification::findOne($id);
        if ($notification) {
            $notification->is_read = 1;
            if ($notification->save()) {
                return 1;
            }
        }
        return 0;
    }
}
