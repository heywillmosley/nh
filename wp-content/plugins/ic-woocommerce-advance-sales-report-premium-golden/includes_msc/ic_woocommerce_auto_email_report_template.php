<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">        
        <title><?php echo $report_title;?></title>		
	</head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background:#F1F1F1;">
    	<br />
    	<center>
        	<h2><?php echo $report_title;?></h2>
        	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
            	<tr>
                	<td align="center" valign="top">
                    	<table border="0" cellpadding="0" cellspacing="0" width="800" style="border:1px solid #DADADA; background:#fff;">
                        	<?php echo $the_summary_html;?>
                        </table>
                    </td>
                </tr>
            </table>
        </center>
        <br>
    </body>
</html>