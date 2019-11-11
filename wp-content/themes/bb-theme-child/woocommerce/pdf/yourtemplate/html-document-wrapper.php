<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php echo $this->get_title(); ?></title>
	<style type="text/css"><?php $this->template_styles(); ?></style>
	<style type="text/css"><?php do_action( 'wpo_wcpdf_custom_styles', $this->get_type(), $this ); ?></style>
	<style>
	    .wm-invoice {
	        max-width: 800px;
	        font-size: 11px;
	        margin: 0 auto;
	        line-height: 1.1;
	    }
	    .wm-invoice #footer {
	        position: relative;
	        text-align: left !important;
	    }
	    
	</style>
</head>
<body class="wm-invoice <?php echo $this->get_type(); ?>">
<?php echo $content; ?>
</body>
</html>