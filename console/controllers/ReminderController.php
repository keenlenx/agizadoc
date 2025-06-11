<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use frontend\models\Moving;
use frontend\models\Transport;
use yii\helpers\Console;

class ReminderController extends Controller
{
    // Action to view both Moving and Transport appointments
    public function actionView()
    {
        // Set the time zone for the application
        $timeZone = 'Europe/Helsinki';
        Yii::$app->formatter->timeZone = $timeZone;

        // Get the current time as a DateTime object in the specified time zone
        $Now = new \DateTime('Now', new \DateTimeZone($timeZone));
        $currentTime = $Now->format('Y-m-d H:i:s');

        // Log the current time for debugging purposes
        Console::output("Current time: " . $currentTime);

        // Get Moving appointments
        $MovingAppointments = Moving::find()->all();
        Console::output("\nMoving Appointments:");
        
        // Iterate over Moving appointments
        foreach ($MovingAppointments as $appointment) {
            // Calculate the time difference in hours
            $appointmentStart = new \DateTime($appointment->Start_time, new \DateTimeZone($timeZone));

            // Skip past appointments
            if ($appointmentStart < $Now) {
                continue; // Skip this appointment
            }
            
            // Format the appointment start time as Y-m-d, l, H:i
            $formattedStartTime = $appointmentStart->format('Y-m-d, l, H:i'); // Date, Day, Time format
            
            // Calculate the Hours Before
            $interval = $appointmentStart->diff($Now); // Get the time difference
            $hoursBefore = $interval->h + ($interval->days * 24); // Convert the difference to hours

            // Display the Moving appointment with the formatted start time and calculated hours before
            Console::output("Moving Appointment ID: " . $appointment->id . " | Start Time: " . $formattedStartTime . 
                " | Hours Before: " . $hoursBefore . " hours | Customer Email: " . $appointment->Customer_email . 
                " | Reminder Sent: " . ($appointment->reminder_sent));
        }

        // Get Transport appointments
        $TransportAppointments = Transport::find()->all();
        Console::output("\nTransport Appointments:");
        
        // Iterate over Transport appointments
        foreach ($TransportAppointments as $appointment) {
            // Calculate the time difference in hours
            $appointmentStart = new \DateTime($appointment->Start_time, new \DateTimeZone($timeZone));

            // Skip past appointments
            if ($appointmentStart < $Now) {
                continue; // Skip this appointment
            }
            
            // Format the appointment start time as Y-m-d, l, H:i
            $formattedStartTime = $appointmentStart->format('Y-m-d, l, H:i'); // Date, Day, Time format
            
            // Calculate the Hours Before
            $interval = $appointmentStart->diff($Now); // Get the time difference
            $hoursBefore = $interval->h + ($interval->days * 24); // Convert the difference to hours

            // Display the Transport appointment with the formatted start time and calculated hours before
            Console::output("Transport Appointment ID: " . $appointment->id . " | Start Time: " . $formattedStartTime . 
                " | Hours Before: " . $hoursBefore . " hours | Customer Email: " . $appointment->Customer_email . 
                " | Reminder Sent: " . ($appointment->reminder_sent));
        }
    }

    // Action to send reminders
    public function actionSend()
    {
        // Set the time zone for the application
        $timeZone = 'Europe/Helsinki';
        Yii::$app->formatter->timeZone = $timeZone;

        // Get the current time as a DateTime object in the specified time zone
        $Now = new \DateTime('Now', new \DateTimeZone($timeZone));
        $currentTime = $Now->format('Y-m-d H:i:s');

        // Log the current time for debugging purposes
        Console::output("Current time: " . $currentTime);

        // Get appointments where the time before start time is less than 6 hours and reminder_sent is 'No'
        $appointmentsToSendReminder = Moving::find()
            ->where(['reminder_sent' => 'No'])  // Ensure reminder has Not been sent yet
            ->all();

        Console::output("\nAppointments to send reminders for:");
        foreach ($appointmentsToSendReminder as $appointment) {
            // Calculate the time difference in hours
            $appointmentStart = new \DateTime($appointment->Start_time, new \DateTimeZone($timeZone));

            // Skip past appointments
            if ($appointmentStart < $Now) {
                continue; // Skip this appointment
            }
            
            $interval = $appointmentStart->diff($Now); // Get the time difference
            $hoursBefore = $interval->h + ($interval->days * 24); // Convert the difference to hours

            // Send reminder if hoursBefore is less than 6 hours
            if ($hoursBefore < 6) {
                // Format the appointment start time as Y-m-d, l, H:i
                $formattedStartTime = $appointmentStart->format('Y-m-d, l, H:i'); // Date, Day, Time format

                Console::output("Appointment ID: " . $appointment->id . " | Start Time: " . $formattedStartTime . 
                    " | Hours Before: " . $hoursBefore . " hours | Customer Email: " . $appointment->Customer_email . 
                    " | Reminder Sent: " . ($appointment->reminder_sent));

                // Send email only if customer email is available
                if ($appointment->Customer_email) {
                    $this->sendReminderEmail($appointment);
                    $appointment->reminder_sent = 'Yes'; // Mark reminder as sent (set as 'Yes')
                    $appointment->save(false); // Save without validation
                    Console::output("Reminder sent for appointment at: " . $formattedStartTime);
                } else {
                    Console::output("No customer email found for appointment at " . $formattedStartTime);
                }
            } else {
                Console::output("Skipping reminder for Appointment ID: " . $appointment->id . " as it's more than 6 hours before the start time.");
            }
        }

        Console::output(count($appointmentsToSendReminder) . " reminders processed!");
    }

    private function sendReminderEmail($appointment)
    {
        $customerEmail = $appointment->Customer_email; // Get customer email from the appointment

        // Format the Start_time (appointment time) in "Y-m-d, l, H:i" format
        $appointmentStart = new \DateTime($appointment->Start_time, new \DateTimeZone('Europe/Helsinki'));
        $formattedStartTime = $appointmentStart->format('Y-m-d, l, H:i'); // Date, Day, Time format

        // Build the email message with formatted time
        $message = "Hello, you have a moving appointment scheduled on " . $formattedStartTime . ". Please be prepared.";

        // Try sending the email and handle potential errors
        try {
            $emailSent = Yii::$app->mailer->compose()
                ->setTo($customerEmail)  // Use the customer email dynamically
                ->setFrom(['info@agiza.fi' => 'AGIZA']) // From address
                ->setSubject('Upcoming Moving Appointment Reminder')  // Subject line
                ->setHtmlBody($message)  // HTML body content
                ->send();

            if ($emailSent) {
                Yii::info('Reminder email successfully sent to ' . $customerEmail, __METHOD__);
            } else {
                Yii::error('Failed to send reminder email to ' . $customerEmail, __METHOD__);
            }
        } catch (\Exception $e) {
            // Log the error if email sending fails
            Yii::error('Error while sending reminder email to ' . $customerEmail . ': ' . $e->getMessage(), __METHOD__);
        }
    }
}
?>
