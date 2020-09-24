<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
/**
 * Main XC Woo Cloud Class.
 *
 * @class XC_WOO_Order_Meta
 * @version	2.1
 */
if (!class_exists("XC_WOO_Order_Meta")):

class XC_WOO_Order_Meta  {
	
	/*
	* XC_WOO_Order_Meta Constructor
	*/
	public function __construct() {
		add_action('add_meta_boxes_shop_order', array($this, 'add_meta_boxes' ));
    }
	
	public function add_meta_boxes(){
		// create Print/Preview buttons
		add_meta_box('xc_woo-printer-box', __('Google Cloud Print', XC_WOO_CLOUD), array( $this, 'sidebar_box_content' ), 'shop_order', 'side', 'default');
	}
	
	public function _is_print_active( $type ){
		$enable = get_option("xc_woo_cloud_{$type}_enable","");
		$printers = get_option("xc_woo_cloud_{$type}_printers","");
		$selected = '';	
		if('yes' == $enable && !empty($printers) ){
			foreach($printers as $v){
				if(isset($v['enable']) && $v['enable'] == '1') $selected = 'yes';	
			}
		}
		if('yes' == $selected) return true;
		return false;
	}
	
	public function sidebar_box_content($post){
		global $post_id;
		$meta_box_actions = array();
		$documents        = array(
			'invoice' => __('Invoice',XC_WOO_CLOUD),
			'packing-slip' => __('Packing Slip',XC_WOO_CLOUD)
		);
		foreach ($documents as $k => $document) {
			if($this->_is_print_active($k)){
				$meta_box_actions[$k] = array(
					'url' => wp_nonce_url(admin_url("admin-ajax.php?action=xc_woo_printer_job&document_type={$k}&order_id=" . $post_id), 'xc_woo_printer_job'),
					'alt' => __("Print ", XC_WOO_CLOUD) . $document,
					'title' => __("Print ", XC_WOO_CLOUD) . $document,
					'target'=> "",
					'class'=> "xc_ajax_button"
				);
			}
		}
		
		
		foreach ($documents as $k => $document) {
			$key=$k."-preview";
			if($this->_is_print_active($k)){
				$meta_box_actions[$key] = array(
					'url' => wp_nonce_url(admin_url("admin-ajax.php?action=xc_woo_printer_preview&document_type={$k}&order_id=" . $post_id), 'xc_woo_printer_preview'),
					'alt' => __("Preview ", XC_WOO_CLOUD) . $document,
					'title' => __("Preview ",XC_WOO_CLOUD) . $document,
					'target'=> "_blank",
					"class" => ""
				);
			}
		}
		
		$meta_box_actions = apply_filters('xc_woo_printer_meta_box_actions', $meta_box_actions, $post_id);
		?>
		
		<ul class="xc_woo_printer-actions">
		  <?php
					foreach ($meta_box_actions as $document_type => $data) {
						printf('<li><a href="%1$s" class="button %5$s" alt="%2$s" target="%4$s" >%3$s</a></li>', $data['url'], $data['alt'], $data['title'], $data['target'],$data['class']);
					} 
		?>
		</ul>
		<?php		
	}
	
}
new XC_WOO_Order_Meta();
endif;
