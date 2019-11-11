    <table class="form-table">
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php esc_html_e( 'API Key', 'frmmlcmp' ) ?></label></td>
        	<td>
                <input type="text" name="frm_mlcmp_api_key" id="frm_mlcmp_api_key" value="<?php echo esc_attr( $frm_mlcmp_settings->get_api_key() ) ?>" class="frm_long_input" /><br/>
                <span class="frm_icon_font frm_mlcmp_resp"></span>
        	</td>
        </tr>
        
    </table>

<script type="text/javascript">
jQuery(document).ready(function($){
$('#frm_mlcmp_api_key').change(frmMlcmpCheckKey);
});

function frmMlcmpCheckKey( ) {
    var apikey = jQuery(this).val();
    if ( apikey == '' ) {
        jQuery('.frm_mlcmp_resp').html('');
        return;
    }

    jQuery.ajax({
        type:'POST',url:ajaxurl,dataType:'json',
        data:{action: 'frm_mlcmp_check_apikey', apikey: apikey, wpnonce: '<?php echo wp_create_nonce("frm_mlcmp") ?>'}, 
        success:function(res) {
            if ( 'error' in res ) {
            	jQuery('.frm_mlcmp_resp').html( ' '+ res.error ).addClass('frm_forbid_icon').removeClass('frm_check1_icon');
            } else {
                jQuery('.frm_mlcmp_resp').html(' Valid API key').addClass('frm_check1_icon').removeClass('frm_forbid_icon');
            }
        }
    });
}
</script>