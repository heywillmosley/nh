<?php require_once( getcwd() .'/../wp-load.php');

global $wp,$wpdb,$wp_rewrite,$wp_the_query,$wp_query;

// Check and see if Admin or Shop Manager
$user = wp_get_current_user();
if( in_array( 'administrator', (array) $user->roles ) || 
    in_array( 'shop_manager', (array) $user->roles ) || 
    in_array( 'aamrole_5515657c49534', (array) $user->roles ) || 
    in_array( 'aamrole_55156637f0312', (array) $user->roles ) ) {
    
    echo "I'm here";

  global $wpdb;
  
  
  $args = array(
    'number'     => $number,
    'orderby'    => 'title',
    'order'      => 'ASC',
    'hide_empty' => $hide_empty,
    'include'    => $ids
  );
  $product_categories = get_terms( 'product_cat', $args );
  $count = count($product_categories);
  if ( $count > 0 ){
    foreach ( $product_categories as $product_category ) {
        echo '<h2><a href="' . get_term_link( $product_category ) . '">' . $product_category->name . '</a></h2>';
        $args = array(
            'posts_per_page' => -1,
            'order' => 'ASC',
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    // 'terms' => 'white-wines'
                    'terms' => $product_category->slug
                )
            ),
            'post_type' => 'product',
            'orderby' => 'title,'
        );
        $products = new WP_Query( $args );
        
        
        // Start table
	echo '<table>
	  <tr>
	    <td><strong>Product Name</strong></td>
	    <td>Low Stock</td>
	    <td></td>
	    <td><strong>Online Count</strong></td>
	    <td><strong>Actual Count</strong></td>
	    <td><strong>Descp</strong></td>
	  </tr>';
        
        while ( $products->have_posts() ) {
            $products->the_post();
            $product_ID = get_the_ID();
            $product = wc_get_product($product_ID); 
            $low_stock = 50;
	    $replenish_min = $low_stock * 2;
	    $hr = "";
	   
	    if( $product_category->name == 'English Flower Essences' || $product_category->name == 'Flower Essences' || $product_category->name == 'Oligo') {
	      $stock_msg = "Made to order";
	    }
	    elseif(empty($product->stock_quantity) ) {
	      $stock_msg = "Not being tracked. Add qty to woo.";

	    }
	    elseif( $product->stock_quantity < 1 ) {
	      $stock_msg = "<span style='color: red'>OUT OF STOCK.<br/>REPLENISH AT LEAST $replenish_min NOW!</span>";
	      $hr = "<tr></tr>";
	    } 
	    elseif( $product->stock_quantity < 10 ) {
	      $stock_msg = "<span style='color: red'>VERY LOW STOCK.<br/>REPLENISH AT LEAST $replenish_min NOW!</span>";
	      $hr = "<tr></tr>";
	    } 
	    elseif( $product->stock_quantity < $low_stock ) {
	      $stock_msg = "<span style='color: red'>LOW STOCK.<br/>REPLENISH SOON.</span>";
	      $hr = "<tr></tr>";
	    } else {
	      $stock_msg = "";
	    }
	    
	    
	    echo $hr;
	    echo '<tr>';
	    echo   "<td valign='top'><a href='" . get_edit_post_link() . "'>" . $product->name . "</a></td>";
	    echo   "<td valign='top'>" . $stock_msg. "</td>";
	    echo   "<td valign='top'> </td>";
	    echo   "<td valign='top'>" . $product->stock_quantity . "</td>";
	    echo   "<td valign='top'>______</td>";
	    echo   "<td valign='top'>______</td>";
	    echo '</tr>';
	    echo $hr;
            
            
        } // end while
        
        echo '</table>';
        
    } //foreach ( $product_categories as $product_category )
    
  } // end if ( $count > 0 )
  
} // end if( in_array( 'administrator', (array) $user->roles )
    

// HELPERS
function p ( $array ) {
    echo '<pre>';
    print_r ( $array );
    echo '</pre>';

} // end prettyPre