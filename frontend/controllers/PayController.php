<?php

namespace frontend\controllers;

use yii;
use yii\web\Controller;
use frontend\models\Transport;
use frontend\models\Moving;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

class PayController extends Controller
{
    private $modelName; // Property to store the model name

      public function init()
    {
      
        parent::init();
        Stripe::setApiKey(Yii::$app->params['stripeKey']);
        // Stripe::setApiKey($StripeTestKey);
    }


    /**
     * Helper method to retrieve orderitem (Delivery or Moving) by txn_id
     */
    private function getOrderItemByTxnID($txn_id)
    {
        $result = Transport::findOne(['transaction_id' => $txn_id]) ?? Moving::findOne(['transaction_id' => $txn_id]);

        if ($result) {
            $this->modelName = strtolower((new \ReflectionClass($result))->getShortName()); // Dynamically set the model name in lowercase
            return $result; // Return the result object
        } else {
            $this->modelName = null; // Indicate no record found
            return null; // Return null when no record is found
        }
    }
    /**
     * Process the Deposit and create Stripe session
     */
    public function actionDeposit($txn_id = null)
    {
        if (!$txn_id) {
            Yii::$app->session->setFlash('error', 'Transaction ID is missing.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        $orderitem = $this->getOrderItemByTxnID($txn_id);

        if (!$orderitem) {
            Yii::$app->session->setFlash('error', 'Transaction not found.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        Yii::info("Processing Deposit for model: {$this->modelName}");

        // Check if the Deposit is already paid
        if ($orderitem->payment_status === 'Deposit' ) {
            Yii::$app->session->setFlash('info', 'Desposit has already been paid.');
            return $this->redirect([$this->modelName . '/pay', 'id' => $orderitem->id, 'transaction_id' => $txn_id]);
        }
         else if ($orderitem->payment_status ==='Pending' && $orderitem->price >=200){
            // Deposit can only be placed above 200
            Yii::$app->session->setFlash('info', 'Full payment is needed for purchases less than 200 Euros');
             $balance=$orderitem->price*30/100;
             $orderitem->deposit=$balance;
         
        } else {
             $balance=$orderitem->price;
        }
        // Create Stripe Checkout Session
        $stripesession = Session::create([
            'payment_method_types' => ['mobilepay', 'card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'EUR',
                    'product_data' => ['name' => 'TXN : #' . $orderitem->transaction_id],
                    'unit_amount' => round($balance * 100), // Stripe expects the amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'allow_promotion_codes' => true,
            'success_url' => Yii::$app->urlManager->createAbsoluteUrl(['pay/deposited', 'txn_id' => $orderitem->transaction_id]),
            'cancel_url' => Yii::$app->urlManager->createAbsoluteUrl(['pay/cancel', 'txn_id' => $orderitem->transaction_id]),
        ]);

        // Redirect user to Stripe Checkout
        //Yii::$app->session->setFlash('info', 'Posted '.$stripesession);
        return $this->redirect($stripesession->url);
    }
    /**
     * Process the payment and create Stripe session
     */
    public function actionPay($txn_id = null)
    {
        if (!$txn_id) {
            Yii::$app->session->setFlash('error', 'Transaction ID is missing.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        $orderitem = $this->getOrderItemByTxnID($txn_id);

        if (!$orderitem) {
            Yii::$app->session->setFlash('error', 'Transaction not found.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        Yii::info("Processing payment for model: {$this->modelName}");

        // Check if the orderitem is already paid
        if ($orderitem->payment_status === 'Paid') {
            Yii::$app->session->setFlash('info', 'Transaction has already been paid.');
            return $this->redirect([$this->modelName . '/view', 'id' => $orderitem->id, 'transaction_id' => $txn_id]);
        }
        if ($orderitem->payment_status ==='Deposit'){
         $balance = $orderitem->price-$orderitem->deposit;
        } else {
             $balance=$orderitem->price;
        }
         //$orderitem->balance=$balance;
        // Create Stripe Checkout Session
        $stripesession = Session::create([
            'payment_method_types' => ['mobilepay', 'card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'EUR',
                    'product_data' => ['name' => 'TXN : #' . $orderitem->transaction_id],
                    'unit_amount' => round($balance * 100,2), // Stripe expects the amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'allow_promotion_codes' => true,
            'success_url' => Yii::$app->urlManager->createAbsoluteUrl(['pay/success', 'txn_id' => $orderitem->transaction_id]),
            'cancel_url' => Yii::$app->urlManager->createAbsoluteUrl(['pay/cancel', 'txn_id' => $orderitem->transaction_id]),
        ]);

        // Redirect user to Stripe Checkout
        //Yii::$app->session->setFlash('info', 'Posted '.$stripesession);
        return $this->redirect($stripesession->url);
    }

    /**
     * Handle successful payment
         */
    public function actionSuccess($txn_id)
    {

        $orderitem = $this->getOrderItemByTxnID($txn_id);

        if (!$orderitem) {
            Yii::$app->session->setFlash('error', 'Item not found.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        // Mark the orderitem as paid
        $orderitem->payment_status = 'Paid'; // Ensure the model has the 'payment_status' attribute

        // Optionally store Stripe session ID
        $orderitem->Stripe_code = $txn_id;

        // Save the orderitem with updated status and Stripe code
        if ($orderitem->save()) {

            Yii::$app->session->setFlash('success', "Payment successful for model: {$this->modelName}");
            $this->getPaymentData($txn_id);
            return $this->redirect([$this->modelName . '/view', 'id' => $orderitem->id, 'transaction_id' => $txn_id]);
        } else {
            // If save failed, capture any validation errors or issues
            Yii::$app->session->setFlash('error', 'Payment succeeded, but there was an issue saving the status.');
            return $this->redirect([$this->modelName . '/view', 'id' => $orderitem->id, 'transaction_id' => $txn_id]);
        }
    }
    /* Handle successful Deposit
         */
    public function actionDeposited($txn_id)
    {

        $orderitem = $this->getOrderItemByTxnID($txn_id);

        if (!$orderitem) {
            Yii::$app->session->setFlash('error', 'Item not found.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        // Mark the orderitem as paid
        $orderitem->payment_status = 'Deposit'; // Ensure the model has the 'payment_status' attribute
        $orderitem->deposit = $orderitem->price*30/100;
        // Optionally store Stripe session ID
        $orderitem->Stripe_code = $txn_id;

        // Save the orderitem with updated status and Stripe code
        if ($orderitem->save()) {

            Yii::$app->session->setFlash('success', "Desposit successful for : {$this->modelName}");
            $this->getPaymentData($txn_id);
            return $this->redirect([$this->modelName . '/view', 'id' => $orderitem->id, 'transaction_id' => $txn_id]);
        } else {
            // If save failed, capture any validation errors or issues
            Yii::$app->session->setFlash('error', 'Payment succeeded, but there was an issue saving the status.');
            return $this->redirect([$this->modelName . '/view', 'id' => $orderitem->id, 'transaction_id' => $txn_id]);
        }
    }

    /**
     * Handle canceled payment
     */
    public function actionCancel($txn_id = null)
    {
        if (!$txn_id) {
            Yii::$app->session->setFlash('error', 'Transaction ID is missing.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        $orderitem = $this->getOrderItemByTxnID($txn_id);

        if ($orderitem) {
            Yii::$app->session->setFlash('error', "Payment canceled for : {$this->modelName}");
           return $this->redirect([$this->modelName . '/view', 'id' => $orderitem->id, 'transaction_id' => $txn_id]);
        }

        Yii::$app->session->setFlash('error', 'orderitem not found.');
        return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
    }

    /**
     * Handle deletion of orderitem
     */
    public function actionDelete($txn_id = null)
    {
        if (!$txn_id) {
            Yii::$app->session->setFlash('error', 'Transaction ID is missing.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        $orderitem = $this->getOrderItemByTxnID($txn_id);

        if (!$orderitem) {
            Yii::$app->session->setFlash('error', 'orderitem not found.');
            return $this->redirect([$this->modelName . '/index']); // Redirect dynamically based on model
        }

        if ($orderitem->delete()) {
            Yii::$app->session->setFlash('success', " (Model: {$this->modelName}) deleted successfully.");
        } else {
            Yii::$app->session->setFlash('error', 'Failed to delete orderitem.');
        }

        return $this->redirect([$this->modelName . '/index']);
    }

       public function getPaymentData($payment_id)
    {
        try {
            // You can retrieve either PaymentIntent or Charge depending on your integration.
            // If you're using PaymentIntent, use the following:
            $paymentIntent = PaymentIntent::retrieve($payment_id);  // Use the PaymentIntent ID
            Yii::info("Stripe Payment Intent Details: " . print_r($paymentIntent, true));

            // Alternatively, if you're using Charges, you can retrieve it like so:
            // $charge = \Stripe\Charge::retrieve($payment_id);  // Use the Charge ID
            // Yii::info("Stripe Charge Details: " . print_r($charge, true));

            // Assuming you use PaymentIntent, we return the details
            return $paymentIntent;  // Return the full PaymentIntent object with details
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Handle any errors that occur during the API call
            Yii::error('Error retrieving Stripe payment data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Example function to show usage of getPaymentData
     * This function is for displaying payment data in a view or debugging purposes.
     */
    public function actionShowPaymentDetails($payment_id)
    {
        $paymentData = $this->getPaymentData($payment_id);

        if ($paymentData) {
            // Display the payment data or process as needed
            return $this->render('Moving/view', ['paymentData' => $paymentData]);
        } else {
            // Handle error if no payment data found
            Yii::$app->session->setFlash('error', 'Payment details not found.');
            return $this->redirect(['site/index']);
        }
    }
}
