<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit ; // Exit if accessed directly
}

$payment_id    = isset( $_GET[ 'payment-id' ] ) ? $_GET[ 'payment-id' ] : $payment_id ;
$payment_type  = get_post_meta( $payment_id , '_payment_type' , true ) ;
$product_price = floatval( get_post_meta( $payment_id , '_product_price' , true ) ) ;
$product_qty   = absint( get_post_meta( $payment_id , '_product_qty' , true ) ) ;

do_action( 'sumopaymentplans_before_view_payment_table' , $payment_id ) ;
?>

<table class="payment_details">
    <tr class="payment_status">
        <td><b><?php _e( 'Payment Status' , _sumo_pp()->text_domain ) ?></b></td>
        <td>:</td>
        <td>
            <?php
            $payment_status = _sumo_pp_get_payment_status( $payment_id ) ;
            printf( '<mark class="%s"/>%s</mark>' , $payment_status[ 'name' ] , esc_attr( $payment_status[ 'label' ] ) ) ;
            ?>
        </td>
    </tr>    
    <tr class="payment_product">
        <td><b><?php _e( 'Payment Product ' , _sumo_pp()->text_domain ) ; ?></b></td>
        <td>:</td>
        <td>
            <?php
            echo _sumo_pp_get_formatted_payment_product_title( $payment_id ) ;
            ?>
        </td>
    </tr>    
    <tr class="payment_plan">
        <td><b><?php _e( 'Payment Plan ' , _sumo_pp()->text_domain ) ; ?></b></td>
        <td>:</td>
        <td>
            <?php
            if ( 'payment-plans' === $payment_type ) {
                echo get_post( get_post_meta( $payment_id , '_plan_id' , true ) )->post_title ;
            } else {
                echo 'N/A' ;
            }
            ?>
        </td>
    </tr>
    <tr class="payment_start_date">
        <td><b><?php _e( 'Payment Start Date ' , _sumo_pp()->text_domain ) ; ?></b></td>
        <td>:</td>
        <td><?php echo _sumo_pp_get_date_to_display( get_post_meta( $payment_id , '_payment_start_date' , true ) ) ; ?></td>
    </tr>
    <tr class="payment_next_payment_date">
        <td><b><?php _e( 'Payment Next Payment Date ' , _sumo_pp()->text_domain ) ; ?></b></td>
        <td>:</td>
        <td>
            <?php
            if ( $next_payment_date = get_post_meta( $payment_id , '_next_payment_date' , true ) ) {
                echo _sumo_pp_get_date_to_display( $next_payment_date ) ;
            } else {
                echo '--' ;
            }
            ?>
        </td>
    </tr>
    <tr class="payment_end_date">
        <td><b><?php _e( 'Payment End Date ' , _sumo_pp()->text_domain ) ; ?></b></td>
        <td>:</td>
        <td>
            <?php
            if ( $payment_end_date = get_post_meta( $payment_id , '_payment_end_date' , true ) ) {
                echo _sumo_pp_get_date_to_display( $payment_end_date ) ;
            } else {
                echo '--' ;
            }
            ?>
    </tr>
    <tr class="initial_payment_amount">
        <td><b><?php _e( 'Initial Payment Amount ' , _sumo_pp()->text_domain ) ; ?></b></td> 
        <td>:</td>
        <td>
            <?php
            if ( 'pay-in-deposit' === $payment_type ) {
                echo wc_price( get_post_meta( $payment_id , '_deposited_amount' , true ) ) . ' x' . $product_qty ;
            } else {
                if ( 'fixed-price' === get_post_meta( $payment_id , '_plan_price_type' , true ) ) {
                    echo wc_price( get_post_meta( $payment_id , '_initial_payment' , true ) ) . ' x' . $product_qty ;
                } else {
                    echo wc_price( (floatval( get_post_meta( $payment_id , '_initial_payment' , true ) ) * $product_price) / 100 ) . ' x' . $product_qty ;
                }
            }
            ?>
        </td>
    </tr>
</table>
<h6><?php _e( 'Payment Schedule' , _sumo_pp()->text_domain ) ; ?></h6>
<?php
_sumo_pp_get_payment_orders_table( $payment_id , array (
    'class'       => 'widefat wc_input_table _sumo_pp_footable' ,
    'custom_attr' => 'data-sort=false data-filter=#filter data-page-size=10 data-page-previous-text=prev data-filter-text-only=true data-page-next-text=next' ,
    'page'        => 'frontend' ,
) ) ;
?>
<div class="pagination pagination-centered"></div>
<table class="payment_activities">
    <tr> 
        <td><h6><?php _e( 'Activity Logs ' , _sumo_pp()->text_domain ) ; ?></h6></td>
    </tr>
    <tr>
        <td>
            <?php
            if ( $payment_notes = _sumo_pp_get_payment_notes( array (
                'payment_id' => $payment_id ,
                    ) )
            ) {
                foreach ( $payment_notes as $index => $note ) :
                    if ( $index < 3 ) {
                        echo '<style type="text/css">.default_notes' . $index . '{display:block;}</style>' ;
                    } else {
                        echo '<style type="text/css">.default_notes' . $index . '{display:none;}</style>' ;
                    }

                    switch ( ! empty( $note->meta[ 'comment_status' ][ 0 ] ) ? $note->meta[ 'comment_status' ][ 0 ] : '' ) :
                        case 'success':
                            ?>
                            <div class="_alert_box _success default_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                            <?php
                            break ;
                        case 'pending':
                            ?>
                            <div class="_alert_box warning default_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                            <?php
                            break ;
                        case 'failure':
                            ?>
                            <div class="_alert_box error default_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                            <?php
                            break ;
                        default :
                            ?>
                            <div class="_alert_box notice default_notes<?php echo $index ; ?>"><span><?php echo $note->content ; ?></span></div>
                        <?php
                    endswitch ;
                endforeach ;

                if ( ! empty( $index ) && $index >= 3 ) {
                    ?>
                    <a data-flag="more" id="prevent_more_notes" style="cursor: pointer;"> <?php _e( 'Show More' , _sumo_pp()->text_domain ) ; ?></a>
                    <?php
                }
            } else {
                ?>
                <div class="_alert_box notice">
                    <span><?php _e( 'No Activities Yet.' , _sumo_pp()->text_domain ) ; ?></span>
                </div>
                <?php
            }
            ?>
        </td>
    </tr>
</table>
<?php
do_action( 'sumopaymentplans_after_view_payment_table' , $payment_id ) ;

