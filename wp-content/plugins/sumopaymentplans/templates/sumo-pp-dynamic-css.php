<?php 
$prefix = _sumo_pp()->prefix ;
?>
mark{
    font: 13px arial, sans-serif;
    text-align: center;
    display:table-cell;
    border-radius: 15px;
    padding:4px 6px 4px 6px;
}
mark.<?php echo "{$prefix}pending" ; ?>{
    background-color:#<?php echo '727272'; ?>;
    color:white;
}
mark.<?php echo "{$prefix}await_aprvl" ; ?>{
    background-color:#<?php echo 'FFFF00'; ?>;
    color:000;
}
mark.<?php echo "{$prefix}completed" ; ?>{
    background-color:#<?php echo '1CBFED'; ?>;
    color:white;
}
mark.<?php echo "{$prefix}in_progress" ; ?>{
    background-color:#<?php echo '008000'; ?>;
    color:white;
}
mark.<?php echo "{$prefix}overdue" ; ?>{
    background-color:#<?php echo '1E1C00'; ?>;
    color:white;
}
mark.<?php echo "{$prefix}cancelled" ; ?>{
    background-color:#<?php echo 'EF381C'; ?>;
    color:white;
}
mark.<?php echo "{$prefix}failed" ; ?>{
    background-color:#<?php echo '99270B'; ?>;
    color:white;
}
li.processing  div.note_content{
    background-color:<?php echo 'F79400'; ?> !important;
}
li.success  div.note_content{
    background-color:<?php echo '259E12'; ?> !important;
}
li.pending  div.note_content{
    background-color:<?php echo '727272'; ?> !important;
}
li.failure  div.note_content{
    background-color: <?php echo 'EF381C'; ?> !important;
}
li .note_content::after {
    border-style: solid !important;
    border-width:10px 10px  0 0  !important;
    bottom: -10px !important;
    content: "" !important;
    display: block !important;
    height: 0 !important;
    left: 20px !important;
    position: absolute !important;
    width: 0 !important;
}
li.processing .note_content::after {
    border-color: <?php echo 'f79400'; ?> transparent !important;
}
li.success .note_content::after {
    border-color: <?php echo '259e12'; ?> transparent !important;
}
li.pending .note_content::after {
    border-color: <?php echo '727272'; ?> transparent !important;
}
li.failure .note_content::after {
    border-color: <?php echo 'ef381c'; ?> transparent !important;
}
._alert_box {
    border-radius:20px;
    font-family:Tahoma,Geneva,Arial,sans-serif;
    font-size:14px;
    padding:10px 10px 10px 20px;
    margin:20px;
}
._alert_box span {
    font-weight:bold;
}
.error {
    background:#ffecec;
    border:2px solid #f5aca6;
}
._success {
    background:#e9ffd9;
    border:2px solid #a6ca8a;
}
.warning {
    background:#fff8c4;
    border:2px solid #f2c779;
}
.notice {
    background:#e3f7fc;
    border:2px solid #8ed9f6;
}
.thumb {
    padding-bottom: 1.5em;
    text-align: left;
    width: 38px;
}
.name{
    border-bottom: 1px solid #f8f8f8;
    line-height: 1.5em;
    padding: 1.5em 1em 1em;
    text-align: left;
    vertical-align: middle;
}
.item {
    -moz-user-select: none;
    background: #f8f8f8 none repeat scroll 0 0;
    font-weight: 400;
    padding: 1em;
    text-align: left;
 }
 .view {
    white-space: nowrap;
    text-align:right;
 }
 .payment_item{
    font-weight: 400;
    text-align:right !important;
 }
 tr:last-child td {
    border-bottom: 1px solid #dfdfdf;
 }