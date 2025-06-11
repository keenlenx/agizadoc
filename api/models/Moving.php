<?
// File: api/models/Moving.php

namespace api\models;

use yii\db\ActiveRecord;

class Moving extends ActiveRecord
{
    // Define your model properties and validation rules here
    public static function tableName()
    {
        return 'moving';  // Replace with your table name
    }

    public function rules()
    {
        return [
            [['Customer_email', 'Customer_phone', 'from_address', 'to_address'], 'required'],
            [['Distance', 'price', 'deposit', 'balance'], 'number'],
            // Define other validation rules as needed
        ];
    }
}
