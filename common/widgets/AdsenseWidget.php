<?
namespace common\widgets;

use yii\base\Widget;

class AdSenseWidget extends Widget
{
    public $client;
    public $slot;
    public $format = 'auto';
    public $responsive = true;
    
    public function run()
    {
        return $this->render('common/views/adsense', [
            'client' => $this->client,
            'slot' => $this->slot,
            'format' => $this->format,
            'responsive' => $this->responsive,
        ]);
    }
}
?>