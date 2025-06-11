<?php

namespace frontend\controllers;

use frontend\models\Notification;
use frontend\models\Transport;
use frontend\models\TransportSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\assets\PricingHelper;
use frontend\assets\DistanceHelper;
use frontend\assets\GeoHelper;
use Yii;

/**
 * TransportController implements the CRUD actions for Transport model.
 */
class TransportController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['index', 'view', 'create', 'update', 'delete', 'complete'], // Specify actions
                    'rules' => [
                        // Allow authenticated users for most actions
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                        // Allow guests only for creating
                        [
                            'allow' => true,
                            'actions' => ['create'],
                            'roles' => ['?'],
                        ],
                        // Redirect guests to login for other actions
                        [
                            'allow' => false,
                            'roles' => ['?'],
                            'denyCallback' => function () {
                                return Yii::$app->response->redirect(['site/login']);
                            },
                        ],
                         // Allow  only for completing
                        [
                            'allow' => false,
                            'actions' => ['complete'],
                            'roles' => ['admin','partner'],
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'], // Require POST for delete
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Transport models.
     */
    public function actionIndex()
    {
        $searchModel = new TransportSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Transport model.
     */
    public function actionView($id)
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Marks a transport as completed.
     */
    public function actionComplete($id)
    {
        $model = $this->findModel($id);

        if ($model->Moving_status === 'Completed') {
            Yii::$app->session->setFlash('error', 'Task is already marked as "Completed".');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->Moving_status === 'Cancelled') {
            Yii::$app->session->setFlash('error', 'Task cannot be completed because it is already "Cancelled".');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model->Moving_status = 'Completed';
        $model->End_time = date('Y-m-d H:i:s');

        if ($model->save()) {
            Yii::$app->session->setFlash('success', 'Task marked as completed successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to mark the task as completed.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Creates a new Transport model.
     */
    public function actionCreate()
    {
        $model = new Transport();
        $model->transaction_id = 'TX' . PricingHelper::gen_txnid();

        $user = Yii::$app->user->identity;
        if ($user) {
            $model->Customer_email = $user->email;
            $model->Customer_phone = $user->phone_no;
            $model->from_address = $user->usr_address;
        }

        if ($this->request->isPost && $model->load($this->request->post())) {
            // Prevent duplicate submissions
            if (Transport::find()->where(['transaction_id' => $model->transaction_id])->exists()) {
                Yii::$app->session->setFlash('error', 'Duplicate submission detected.');
                return $this->redirect(['index']);
            }

            // Calculate distance and price
            $result = $this->getDistanceAndPrice($model->from_address, $model->to_address);
            if ($result) {
                $model->Distance = $result['distance'];
                $model->price = $result['price'];
            } else {
                Yii::$app->session->setFlash('error', 'Error calculating distance and price.');
                return $this->render('create', ['model' => $model]);
            }

            if ($model->save()) {
                Notification::newnotifyStatus($model->Moving_status, $model->id, $model->transaction_id, $model->Customer_email);
                Yii::$app->session->setFlash('success', 'Failed to create delivery.');
                return $this->redirect(['view', 'id' => $model->id]); // PRG pattern
            } else {
                Yii::$app->session->setFlash('error', 'Failed to create delivery.');
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    /**
     * Updates an existing Transport model.
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post())) {
            $result = $this->getDistanceAndPrice($model->from_address, $model->to_address);
            if ($result) {
                $model->Distance = $result['distance'];
                $model->price = $result['price'];
            } else {
                Yii::$app->session->setFlash('error', 'Failed to calculate distance and price.');
                return $this->render('update', ['model' => $model]);
            }

            if ($model->save()) {
                Notification::newnotifyStatus($model->Moving_status, Yii::$app->user->id, $model->transaction_id, $model->Customer_email);
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Failed to update record.');
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Deletes an existing Transport model.
     */
    public function actionDelete($id)
    {
        try {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('success', 'Transport record deleted.');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Failed to delete Transport record.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the Transport model.
     */
    protected function findModel($id)
    {
        if (($model = Transport::findOne(['id' => $id])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Calculates distance and price.
     */
    public function getDistanceAndPrice($pickupAddress, $deliveryAddress)
    {
        if (empty($pickupAddress) || empty($deliveryAddress)) {
            Yii::$app->session->setFlash('error', 'Pickup or delivery address is missing.');
            return null;
        }

        $pickupCoords = GeoHelper::geocodePickupAddress($pickupAddress);
        $deliveryCoords = GeoHelper::geocodeDeliveryAddress($deliveryAddress);

        if (!$pickupCoords || !$deliveryCoords) {
            Yii::$app->session->setFlash('error', 'Failed to geocode addresses.');
            return null;
        }

        $distance = DistanceHelper::calculateDistance($pickupCoords['lat'], $pickupCoords['lng'], $deliveryCoords['lat'], $deliveryCoords['lng'], 'km');
        $price = PricingHelper::TransportPrice($distance);

        return ['distance' => $distance, 'price' => $price];
    }
}
