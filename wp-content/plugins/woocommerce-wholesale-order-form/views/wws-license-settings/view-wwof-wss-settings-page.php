<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$wwof_license_email           = is_multisite() ? get_site_option( WWOF_OPTION_LICENSE_EMAIL ) : get_option( WWOF_OPTION_LICENSE_EMAIL );
$wwof_license_key             = is_multisite() ? get_site_option( WWOF_OPTION_LICENSE_KEY ) : get_option( WWOF_OPTION_LICENSE_KEY );
$wwof_license_expiration_date = is_multisite() ? get_site_option( WWOF_LICENSE_EXPIRED ) : get_option( WWOF_LICENSE_EXPIRED );

$display = $wwof_license_expiration_date ? 'table-row' : 'none';
?>
<div id="wws_settings_wwof" class="wws_license_settings_page_container">

    <table class="form-table">
        <tbody>
            <tr valign="top" id="wws_wwof_license_expired_notice" style="background-color: #fff; border-left: 4px solid #dc3232; display: <?php echo $display; ?>">
                <th scope="row" class="titledesc">
                    <label style="display: inline-block; padding-left: 10px;"><?php _e( 'License Expired' , 'woocommerce-wholesale-order-form' ); ?></label>
                </th>
                <td class="forminp">
                    <p><?php echo sprintf( __( 'The entered license was purchased over 12 months ago and expired on <b id="wwof-license-expiration-date">%1$s</b>.<br/> To continue receiving support & updates please <b><a href="%2$s" target="_blank">click here to renew your license</a>.</b>' , 'woocommerce-wholesale-order-form' ) , date( 'Y-m-d' , $wwof_license_expiration_date ) , 'https://wholesalesuiteplugin.com/my-account/licenses/' ); ?></p>
                </td>
            </tr>

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