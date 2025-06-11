<?php

namespace frontend\controllers;

use frontend\models\Moving;
use frontend\models\MovingSearch;
use frontend\models\Notification;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\assets\PricingHelper;
use frontend\assets\DistanceHelper;
use frontend\assets\GeoHelper;

use Yii;

/**
 * MovingController implements the CRUD actions for Moving model.
 */
class MovingController extends Controller 
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
                        // Allow admin only for creating
                        [
                            'allow' => true,
                            'actions' => ['delete'],
                            'roles' => ['admin'],
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
                            'roles' => ['@'],
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

    protected function sendEmail($toEmail, $subject, $body)
    {
        try {
            // Send email using Yii's mailer component
            Yii::$app->mailer->compose()
                ->setTo($toEmail)
                ->setFrom(Yii::$app->params['adminEmail']) // Replace with the sender's email if needed
                ->setSubject($subject)
                ->setTextBody($body) // Send plain text body (can also use setHtmlBody for HTML)
                ->send();

            // Log email sent
            Yii::info('Email sent to ' . $toEmail . ' with subject: ' . $subject);
            return true;
        } catch (\Exception $e) {
            // Log the error if email failed to send
            Yii::error('Error sending email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lists all Moving models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new MovingSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Moving model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {

        return $this->render('view', [
            'model' => $this->findModel($id),

        ]);
    }


    /**
     * Creates a new Moving model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
public function actionCreate($data=null)
{
    $model = new Moving();
    $model->transaction_id = 'MV' . PricingHelper::gen_txnid();
    $session = Yii::$app->session;

    // **1. Prefill from JSON URL if provided**
    if ($data !== null) {
        $decodedData = json_decode(urldecode($data), true);
        if (is_array($decodedData)) {
            $model->setAttributes($decodedData, false);
        }
    }

      // **2. Restore guest session data (if available)**
    if (Yii::$app->user->isGuest && $session->has('movingData')) {
        $movingData = $session->get('movingData');
        
        foreach ($model->attributes as $attribute => $value) {
            if (isset($movingData[$attribute]) && $movingData[$attribute] !== null) {
                $model->$attribute = $movingData[$attribute];  // Set only non-null values
            }
        }

        // Remove session data after restoring
        $session->remove('movingData');
    }
    // Ensure time_created is set
    if (!$model->time_created) {
        $model->time_created = date('Y-m-d H:i:s');  // Set current timestamp if not set
    }

    // **3. Prefill details from logged-in user**
    $user = Yii::$app->user->identity;
    if ($user) {
        $model->Customer_email = $model->Customer_email ?: $user->email;
        $model->Customer_phone = $model->Customer_phone ?: $user->phone_no;
        $model->from_address = $model->from_address ?: $user->usr_address;
    }

    // **4. If the form is submitted**
    if ($this->request->isPost && $model->load($this->request->post())) {
        // **5. Save data to session and redirect guests to login**
        if (Yii::$app->user->isGuest) {
            $session->set('movingData', $model->attributes);
            Yii::$app->session->setFlash('info', 'Please log in or sign up to proceed with your request.');
            return $this->redirect(['site/login']);
        }

        // **6. Ensure required fields are filled**
        if (empty($model->from_address) || empty($model->to_address)) {
            Yii::$app->session->setFlash('error', 'Please provide both pickup and delivery addresses.');
            return $this->render('create', ['model' => $model]);
        }

        // **7. Calculate distance and price**
        $result = $this->getDistanceAndPrice($model->from_address, $model->to_address);
        if ($result) {
            $model->Distance = $result['distance'];
            $model->price = $result['price'];
        } else {
            Yii::$app->session->setFlash('error', 'Error geocoding addresses. Delivery cannot be created.');
            return $this->render('create', ['model' => $model]);
        }

        // **8. Validate and save the model**
        if (!$model->validate()) {
            Yii::$app->session->setFlash('error', 'Validation failed. Please check the input fields.');
            return $this->render('create', ['model' => $model]);
        }

        if ($model->save()) {
            $userId = $user->id ?? 1;
            Notification::newnotifyStatus('Created', $userId, $model->transaction_id, $model->Customer_email);

            Yii::info('Moving record created successfully with ID: ' . $model->id);

            // **9. Send notifications**
            $this->sendEmail(Yii::$app->params['adminEmail'], 'New Record Created', 
                'A new record has been created. Transaction ID: ' . $model->transaction_id
            );

            // **10. Send customer confirmation email**
            $emailBody = 'Thank you for ordering our Moving Service #' . htmlspecialchars($model->transaction_id) . 
                '. Login to agiza.fi/site/login with your email to track the status.';
            $this->sendEmail($model->Customer_email, 'NEW ORDER', $emailBody);

            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            Yii::$app->session->setFlash('error', 'Failed to save. Please check your input.');
        }
    } else {
        $model->loadDefaultValues();
    }

    return $this->render('create', ['model' => $model]);
}



    /**
     * Updates an existing Moving model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
 public function actionUpdate($id)
{
    // Step 1: Find the model
    $model = $this->findModel($id);

    // Step 2: Check if the form is submitted
    if ($this->request->isPost && $model->load($this->request->post())) {
        //If the transaction ID was not created in the first place
         if (empty($model->transaction_id)) {
            // If transaction_id is empty, generate a new one
            $model->transaction_id = PricingHelper::gen_txnid();
            Yii::info('Generated new transaction ID: ' . $model->transaction_id);
        }

        // Step 3: Get the pickup and delivery addresses
        $pickupAddress = $model->from_address;
        $deliveryAddress = $model->to_address;

        // Ensure that both pickup and delivery addresses are provided
        if (!empty($pickupAddress) && !empty($deliveryAddress)) {
            // Step 4: Use the helper method to calculate distance and price
            $result = $this->getDistanceAndPrice($pickupAddress, $deliveryAddress);

            // Step 5: Check if calculation was successful
            if ($result) {
                $distance = $result['distance'];
                $price = $result['price'];

                // Step 6: Assign the calculated values to the model
                $model->Distance = $distance;
                $model->price = PricingHelper::MovingPrice($distance,$model->assistance,$model->Elevator,$model->Floor_no);
                $model->balance = $model->price-$model->deposit;

            } else {
                // If there was an issue with the calculation, return an error message
                Yii::$app->session->setFlash('error', 'Failed to calculate the distance and price.');
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        } else {
            // If either address is empty, show an error message
            Yii::$app->session->setFlash('error', 'Both pickup and delivery addresses must be provided.');
            return $this->render('update', [
                'model' => $model,
            ]);
        }

        // Step 7: Save the model with the updated values
        if ($model->save()) {
            Notification::newnotifyStatus($model->Moving_status,$this->user->id,$model->transaction_id,$model->Customer_email);
            Yii::info('Moving record updated successfully with ID: ' . $model->id);
            Yii::$app->session->setFlash('success', 'Moving record updated successfully. ID: ' . $model->id);
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            Yii::error('Error updating Moving record with ID ' . $model->id . ': ' . print_r($model->errors, true));
            Yii::$app->session->setFlash('error', 'Failed to update , Check inputs');
        }
    }

    // Render the update view with the model
    return $this->render('update', [
        'model' => $model,
    ]);
}


    /**
     * Deletes an existing Moving model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        try {
            $this->findModel($id)->delete();
            Yii::info('Moving record deleted successfully with ID: ' . $id);
            Yii::$app->session->setFlash('success', 'Moving record deleted successfully.');
        } catch (\Exception $e) {
            Yii::error('Error deleting Moving record with ID ' . $id . ': ' . $e->getMessage());
            Yii::$app->session->setFlash('error', 'Failed to delete Moving record.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the Moving model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Moving the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Moving::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * Calculates distance and price based on pickup and delivery addresses.
     * @param string $pickupAddress
     * @param string $deliveryAddress
     * @return array|null
     */
    public function getDistanceAndPrice($pickupAddress, $deliveryAddress)
    {
        // Ensure that addresses are not empty
        if (empty($pickupAddress) || empty($deliveryAddress)) {
            Yii::$app->session->setFlash('error', 'Pickup or delivery address is missing.');
            return null;
        }

        // Geocode the pickup address to get its latitude and longitude
        $pickupCoords = GeoHelper::geocodePickupAddress($pickupAddress);
        
        // Geocode the delivery address to get its latitude and longitude
        $deliveryCoords = GeoHelper::geocodeDeliveryAddress($deliveryAddress);

        // Check if geocoding was unsuccessful for the pickup address
        if (!$pickupCoords) {
            Yii::$app->session->setFlash('error', 'Failed to geocode pickup address: ' . $pickupAddress);
            return null; // Return null if geocoding the pickup address fails
        }

        // Check if geocoding was unsuccessful for the delivery address
        if (!$deliveryCoords) {
            Yii::$app->session->setFlash('error', 'Failed to geocode delivery address: ' . $deliveryAddress);
            return null; // Return null if geocoding the delivery address fails
        }

        // Calculate the distance between the pickup and delivery locations using latitude and longitude
        $distance = DistanceHelper::calculateDistance(
            $pickupCoords['lat'], $pickupCoords['lng'],
            $deliveryCoords['lat'], $deliveryCoords['lng'],
            'km' // Return the distance in kilometers
        );

        // Calculate the price based on the calculated distance
        $price = PricingHelper::MovingPrice($distance,'','','');

        // Return both the distance and the price in an associative array
        return [
            'distance' => $distance, // The distance in kilometers
            'price' => $price // The calculated price based on the distance

        ];
    }
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


}
