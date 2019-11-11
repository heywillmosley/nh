<?php
namespace IwantToBelive\Wc\Copper\Integration\Admin;

use IwantToBelive\Wc\Copper\Integration\Includes\Bootstrap;
use IwantToBelive\Wc\Copper\Integration\Includes\Crm;

class WcSettingsPage
{
    private static $instance = false;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function __construct()
    {
        add_action('admin_menu', [$this, 'addSubmenu'], PHP_INT_MAX);
        add_action('wp_ajax_wcCopperAjaxValidate', [$this, 'wcCopperAjaxValidate']);
        add_action('wp_ajax_wcCopperAjaxSaveSettings', [$this, 'wcCopperAjaxSaveSettings']);

        if (isset($_GET['page']) && $_GET['page'] === Bootstrap::OPTIONS_KEY) {
            add_action('admin_enqueue_scripts', function () {
                wp_enqueue_style('jquery-ui-tabs-iwtb', WC_COPPER_PLUGIN_URL . 'admin/css/jquery-ui.css', false, '1.8.8');
                wp_enqueue_script('jquery-ui-tabs-iwtb', WC_COPPER_PLUGIN_URL . 'admin/js/jquery-ui.js', false, WC_COPPER_PLUGIN_VERSION, true);
                wp_enqueue_script('wc-copper-admin-js', WC_COPPER_PLUGIN_URL . 'admin/js/admin.js', false, WC_COPPER_PLUGIN_VERSION, true);
            });
        }
    }

    public function wcCopperAjaxValidate()
    {
        parse_str(trim(wp_unslash($_POST['form'])), $data);

        $token = isset($data['token']) ? wp_unslash($data['token']) : '';
        $email = isset($data['email']) ? wp_unslash($data['email']) : '';

        if (empty($token) || empty($email)) {
            $response = sprintf(
                '<div data-ui-component="wccoppernotice" class="error notice notice-error is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
                esc_html__('ERROR', 'wc-copper-integration'),
                esc_html__('To integrate with Copper, your must fill API token and email.', 'wc-copper-integration')
            );
        } else {
            $setting = (array) get_option(Bootstrap::OPTIONS_KEY);

            $setting['token'] = $token;
            $setting['enabled'] = isset($data['enabled']) ? wp_unslash($data['enabled']) : '';
            $setting['type'] = isset($data['type']) ? wp_unslash($data['type']) : '';
            $setting['email'] = $email;

            update_option(Bootstrap::OPTIONS_KEY, $setting);

            $response = Crm::checkConnection();

            if (empty($response)) {
                CRM::updateInformation();

                $response = sprintf(
                    '<div data-ui-component="wccoppernotice" class="updated notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html__('Integration settings check is successfully.', 'wc-copper-integration')
                );
            }
        }

        echo wp_kses_post($response);

        exit();
    }

    public function wcCopperAjaxSaveSettings()
    {
        parse_str(trim(wp_unslash($_POST['form'])), $data);

        $setting = (array) get_option(Bootstrap::OPTIONS_KEY);
        $data['token'] = isset($setting['token']) ? $setting['token'] : '';
        $data['email'] = isset($setting['email']) ? $setting['email'] : '';
        $data['enabled'] = isset($setting['enabled']) ? $setting['enabled'] : '';
        $data['type'] = isset($setting['type']) ? $setting['type'] : '';

        update_option(Bootstrap::OPTIONS_KEY, $data);

        echo sprintf(
            '<div data-ui-component="wccoppernotice" class="updated notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html__('Settings successfully updated.', 'wc-copper-integration')
        );

        exit();
    }

    public function addSubmenu()
    {
        add_submenu_page(
            'woocommerce',
            esc_html__('Copper CRM', 'wc-copper-integration'),
            esc_html__('Copper CRM', 'wc-copper-integration'),
            'manage_woocommerce',
            Bootstrap::OPTIONS_KEY,
            [$this, 'settingsPage']
        );
    }

