<?php
namespace IwantToBelive\Wc\Copper\Integration\Includes;

class OrderToCrm
{
    private static $instance = false;

    public $orderID = 0;

    protected function __construct()
    {
        if ($this->isEnabled()) {
            add_action('woocommerce_checkout_update_order_meta', [$this, 'orderSendCrm'], 10, 1);
            add_action('woocommerce_resume_order', [$this, 'orderSendCrm'], 10, 1);
            add_action('woocommerce_order_status_changed', [$this, 'orderSendCrm']);
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function orderSendCrm($orderID)
    {
        $this->orderID = $orderID;

        $settings = get_option(Bootstrap::OPTIONS_KEY);

        $exists = get_post_meta($orderID, '_wc_copper_send', true)
            && $this->checkExists($orderID, $settings['type']);

        if ($settings['type'] === 'lead') {
            if ($exists) {
                $this->leadUpdateStatus($orderID);
            } else {
                $this->leadProcessing($orderID);
            }
        } else {
            if ($exists) {
                $this->opportunityUpdateStage($orderID);
            } else {
                $this->opportunityProcessing($orderID);
            }
        }
    }

    private function checkExists($orderID, $type)
    {
        if ($type === 'lead') {
            $leadID = get_post_meta($orderID, '_wc_copper_lead_id', true);

            if (!$leadID) {
                return false;
            }

            $result = Crm::sendGetApiRequest('leads/' . $leadID);

            if (
                empty($result) ||
                (isset($result['status'])) && (int) $result['status'] === 404
            ) {
                return false;
            }

            return true;
        }

        $opportunityID = get_post_meta($orderID, '_wc_copper_opportunity_id', true);

        if (!$opportunityID) {
            return false;
        }

        $result = Crm::sendGetApiRequest('opportunities/' . $opportunityID);

        if (
            empty($result) ||
            (isset($result['status'])) && (int) $result['status'] === 404
        ) {
            return false;
        }

        return true;
    }

    private function leadUpdateStatus($orderID)
    {
        $leadID = get_post_meta($orderID, '_wc_copper_lead_id', true);

        if (!$leadID) {
            return;
        }

        $settings = get_option(Bootstrap::OPTIONS_KEY);
        $leadStatuses = isset($settings['lead_statuses']) ? $settings['lead_statuses'] : '';

        if (empty($leadStatuses)) {
            return;
        }

        $order = wc_get_order($orderID);
        $status = $order->get_status();

        if (empty($leadStatuses[$status])) {
            return;
        }

        Crm::sendApiPutRequest(
            'leads/' . $leadID,
            [
                'status_id' => $leadStatuses[$status]
            ]
        );
    }

    private function opportunityUpdateStage($orderID)
    {
        $opportunityID = get_post_meta($orderID, '_wc_copper_opportunity_id', true);

        if (!$opportunityID) {
            return;
        }

        $settings = get_option(Bootstrap::OPTIONS_KEY);
        $opportunityStages = isset($settings['opportunity_stages']) ? $settings['opportunity_stages'] : '';

        if (empty($opportunityStages)) {
            return;
        }

        $order = wc_get_order($orderID);
        $status = $order->get_status();

        if (empty($opportunityStages[$status])) {
            return;
        }

        // Pipeline support
        $pipelineStage = explode('_', $opportunityStages[$status]);

        Crm::sendApiPutRequest(
            'opportunities/' . $opportunityID,
            [
                'pipeline_id' => $pipelineStage[0],
                'pipeline_stage_id' => $pipelineStage[1]
            ]
        );
    }

    private function leadProcessing($orderID)
    {
        $settings = get_option(Bootstrap::OPTIONS_KEY);

        $leadStatuses = isset($settings['lead_statuses']) ? $settings['lead_statuses'] : [];

        if (empty($leadStatuses)) {
            return;
        }

        $order = wc_get_order($orderID);
        $status = $order->get_status();

        if (empty($leadStatuses[$status])) {
            return;
        }

        $data = $order->get_data();
        $orderData = $this->prepareOrderData($data, $order);

        $sendFields['lead'] = $this->prepareFields($settings['lead'], $orderData);

        $sendFields['lead']['status_id'] = $leadStatuses[$status];

        if (empty($sendFields['lead']['title'])) {
            $sendFields['lead']['title'] = esc_html__('Order', 'wc-copper-integration')
                . ' '
                . $order->get_order_number();
        }

        $sendFields['lead']['monetary_value'] = (float) $data['total'];

        if (!empty($sendFields['lead']['details'])) {
            $sendFields['lead']['details'] .= "\n\n" . '-------------' . "\n\n";
        }

        if ($order->get_customer_note()) {
            $sendFields['lead']['details'] .= "\n\n" . $order->get_customer_note();
        }

        $sendFields['lead']['details'] .= $this->generateNote($order);

        $lead = Crm::send($sendFields, 'lead');

        // If lead success created
        if (!empty($lead['id'])) {
            update_post_meta($orderID, '_wc_copper_lead_id', $lead['id']);

            $order->add_order_note(esc_html__('Added lead in CRM #', 'wc-copper-integration') . $lead['id']);
        }

        update_post_meta($orderID, '_wc_copper_send', true);
    }

    private function opportunityProcessing($orderID)
    {
        $settings = get_option(Bootstrap::OPTIONS_KEY);

        $opportunityStages = isset($settings['opportunity_stages']) ? $settings['opportunity_stages'] : '';

        if (empty($opportunityStages)) {
            return;
        }

        $order = wc_get_order($orderID);
        $status = $order->get_status();

        if (empty($opportunityStages[$status])) {
            return;
        }

        $data = $order->get_data();
        $orderData = $this->prepareOrderData($data, $order);

        $sendFields['opportunity'] = $this->prepareFields($settings['opportunity'], $orderData);

        $sendFields['opportunity']['pipeline_id'] = $opportunityStages[$status];

        if (empty($sendFields['opportunity']['name'])) {
            $sendFields['opportunity']['name'] = esc_html__('Order', 'wc-copper-integration')
                . ' '
                . $order->get_order_number();
        }

        $sendFields['opportunity']['monetary_value'] = (float) $data['total'];


        $sendFields['person'] = $this->prepareFields($settings['person'], $orderData);
        $sendFields['organization'] = $this->prepareFields($settings['organization'], $orderData);

        if (!empty($sendFields['opportunity']['details'])) {
            $sendFields['opportunity']['details'] .= "\n\n" . '-------------' . "\n\n";
        }

        if ($order->get_customer_note()) {
            $sendFields['opportunity']['details'] .= "\n\n" . $order->get_customer_note();
        }

        $sendFields['opportunity']['details'] .= $this->generateNote($order);

        $opportunity = Crm::send($sendFields, 'opportunity');

        // If opportunity success created
        if (!empty($opportunity['id'])) {
            update_post_meta($orderID, '_wc_copper_opportunity_id', $opportunity['id']);

            $order->add_order_note(
                esc_html__('Added opportunity in CRM #', 'wc-copper-integration')
                . $opportunity['id']
            );
        }

        update_post_meta($orderID, '_wc_copper_send', true);
    }

    private function prepareOrderData($data, $order)
    {
        $returnData = [];

        foreach ($data['billing'] as $key => $value) {
            $returnData['billing_' . $key] = $value;
        }

        foreach ($data['shipping'] as $key => $value) {
            $returnData['shipping_' . $key] = $value;
        }

        $utmFields = $this->parseUtmCookie();

        $returnData['utm_source'] = isset($utmFields['utm_source'])
            ? rawurldecode(wp_unslash($utmFields['utm_source']))
            : '';
        $returnData['utm_medium'] = isset($utmFields['utm_medium'])
            ? rawurldecode(wp_unslash($utmFields['utm_medium']))
            : '';
        $returnData['utm_campaign'] = isset($utmFields['utm_campaign'])
            ? rawurldecode(wp_unslash($utmFields['utm_campaign']))
            : '';
        $returnData['utm_term'] = isset($utmFields['utm_term'])
            ? rawurldecode(wp_unslash($utmFields['utm_term']))
            : '';
        $returnData['utm_content'] = isset($utmFields['utm_content'])
            ? rawurldecode(wp_unslash($utmFields['utm_content']))
            : '';

        // Set ga client id
        $returnData['gaClientID'] = '';

        if (!empty($_COOKIE['_ga'])) {
            $clientId = explode('.', wp_unslash($_COOKIE['_ga']));
            $returnData['gaClientID'] = $clientId[2] . '.' . $clientId[3];
        }

        $returnData['order_number'] = $order->get_order_number();
        $returnData['order_create_date'] = $order->get_date_created()->date_i18n('d.m.Y');

        // Supports used coupon list
        if (!empty($order->get_used_coupons())) {
            $returnData['order_coupon_list'] = implode(', ', $order->get_used_coupons());
        } else {
            $returnData['order_coupon_list'] = '';
        }

        // Supports for Dokan vendor plugin
        if (class_exists('\\Dokan_Vendor')) {
            $vendor = get_post_field('post_author', $order->get_id(), 'raw');
            $vendorName = get_userdata($vendor)->display_name;

            $returnData['dokan_vendor'] = $vendorName;
        }

        // get voucher code by plugin `WooCommerce - PDF Vouchers`
        if (defined('WOO_VOU_META_PREFIX')) {
            if (!empty($orderItems)) {
                $firstItem = array_shift($orderItems);
                $codesItemMeta = wc_get_order_item_meta($firstItem->get_id(), WOO_VOU_META_PREFIX . 'codes');
                $returnData['voucher_code'] = $codesItemMeta;
            } else {
                $returnData['voucher_code'] = '';
            }
        }

        // support use payment method title in fields
        if ($order->get_payment_method_title()) {
            $returnData['payment_method_title'] = $order->get_payment_method_title();
        } else {
            $returnData['payment_method_title'] = '';
        }

        // support use shipping method title in fields
        if ($order->get_shipping_method()) {
            $returnData['shipping_method_title'] = $order->get_shipping_method();
        } else {
            $returnData['shipping_method_title'] = '';
        }

        // support use first product title in fields
        if (!empty($order->get_items())) {
            $firstItem = array_shift($order->get_items());

            $returnData['first_product_title'] = $firstItem->get_name();
        } else {
            $returnData['first_product_title'] = '';
        }

        return $returnData;
    }

    private function prepareFields($fields, $orderData)
    {
        $keys = array_map(function ($key) {
            return '[' . $key . ']';
        }, array_keys($orderData));
        $values = array_values($orderData);
        array_walk($values, function (&$value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
        });

        $prepareFields = [];
        $customFields = get_option(Bootstrap::CUSTOM_FIELDS_KEY);

        $strposFunction = 'mb_strpos';

        if (!function_exists('mb_strpos')) {
            $strposFunction = 'strpos';
        }

        foreach ($fields as $fieldName => $value) {
            if (is_array($value)) {
                foreach ($value as $fieldSubName => $subValue) {
                    if ($strposFunction($fieldSubName, '-populate') !== false) {
                        continue;
                    }

                    $populateValue = isset($value[$fieldSubName . '-populate'])
                        ? $value[$fieldSubName . '-populate']
                        : '';

                    if ($populateValue) {
                        $populateValue = trim(
                            str_replace(
                                $keys,
                                $values,
                                $populateValue
                            )
                        );
                    }

                    if ($populateValue) {
                        $prepareFields[$fieldName][$fieldSubName] = $populateValue;
                    } else {
                        $prepareFields[$fieldName][$fieldSubName] = trim(
                            str_replace(
                                $keys,
                                $values,
                                $subValue
                            )
                        );
                    }

                    // if custom field
                    if ((int) $fieldSubName) {
                        foreach ($customFields as $customField) {
                            if ($customField['id'] == $fieldSubName) {
                                switch ($customField['data_type']) {
                                    case 'Date':
                                        $prepareFields[$fieldName][$fieldSubName] =
                                            date_i18n('m/d/Y', strtotime($prepareFields[$fieldName][$fieldSubName]));
                                        break;
                                    case 'Dropdown':
                                    case 'MultiSelect':
                                        $explodedField = explode(', ', $prepareFields[$fieldName][$fieldSubName]);
                                        $resolveValues = [];

                                        $ids = \array_column($customField['options'], 'id');
                                        $labels = \array_column($customField['options'], 'name');

                                        foreach ($explodedField as $explodeValue) {
                                            if (array_search($explodeValue, $ids) !== false) {
                                                $resolveValues[] = $explodeValue;
                                            } elseif (array_search($explodeValue, $labels) !== false) {
                                                $resolveValues[] = $ids[array_search($explodeValue, $labels)];
                                            }
                                        }

                                        if ($resolveValues) {
                                            $prepareFields[$fieldName][$fieldSubName] =
                                                $customField['data_type'] === 'Dropdown'
                                                    ? $resolveValues[0]
                                                    : $resolveValues;
                                        }

                                        break;
                                }
                            }
                        }
                    }
                }
            } else {
                $prepareFields[$fieldName] = trim(
                    str_replace($keys, $values, $value)
                );
            }
        }

        return $prepareFields;
    }

    private function generateNote($order)
    {
        $orderData = $order->get_data();
        $productRows = '';

        foreach ($order->get_items() as $item) {
            $productRows .= $item->get_name()
                . ', '
                . esc_html__('Quantity', 'wc-copper-integration')
                . ': '
                . $item->get_quantity()
                . ', '
                . esc_html__('Summ', 'wc-copper-integration')
                . ': '
                . $item->get_total()
                . "\n\n";
        }

        // Supports shipping cost
        if ($order->get_shipping_total()) {
            $productRows .= esc_html__('Shipping', 'wc-copper-integration')
                . ': '
                . $order->get_shipping_method()
                . ', '
                . esc_html__('Summ', 'wc-copper-integration')
                . ': '
                . $order->get_shipping_total()
                . "\n"
                . "\n";
        }

        foreach (WC()->countries->get_address_fields(WC()->countries->get_base_country(), 'billing' . '_') as $value => $field) {
            if (!empty($orderData['billing'][str_replace('billing_', '', $value)])) {
                $productRows .= $field['label']
                    . ': '
                    . $orderData['billing'][str_replace('billing_', '', $value)]
                    . "\n";
            }
        }

        return $productRows;
    }

    private function parseUtmCookie()
    {
        if (!empty($_COOKIE[Bootstrap::UTM_COOKIE])) {
            return json_decode(wp_unslash($_COOKIE[Bootstrap::UTM_COOKIE]), true);
        }

        return [];
    }

    private function isEnabled()
    {
        $settings = get_option(Bootstrap::OPTIONS_KEY);

        return !empty($settings['enabled'])
            && (int) $settings['enabled'] === 1
            && !empty($settings['token'])
            && !empty($settings['email']);
    }

    private function __clone()
    {
    }
}
