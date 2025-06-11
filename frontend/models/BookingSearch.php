<?
namespace frontend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use frontend\models\Booking;

/**
 * BookingSearch represents the model behind the search form of `frontend\models\Booking`.
 */
class BookingSearch extends Booking
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'no_of_hours'], 'integer'],
            [['customer_name', 'customer_email', 'start_time', 'end_time', 'status'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates a data provider instance with search query applied.
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Booking::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20, // Adjust as needed
            ],
            'sort' => [
                'defaultOrder' => ['start_time' => SORT_ASC],
            ],
        ]);

        if (!$this->load($params) || !$this->validate()) {
            return $dataProvider;
        }

        // Filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'no_of_hours' => $this->no_of_hours,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ]);

        $query->andFilterWhere(['like', 'customer_name', $this->customer_name])
              ->andFilterWhere(['like', 'customer_email', $this->customer_email])
              ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
}