    public function settingsPage()
    {
        $settings = get_option(Bootstrap::OPTIONS_KEY);
        ?>
        <div id="poststuff" class="woocommerce-reports-wrap halved">
            <h1><?php esc_html_e('Integration settings', 'wc-copper-integration'); ?></h1>
            <p>
                <?php
                echo sprintf(
                    '%1$s <a href="%2$s" target="_blank">%3$s</a>. %4$s.',
                    esc_html__('Plugin documentation: ', 'wc-copper-integration'),
                    esc_url(WC_COPPER_PLUGIN_URL . 'documentation/index.html#step-1'),
                    esc_html__('open', 'wc-copper-integration'),
                    esc_html__(
                        'Or open the folder `documentation` in the plugin and open index.html',
                        'wc-copper-integration'
                    )
                );
                ?>
            </p>

            <form data-ui-component="integration-settings">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enabled">
                                <?php esc_html_e('Enable', 'wc-copper-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="hidden" value="0" id="enabled" name="enabled">
                            <input type="checkbox"
                                value="1"
                                <?php echo isset($settings['enabled']) && $settings['enabled'] == '1' ? 'checked' : ''; ?>
                                id="enabled"
                                name="enabled">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="token">
                                <?php esc_html_e('API token', 'wc-copper-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                aria-required="true"
                                value="<?php
                                echo isset($settings['token'])
                                    ? esc_attr($settings['token'])
                                    : '';
                                ?>"
                                id="token"
                                placeholder="<?php esc_html_e('Your personal API token', 'wc-copper-integration'); ?>"
                                name="token"
                                class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email">
                                <?php esc_html_e('User email', 'wc-copper-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email"
                                aria-required="true"
                                value="<?php
                                echo isset($settings['email'])
                                    ? esc_attr($settings['email'])
                                    : '';
                                ?>"
                                id="email"
                                placeholder="mail@email.com"
                                name="email"
                                class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="type">
                                <?php esc_html_e('Type', 'wc-copper-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="type" id="type">
                                <option value="lead" <?php
                                echo !isset($settings['type']) || $settings['type'] == 'lead' ? 'selected' : '';
                                ?>>
                                    <?php esc_html_e('Lead', 'wc-copper-integration'); ?>
                                </option>
                                <option value="opportunity" <?php
                                echo isset($settings['type']) && $settings['type'] == 'opportunity' ? 'selected' : '';
                                ?>>
                                    <?php esc_html_e('Opportunity', 'wc-copper-integration'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit"
                        class="button button-primary"
                        data-ui-component="save-integration-settings"
                        value="<?php esc_attr_e('Save', 'wc-copper-integration'); ?>"
                        name="submit">
                </p>
            </form>

            <?php if (isset($settings['token']) && $settings['token']) { ?>
                <hr>
                <h1><?php esc_html_e('Fields mapping', 'wc-copper-integration'); ?></h1>
                <form>
                    <strong>
                    <?php
                    esc_html_e(
                        'In the following fields, you can use these tags:',
                        'wc-copper-integration'
                    );
                    ?>
                    </strong>
                    <table border="1" cellpadding="10" cellspacing="0">
                        <tbody>
                            <tr>
                                <td>
                                    <?php
                                    foreach (WC()->countries->get_address_fields(WC()->countries->get_base_country(), 'billing' . '_') as $value => $field) {
                                        echo esc_html('[' . $value . '] - ' . (isset($field['label']) ? $field['label'] : $field['placeholder'])) . '<br>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    foreach (WC()->countries->get_address_fields(WC()->countries->get_base_country(), 'shipping' . '_') as $value => $field) {
                                        echo esc_html('[' . $value . '] - ' . (isset($field['label']) ? $field['label'] : $field['placeholder'])) . '<br>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <?php
                                    esc_html_e(
                                        'Utm-fields',
                                        'wc-copper-integration'
                                    );
                                    ?>:<br>
                                    <span class="mailtag code">[utm_source]</span>
                                    <span class="mailtag code">[utm_medium]</span>
                                    <span class="mailtag code">[utm_campaign]</span>
                                    <span class="mailtag code">[utm_term]</span>
                                    <span class="mailtag code">[utm_content]</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <?php
                                    esc_html_e(
                                        'GA fields',
                                        'wc-copper-integration'
                                    );
                                    ?>:<br>
                                    <span class="mailtag code">[gaClientID]</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <?php
                                    esc_html_e(
                                        'Additional tags',
                                        'wc-copper-integration'
                                    );
                                    ?>:
                                    <br>
                                    <span class="mailtag code">[order_number]</span>
                                    <span class="mailtag code">[order_create_date]</span>
                                    <span class="mailtag code">[first_product_title]</span>
                                    <span class="mailtag code">[payment_method_title]</span>
                                    <span class="mailtag code">[shipping_method_title]</span>
                                    <span class="mailtag code">[order_coupon_list]</span>
                                    <?php if (defined('WOO_VOU_META_PREFIX')) { ?>
                                        <span class="mailtag code">[voucher_code]</span>
                                    <?php } ?>
                                    <?php if (class_exists('\\Dokan_Vendor')) { ?>
                                        <span class="mailtag code">[dokan_vendor]</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <br>
                    <div data-ui-component="wc-copper-setting-tabs">
                        <ul>
                            <li>
                                <a href="#lead-fields">
                                    <?php esc_html_e('Lead fields', 'wc-copper-integration'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#opportunity-fields">
                                    <?php esc_html_e('Opportunity fields', 'wc-copper-integration'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#organization-fields">
                                    <?php esc_html_e('Organization fields', 'wc-copper-integration'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#person-fields">
                                    <?php esc_html_e('Person fields', 'wc-copper-integration'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#status-mapping">
                                    <?php esc_html_e('Status mapping', 'wc-copper-integration'); ?>
                                </a>
                            </li>
                        </ul>
                        <div id="lead-fields">
                            <strong><?php esc_html_e('Value - auto set', 'wc-copper-integration'); ?></strong>
                            <hr>
                            <?php LeadSettings::getInstance()->render($settings); ?>
                        </div>
                        <div id="opportunity-fields">
                            <strong><?php esc_html_e('Value - auto set', 'wc-copper-integration'); ?></strong>
                            <hr>
                            <?php OpportunitySettings::getInstance()->render($settings); ?>
                        </div>
                        <div id="organization-fields">
                            <?php OrganizationSettings::getInstance()->render($settings); ?>
                        </div>
                        <div id="person-fields">
                            <?php PersonSettings::getInstance()->render($settings); ?>
                        </div>
                        <div id="status-mapping">
                            <h3><?php esc_html_e('For lead', 'wc-copper-integration'); ?></h3>
                            <table class="form-table">
                                <?php
                                $leadStatuses = isset($settings['lead_statuses']) ? $settings['lead_statuses'] : [];

                                foreach (wc_get_order_statuses() as $status => $label) {
                                    $value = str_replace('wc-', '', $status);
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo esc_html($label); ?>
                                        </td>
                                        <td>
                                            <?php esc_html_e('Status', 'wc-copper-integration'); ?>
                                            <select id="lead_statuses<?php echo esc_attr($value); ?>"
                                                name="lead_statuses[<?php echo esc_attr($value); ?>]">
                                                <option value=""><?php esc_html_e('Not chosen', 'wc-copper-integration'); ?></option>
                                                <?php
                                                $currentValue = isset($leadStatuses[$value]) ? $leadStatuses[$value] : '';

                                                foreach (get_option(Bootstrap::LEAD_STATUSES_KEY) as $status) {
                                                    echo '<option value="'
                                                        . esc_attr($status['id'])
                                                        . '"'
                                                        . ($currentValue == $status['id'] ? ' selected' : '')
                                                        . '>'
                                                        . esc_html($status['id'] . ' - ' . $status['name'])
                                                        . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </table>
                            <h3><?php esc_html_e('For opportunity', 'wc-copper-integration'); ?></h3>
                            <table class="form-table">
                                <?php
                                $opportunityStages = isset($settings['opportunity_stages']) ? $settings['opportunity_stages'] : [];

                                foreach (wc_get_order_statuses() as $status => $label) {
                                    $value = str_replace('wc-', '', $status);
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo esc_html($label); ?>
                                        </td>
                                        <td>
                                            <?php esc_html_e('Stage / Pipeline', 'wc-copper-integration'); ?>
                                            <select id="_opportunity_stages_<?php echo esc_attr($value); ?>"
                                                name="opportunity_stages[<?php echo esc_attr($value); ?>]">
                                                <option value=""><?php esc_html_e('Not chosen', 'wc-copper-integration'); ?></option>
                                                <?php
                                                $currentStage = isset($opportunityStages[$value]) ? $opportunityStages[$value] : '';

                                                foreach (get_option(Bootstrap::PIPELINES_KEY) as $pipeline) {
                                                    if (!empty($pipeline['stages'])) {
                                                        echo '<optgroup label="' . esc_html($pipeline['name']) . '">';

                                                        foreach ($pipeline['stages'] as $stage) {
                                                            $value = $pipeline['id'] . '_' . $stage['id'];

                                                            echo '<option value="'
                                                                . esc_attr($value)
                                                                . '"'
                                                                . ($currentStage == $value ? ' selected' : '')
                                                                . '>'
                                                                . esc_html($stage['name'])
                                                                . '</option>';
                                                        }

                                                        echo '</optgroup>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                    <hr>

                    <p class="submit">
                        <input type="submit"
                            class="button button-primary"
                            data-ui-component="wc-copper-save-settings"
                            value="<?php esc_attr_e('Save settings', 'wc-copper-integration'); ?>"
                            name="submit">
                    </p>
                </form>
            <?php } ?>
            <hr>
            <?php
            if (isset($_POST['purchase-code'])) {
                $code = trim(wp_unslash($_POST['purchase-code']));

                $response = \wp_remote_post(
                    'https://wordpress-plugins.xyz/envato/license.php',
                    [
                        'body' => [
                            'purchaseCode' => $code,
                            'itemID' => '23770844',
                            'action' => isset($_POST['verify']) ? 'activate' : 'deactivate',
                            'domain' => site_url()
                        ],
                        'timeout' => 20
                    ]
                );

                if (is_wp_error($response)) {
                    $messageContent = '(Code - '
                        . $response->get_error_code()
                        . ') '
                        . $response->get_error_message();

                    $message = 'failedCheck';
                } else {
                    $response = json_decode(wp_remote_retrieve_body($response));

                    if ($response->status == 'successCheck') {
                        if (isset($_POST['verify'])) {
                            update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, $code);
                        } else {
                            update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, '');
                        }
                    } elseif (!isset($_POST['verify']) && $response->status == 'alreadyInactive') {
                        update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, '');
                    }

                    $messageContent = $response->message;
                    $message = $response->status;
                }

                if ($message == 'successCheck') {
                    echo sprintf(
                        '<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
                        esc_html($messageContent)
                    );
                } elseif ($messageContent) {
                    echo sprintf(
                        '<div class="error notice notice-error is-dismissible"><p>%s</p></div>',
                        esc_html($messageContent)
                    );
                }
            }

            $code = get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY);
            ?>
            <h1>
                <?php esc_html_e('License verification', 'wc-copper-integration'); ?>
                <?php if ($code) { ?>
                    - <small style="color: green;">
                        <?php esc_html_e('verified', 'wc-copper-integration'); ?>
                    </small>
                <?php } else { ?>
                    - <small style="color: red;">
                        <?php esc_html_e('please verify your purchase code', 'wc-copper-integration'); ?>
                    </small>
                <?php } ?>
            </h1>
            <form method="post" id="wccopper-license-verify" action="#wccopper-license-verify">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="purchase-code">
                                <?php esc_html_e('Purchase code', 'wc-copper-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                aria-required="true"
                                required
                                value="<?php
                                echo !empty($code)
                                    ? esc_attr($code)
                                    : '';
                                ?>"
                                id="purchase-code"
                                name="purchase-code"
                                class="large-text">
                            <small>
                                <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-"
                                    target="_blank">
                                    <?php esc_html_e('Where Is My Purchase Code?', 'wc-copper-integration'); ?>
                                </a>
                            </small>
                        </td>
                    </tr>
                </table>
                <p>
                    <input type="submit"
                        class="button button-primary"
                        value="<?php esc_attr_e('Verify', 'wc-copper-integration'); ?>"
                        name="verify">
                    <?php if ($code) { ?>
                        <input type="submit"
                            class="button button-primary"
                            value="<?php esc_attr_e('Unverify', 'wc-copper-integration'); ?>"
                            name="unverify">
                    <?php } ?>
                </p>
            </form>
        </div>
        <?php
    }
}
