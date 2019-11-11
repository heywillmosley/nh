<?php //echo '<pre>'.print_r($week_days, true).'</pre>'; ?>
<table class="berocket_days_table" style="width:100%;border-collapse: collapse;table-layout: auto;">
    <tr style="height:36px;">
        <th class="day_row" rowspan="2" style="width:120px;border:1px solid #555;"><?php _e('Date', 'sales-report-for-woocommerce')?></th>
        <th style="border:1px solid #555;" colspan="5"><?php _e('Price', 'sales-report-for-woocommerce')?></th>
    </tr>
    <tr style="height:36px;">
        <td class="price_row" style="width:100px;border:1px solid #555;text-align:right;"><?php echo wc_price(0); ?></td>
        <td style="border:1px solid #555;border-right: 1px dashed orange;text-align:right;"><?php echo wc_price($max_price * 0.25); ?></td>
        <td style="border:1px solid #555;border-left:0;border-right: 1px dashed orange;text-align:right;"><?php echo wc_price($max_price * 0.5); ?></td>
        <td style="border:1px solid #555;border-left:0;border-right: 1px dashed orange;text-align:right;"><?php echo wc_price($max_price * 0.75); ?></td>
        <td style="border:1px solid #555;border-left:0;text-align:right;"><?php echo wc_price($max_price); ?></td>
    </tr>
    <?php 
    foreach($week_days as $week_day) {
        $width = $week_day['total_price'] / $max_price * 100;
        $global_width = $width * 4;
        $widths = array();
        for($i = 0;$i < 4; $i++) {
            $widths[$i] = ($global_width > 100 ? 100 : ($global_width < 0 ? 0 : $global_width));
            $global_width = $global_width - 100;
        }
        ?>
        <tr style="min-height:36px;height:36px;">
            <td style="border:1px solid #555;font-size:16px;line-height:16px;"><?php echo $week_day['string']; ?></td>
            <td style="border:1px solid #555;font-size:16px;line-height:16px;"><?php echo wc_price($week_day['total_price']); ?></td>
            <?php
            foreach($widths as $width_i => $width_local) {
                echo '<td height="36px" style="height:36px;padding:0;border:1px solid #555;'.($width_i < 3 ? 'border-right: 1px dashed orange;' : '').($width_i > 0 ? 'border-left: 0;' : '').'">'.($width_local > 0 ? '<div class="orange_divs" style="background-color:orange;width:'.$width_local.'%;min-height:36px;height:100%;"></div>' : '').'</td>';
            }
            ?>
        </tr>
        <?php
    }
    ?>
</table>
