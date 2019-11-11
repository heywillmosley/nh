<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$wwof_license_email = get_option( WWOF_OPTION_LICENSE_EMAIL );
$wwof_license_key = get_option( WWOF_OPTION_LICENSE_KEY );
?>
<div id="wws_settings_wwof" class="wws_license_settings_page_container">

    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="wws_wwof_license_email"><?php _e( 'License Email' , 'woocommerce-wholesale-order-form' ); ?></label>
                </th>
                <td class="forminp forminp-text">
                    <input type="text" id="wws_wwof_license_email" class="regular-text ltr" value="<?php echo $wwof_license_email; ?>"/>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="wws_wwof_license_key"><?php _e( 'License Key' , 'woocommerce-wholesale-order-form' ); ?></label>
                </th>
                <td class="forminp forminp-text">
                    <input type="text" id="wws_wwof_license_key" class="regular-text ltr" value="<?php echo $wwof_license_key; ?>"/>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <input type="button" id="wws_save_btn" class="button button-primary" value="<?php _e( 'Save Changes' , 'woocommerce-wholesale-order-form' ); ?>"/>
        <span class="spinner"></span>
    </p>

</div><!--#wws_settings_wwof-->