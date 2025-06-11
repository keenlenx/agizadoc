<?php
use yii\helpers\Url;
use yii2fullcalendar\yii2fullcalendar;

$this->title = 'Booking Calendar';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="booking-calendar">
    <h2>Booking Calendar</h2>
    
    <?= yii2fullcalendar::widget([
        'options' => ['id' => 'calendar'],
        'clientOptions' => [
            'header' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'month,agendaWeek,agendaDay',
            ],
            'events' => Url::to(['/booking/calendar-events']), // Fetch events dynamically
            'editable' => false, // Set to true if you want drag & drop
            'eventLimit' => true,
        ],
    ]); ?>
</div>
