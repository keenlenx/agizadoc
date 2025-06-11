<?
use yii\db\Migration;

/**
 * Class m230101_123456_add_stripe_columns_to_order_items
 */
class m230101_123456_add_stripe_columns_to_order_items extends Migration
{
    public function safeUp()
    {
        // Add new columns to the Transport and Moving tables
        // $this->addColumn('{{%transport}}', 'payment_method', $this->string()->after('payment_status'));
        $this->addColumn('{{%transport}}', 'payment_intent_id', $this->string()->after('payment_method'));
        $this->addColumn('{{%transport}}', 'paid_amount', $this->decimal(10, 2)->after('payment_intent_id'));
        $this->addColumn('{{%transport}}', 'currency', $this->string()->after('paid_amount'));
        $this->addColumn('{{%transport}}', 'stripe_charge_id', $this->string()->after('currency'));
        $this->addColumn('{{%transport}}', 'stripe_receipt_url', $this->string()->after('stripe_charge_id'));

        // Add similar columns to the Moving table if needed
    }

    public function safeDown()
    {
        // Drop the columns in case of rollback
        $this->dropColumn('{{%transport}}', 'payment_method');
        $this->dropColumn('{{%transport}}', 'payment_intent_id');
        $this->dropColumn('{{%transport}}', 'paid_amount');
        $this->dropColumn('{{%transport}}', 'currency');
        $this->dropColumn('{{%transport}}', 'stripe_charge_id');
        $this->dropColumn('{{%transport}}', 'stripe_receipt_url');
    }
}
