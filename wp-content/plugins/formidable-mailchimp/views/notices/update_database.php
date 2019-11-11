<div class="error" id="frmmlcmp_install_message">
	<p><?php
	printf( __( 'Your Formidable MailChimp database needs to be updated.%1$sPlease deactivate and reactivate the plugin or %2$sUpdate Now%3$s', 'frmmlcmp' ),
		'<br/>',
		'<a id="frmmlcmp_install_link" href="javascript:frmmlcmp_install_now()">',
		'</a>' ); ?>
	</p>
</div>

<script type="text/javascript">
	function frmmlcmp_install_now(){
		jQuery('#frmmlcmp_install_link').replaceWith('<img src="<?php echo esc_url_raw( $url ) ?>/images/wpspin_light.gif" alt="<?php esc_attr_e( 'Loading&hellip;' ); ?>" />');
		jQuery.ajax({type:'POST',url:"<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ) ?>",data:'action=frmmlcmp_install',
			success:function(msg){jQuery("#frmmlcmp_install_message").fadeOut('slow');}
		});
	}
</script>