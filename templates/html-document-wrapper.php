<?php
/**
 * HTML Wraper for both invoice and packing-slip templates
 *
 * This template can be overridden by copying it to yourtheme/xc-woo-google-cloud-print/html-document-wrapper.php.
 *
 *
 * @author 		XpertsClub
 * @package 	WoocommerceGoogleCloudPrint/Templates
 * @version     1.5
 */
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php echo $this->get_title(); ?></title>
	<style type="text/css"><?php $this->template_styles(); ?></style>
	<style type="text/css"><?php do_action( 'xc_woo_cloud_print_custom_styles', $this->type, $this ); ?></style>
</head>
<body class="<?php echo $this->type; ?>">
<?php echo $content; ?>
</body>
</html>