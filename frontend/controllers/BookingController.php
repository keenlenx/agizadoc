<?
namespace frontend\controllers;

use Yii;
use frontend\models\Booking;
use frontend\models\BookingSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Url;

/**
 * BookingController handles appointment scheduling.
 */
class BookingController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all bookings.
     */
    public function actionIndex()
    {
        $searchModel = new BookingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single booking.
     * @param integer $id
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new booking.
     */
    public function actionCreate()
    {
        $model = new Booking();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Booking successfully created!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing booking.
     * @param integer $id
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Booking successfully updated!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes a booking.
     * @param integer $id
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Booking deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Fetches all confirmed bookings for FullCalendar.
     */
    public function actionGetBookings()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $bookings = Booking::find()->where(['status' => 'Confirmed'])->all();

        $events = [];
        foreach ($bookings as $booking) {
            $events[] = [
                'title' => "Booked - " . $booking->customer_name,
                'start' => $booking->start_time,
                'end' => $booking->end_time,
                'color' => '#FF0000' // Red for booked slots
            ];
        }

        return $events;
    }

    /**
     * Displays the booking calendar.
     */
    public function actionCalendar()
    {
        return $this->render('calendar');
    }

    /**
     * Finds the Booking model based on its primary key.
     * @param integer $id
     */
    protected function findModel($id)
    {
        if (($model = Booking::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested booking does not exist.');
    }
    public function actionCalendarEvents()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $bookings = Booking::find()->all(); // Fetch all bookings from DB
        $events = [];

        foreach ($bookings as $booking) {
            $events[] = [
                'id' => $booking->id,
                'title' => $booking->customer_name . " ({$booking->no_of_hours} hrs)", 
                'start' => $booking->start_time,
                'end' => date('Y-m-d H:i:s', strtotime($booking->start_time . " + {$booking->no_of_hours} hours")),
                'color' => '#007bff', // Blue color for bookings
            ];
        }

        return $events;
    }

}
