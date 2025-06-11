<?
namespace frontend\assets;

use frontend\models\Booking; // Replace with your actual model
use yii\helpers\ArrayHelper;

class AppointmentHelper
{
    /**
     * Check if a given start time and duration conflicts with an existing booking.
     *
     * @param string $start_time The proposed booking start time.
     * @param int $no_of_hours The proposed duration in hours.
     * @return bool True if there is a conflict, otherwise false.
     */
    public static function hasConflict($start_time, $no_of_hours)
    {
        $end_time = date('Y-m-d H:i:s', strtotime("+$no_of_hours hours", strtotime($start_time)));

        $conflict = Booking::find()
            ->where(['<', 'start_time', $end_time])
            ->andWhere(['>', 'end_time', $start_time])
            ->exists();

        return $conflict;
    }
}
