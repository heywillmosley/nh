<?php 
if( ! in_array('hide_header', $html_data['blocks']) ) {
    $header = $date_string;
    echo '<h2>'.$header.'</h2>';
} if( in_array('sales', $html_data['blocks']) ) {
    echo '<div><h3>'.__('Sales', 'sales-report-for-woocommerce').'</h3>'.wc_price($total_price).'</div>';
} if( in_array('order_count', $html_data['blocks']) ) {
    echo '<div><h3>'.__('Order count', 'sales-report-for-woocommerce').'</h3>'.$order_count.'</div>';
} if( in_array('products', $html_data['blocks']) ) {
    echo '<div><h3>'.__('Products', 'sales-report-for-woocommerce').'</h3>';
    foreach($ready_products as $product) {
        echo '<p style="font-size: 1em;padding: 0;margin: 0;">', $product['quantity'], ' x <strong>', $product['name'],
        ( ( empty( $product['sku'] ) ) ? '' : ' (' . $product['sku'] . ')' ),'</strong></p>';
    }
    echo '</div>';
}
?>
