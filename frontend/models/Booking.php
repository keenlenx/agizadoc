<?
namespace frontend\models;

use Yii;
use yii\db\ActiveRecord;

class Booking extends ActiveRecord
{
    public static function tableName()
    {
        return 'bookings'; // Ensure it matches your database table name
    }

    public function rules()
    {
        return [
            [['txn_id', 'customer_name', 'customer_email', 'customer_phone', 'start_time', 'end_time', 'no_of_hours'], 'required'],
            [['start_time', 'end_time'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['no_of_hours'], 'integer', 'min' => 1, 'max' => 12],
            [['txn_id'], 'string', 'max' => 50],
            [['customer_name', 'customer_email'], 'string', 'max' => 255],
            [['customer_phone'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => ['Pending', 'Confirmed', 'Cancelled', 'Completed']],
            [['txn_id'], 'unique'],
            [['start_time'], 'validateBookingConflict'], // Custom validation for conflicts
        ];
    }

    // Custom validation to prevent overlapping bookings
    public function validateBookingConflict($attribute, $params)
    {
        $conflict = self::find()
            ->where(['not', ['id' => $this->id]]) // Exclude current booking (for updates)
            ->andWhere([
                'or',
                ['between', 'start_time', $this->start_time, $this->end_time],
                ['between', 'end_time', $this->start_time, $this->end_time]
            ])
            ->exists();

        if ($conflict) {
            $this->addError($attribute, 'This time slot is already booked. Please choose another time.');
        }
    }

    // Auto-calculate `end_time` before saving based on `no_of_hours`
    public function beforeSave($insert)
    {
        if ($this->isNewRecord || $this->isAttributeChanged('start_time') || $this->isAttributeChanged('no_of_hours')) {
            $this->end_time = date('Y-m-d H:i:s', strtotime($this->start_time . " +{$this->no_of_hours} hours"));
        }
        return parent::beforeSave($insert);
    }
}
