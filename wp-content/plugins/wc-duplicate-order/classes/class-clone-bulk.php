<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Clone Order Functionality.
 *
 * @class    CloneOrder
 * @version  1.0.1
 * @category Class
 * @author   Jamie Gill
 */

class CloneBulk extends CloneOrder {
	
	public $original_order_id;
	
	function __construct() {
		
    	add_action('admin_footer-edit.php', array($this, 'custom_bulk_select'));
    	add_action('load-edit.php', array($this, 'custom_bulk_action'));
    	
    }
    
    public function custom_bulk_select() {
 
		global $post_type;
		 
		if($post_type == 'shop_order') {
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('<option>').val('duplicate').text('<?php _e('Duplicate')?>').appendTo("select[name='action']");
				});
			</script>
		<?php
		}
	}
	

	public function custom_bulk_action() {
	
		// Thanks to J Lo for the tutorial on bulk actions 
		// https://blog.starbyte.co.uk/woocommerce-new-bulk-action/
		
		global $typenow;
		$post_type = $typenow;
		
		if($post_type == 'shop_order') {
		
			$wp_list_table = _get_list_table('WP_Posts_List_Table');
			$action = $wp_list_table->current_action();
			
			$allowed_actions = array("duplicate");
			
			if(!in_array($action, $allowed_actions)) return;
			
			if(isset($_REQUEST['post'])) {
				$orderids = array_map('intval', $_REQUEST['post']);
			}
			
			switch($action) {
				case "duplicate":
			
				foreach( $orderids as $orderid ) {
					$this->clone_order($orderid);
				}

				break;
				default: return;
			}
			
			$sendback = admin_url( "edit.php?post_type=$post_type&duplicate=success" );
			wp_redirect($sendback);
			
			exit();
		}
	
	}
	   
}

new CloneBulk;

