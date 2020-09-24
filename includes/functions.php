<?php
if(!function_exists('get_xc_cloud_printers')){
	function get_xc_cloud_printers(){
		$XC_WOO_CLOUD_Settings = new XC_WOO_CLOUD_Settings();
		$printers = $XC_WOO_CLOUD_Settings->get_printers();
		return $printers;
	}
}

function xc_woo_cloud_print_get_options() {
	$section = new XC_WOO_Settings();
	$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );
	$options = array();
	foreach ( $subsections as $subsection ) {
		if($subsection == '') $key = 'general'; else $key = $subsection;
		foreach ( $section->get_settings( $subsection ) as $value ) {
			if ( isset( $value['id'] ) ) {
				$options[$key][]=array("id"=>$value['id'], 'name' => $value['name'], 'value' => get_option($value['id']));
			}
		}
	}
	return $options;
}