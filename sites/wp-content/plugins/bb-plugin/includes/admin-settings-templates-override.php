<?php if ( is_network_admin() ) : ?>
<h4><?php _e( 'Override Core Templates', 'fl-builder' ); ?></h4>
<p><?php _e( 'Enter the ID of a site on the network whose templates should override core builder templates. Leave this field blank if you do not wish to override core templates.', 'fl-builder' ); ?></p>
<p>
	<input type="text" name="fl-templates-override" value="<?php if ( $site_id ) echo $site_id; ?>" size="5" />
</p>
<?php elseif ( ! is_multisite() ): ?>
<h4><?php _e( 'Override Core Templates', 'fl-builder' ); ?></h4>
<p><?php _e( 'Use this setting to override core builder templates with your templates.', 'fl-builder' ); ?></p>
<p>
	<label>
		<input type="checkbox" name="fl-templates-override" value="1" <?php checked( $site_id, 1 ); ?> />
		<span><?php _e( 'Override Core Templates', 'fl-builder' ); ?></span>
	</label>
</p>
<?php endif; ?>