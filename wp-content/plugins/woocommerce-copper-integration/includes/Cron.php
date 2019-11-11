<?php
namespace IwantToBelive\Wc\Copper\Integration\Includes;

class Cron
{
    private static $instance = false;

    protected function __construct()
    {
        add_action('init', [$this, 'createCron']);
        add_action(Bootstrap::CRON_TASK, [$this, 'cronAction']);
        add_action(Bootstrap::CRON_TASK_BULK_ORDERS, [$this, 'bulkSentCron'], 10, 1);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function createCron()
    {
        if (!wp_next_scheduled(Bootstrap::CRON_TASK)) {
            wp_schedule_event(time(), 'daily', Bootstrap::CRON_TASK);
        }
    }

    public function cronAction()
    {
        $settings = get_option(Bootstrap::OPTIONS_KEY);

        if (!empty($settings['token']) && !empty($settings['email'])) {
            Crm::updateInformation();
        }
    }

    public function bulkSentCron($orderIds)
    {
        if (!empty($orderIds)) {
            $orderSender = OrderToCrm::getInstance();

            $count = 0;
            $nextOrderSendIds = $orderIds;

            foreach ($orderIds as $key => $id) {
                if ($count >= 5) {
                    continue;
                }

                $orderSender->orderSendCrm($id);
                unset($nextOrderSendIds[$key]);

                $count++;

                if ((int) $count === 5 && !empty($nextOrderSendIds)) {
                    wp_schedule_single_event(time() + 15, Bootstrap::CRON_TASK_BULK_ORDERS, [$nextOrderSendIds]);
                }
            }
        }
    }

    private function __clone()
    {
        // Nothing
    }
}
