<?php
/**
* Plugin Name: Woocommerce Google Cloud Print
* Plugin URI: http://wp.xperts.club/googlecloudprint
* Description: This plugin can send new orders in your store to be automatically and instantly to your google cloud printer. Save time and money, and fulfill orders faster!
* Version: 2.7
* Author: Xperts Club
* Author URI: http://wp.xperts.club/
* Requires at least: 4.4
* WC requires at least: 3.0.0
* WC tested up to: 3.7 *
* Tested up to: 5.2 *
* Text Domain: xc_woo_cloud_print
* Domain Path: /languages/
**/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
} //!defined('ABSPATH')
if (!defined('XC_WOO_CLOUD'))
    define('XC_WOO_CLOUD', 'xc_woo_cloud_print');
if (!defined('XC_WOO_CLOUD_FILE'))
    define('XC_WOO_CLOUD_FILE', __FILE__ );	
if (!defined('XC_WOO_CLOUD_BASE_NAME'))
    define('XC_WOO_CLOUD_BASE_NAME', plugin_basename(__FILE__));
if (!defined('XC_WOO_CLOUD_DIR'))
    define('XC_WOO_CLOUD_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
if (!defined('XC_WOO_CLOUD_URL'))
    define('XC_WOO_CLOUD_URL', untrailingslashit(plugins_url('/', __FILE__)));
if (!defined('XC_WOO_CLOUD_VERSION'))
    define('XC_WOO_CLOUD_VERSION', '2.7');
	

register_deactivation_hook( __FILE__, 'deactivate_xc_woo_cloud' );

function deactivate_xc_woo_cloud(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
	Xc_Woo_Cloud_Deactivator::deactivate();	
}

	
/**
* Main XC Woo Cloud Class.
*
* @class XC_WOO_CLOUD
* @version    2.0
*/
if (!class_exists("XC_WOO_CLOUD")):
    final class XC_WOO_CLOUD
    {
        protected static $_instance = null;
        private $XC_WOO_CLOUD_Settings;
        /**        
        * XC_WOO_CLOUD Constructor.
        */
        public function __construct()
        {
            add_action('init', array(
                &$this,
                'init'
            ));
						
            add_action('wp_ajax_xc_woo_printer_job', array( $this, 'xc_woo_printer_job'));
			
			add_action( 'xc_woo_cloud_print_custom_styles', array(&$this,'xc_woo_cloud_print_custom_styles'),10,2);
			
			add_action('wp_ajax_xc_woo_printer_preview', array( $this, 'xc_woo_printer_preview' ));
			
			add_action('woocommerce_checkout_order_processed', array(&$this, 'xc_order_processed'),PHP_INT_MAX,3);
			add_action('woocommerce_payment_complete', array($this, 'xc_order_payment_complete'),PHP_INT_MAX);
			add_action('xc_woo_order_print_orders_external', array($this, 'xc_woo_print_order'));
			add_action('xc_woo_order_print_cron', array($this, 'xc_woo_order_print_cron'));
			
			add_action('xc_woo_order_print_extra_data',array(&$this,'xc_woo_order_print_extra_data'),10,2);
			add_action('xc_woo_print_other_data',array(&$this,'xc_woo_print_other_data'),10,3);
			
			add_filter("xc_woo_cloud_print_order_upon_process_paymnt_methods", array(&$this, 'xc_woo_cloud_print_order_upon_process_paymnt_methods'),10,1);
			
			add_filter('xc_woo_cloud_print_document_title', array($this, 'xc_woo_cloud_print_order_id_to_order_number'), 10, 3);
			
			
        }
        /**        
        * Main XC_WOO_CLOUD Instance.
        *        
        * Ensures only one instance of XC_WOO_CLOUD is loaded or can be loaded.        
        *        
        * @return XC_WOO_CLOUD - Main instance.        
        */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            } //is_null(self::$_instance)
            return self::$_instance;
        }
        /**        
        * Hook into actions and filters.        
        */
        public function init()
        {
            load_plugin_textdomain(XC_WOO_CLOUD, false, dirname(plugin_basename(__FILE__)) . '/languages/');
            if (is_admin()):
                add_action('admin_enqueue_scripts', array(
                    $this,
                    'admin_enqueue_scripts'
                ));
				
				$this->create_files();
				
				add_action('admin_notices', array( &$this, 'admin_message' ));	
				
            endif;
            require_once XC_WOO_CLOUD_DIR . '/includes/options.php';
			require_once XC_WOO_CLOUD_DIR . '/includes/woo_printer_logs.php';
			require_once XC_WOO_CLOUD_DIR . '/includes/woo_order_meta.php';
            require_once XC_WOO_CLOUD_DIR . '/includes/woo_settings.php';
			require_once XC_WOO_CLOUD_DIR . '/includes/functions.php';
			require_once XC_WOO_CLOUD_DIR . '/includes/licence.php';
			new XC_WOO_Printer_Licence(XC_WOO_CLOUD,XC_WOO_CLOUD_VERSION);
            $XC_WOO_CLOUD_Settings       = new XC_WOO_CLOUD_Settings();
            $this->XC_WOO_CLOUD_Settings = $XC_WOO_CLOUD_Settings;
        }
		
		public function admin_message(){
			
			$warning = $this->xc_is_printes_selected();
			if(!empty($warning)){
				?>
                <div class="notice notice-error is-dismissible">
		<?php echo $warning;?>
	</div>
                <?php	
			}
			
			if(isset($_GET['message']) && $_GET['message'] == 1 && (isset($_GET['page']) && $_GET['page'] == "wc-settings")){
			?>
            <div class="updated">
            	<p><strong><?php echo __( 'Success', XC_WOO_CLOUD ) ; ?></strong> : <?php echo __( 'Sample print has been sent to your printer', XC_WOO_CLOUD ) ; ?></p>
            </div>
            <?php
			}
		}
		
		
        function admin_enqueue_scripts()
        {
            wp_enqueue_style('xc_print_admin_styles', XC_WOO_CLOUD_URL . '/assets/css/admin-style.css',array(),XC_WOO_CLOUD_VERSION);
            wp_enqueue_script('xc_print_admin_scripts', XC_WOO_CLOUD_URL . '/assets/js/admin-script.js', array('jquery'), XC_WOO_CLOUD_VERSION);	
			
        }
		
		function create_files(){
			$upload_dir      = wp_upload_dir();
			$files = array(
				array(
					'base' 		=> $upload_dir['basedir'] . '/xc_files',
					'file' 		=> 'index.html',
					'content' 	=> '',
				),
				array(
					'base' 		=> $upload_dir['basedir'] . '/xc_files/xc_invoices',
					'file' 		=> 'index.html',
					'content' 	=> '',
				),
				array(
					'base' 		=> $upload_dir['basedir'] . '/xc_files/xc_packing_slips',
					'file' 		=> 'index.html',
					'content' 	=> '',
				),
				array(
					'base' 		=> $upload_dir['basedir'] . '/xc_files/xc_barcodes',
					'file' 		=> 'index.html',
					'content' 	=> '',
				),
				array(
					'base' 		=> $upload_dir['basedir'] . '/xc_files/xc_extradata',
					'file' 		=> 'index.html',
					'content' 	=> '',
				),
			
			);	
			
			foreach ( $files as $file ) {
				if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
					if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
						fwrite( $file_handle, $file['content'] );
						fclose( $file_handle );
					}
				}
			}
			
			
		}
		
		function xc_is_printes_selected(){
			$invoice_enable = get_option('xc_woo_cloud_invoice_enable',"");
			$invoice_printers = get_option('xc_woo_cloud_invoice_printers',"");	
			$packingslip_enable = get_option('xc_woo_cloud_packing-slip_enable',"");
			 $packingslip_printers = get_option('xc_woo_cloud_packing-slip_printers',"");
			 $message = '';
			 if('yes' == $invoice_enable){
				 if(empty($invoice_printers)){
					$message = sprintf(__("<li>Please select printer for <a href='%s'>Invoice printing</a></li>", XC_WOO_CLOUD), admin_url('admin.php?page=wc-settings&tab=xc_woo_cloud&section=invoice')); 
				 }else{
					$enable = 'no';
					foreach($invoice_printers as $v){
						if(isset($v['enable']) && $v['enable'] == '1') $enable = 'yes';	
					}
					if($enable == 'no'){
						$message = sprintf(__("<li>Please select printer for <a href='%s'>Invoice printing</a></li>", XC_WOO_CLOUD), admin_url('admin.php?page=wc-settings&tab=xc_woo_cloud&section=invoice')); 
					}
					
				 }
			 }
			 if('yes' == $packingslip_enable){
				 if(empty($packingslip_printers)){
					$message .= sprintf(__("<li>Please select printer for <a href='%s'>Packing Slip printing</a></li>", XC_WOO_CLOUD), admin_url('admin.php?page=wc-settings&tab=xc_woo_cloud&section=packing-slip')); 
				 }else{
					$enable = 'no';
					foreach($packingslip_printers as $v){
						if(isset($v['enable']) && $v['enable'] == '1') $enable = 'yes';	
					}
					if($enable == 'no'){
						$message .= sprintf(__("<li>Please select printer for <a href='%s'>Packing Slip printing</a></li>", XC_WOO_CLOUD), admin_url('admin.php?page=wc-settings&tab=xc_woo_cloud&section=packing-slip')); 
					}
				 }
			 }
			 if(!empty($message)){
				$message = '<p>You have enabled automatic printing but did not select default printers. To make sure your invoices and package slips print automatically, please do the following: </p><ul>'.$message.'</ul>'; 
			 }
			 return $message;
		}
		
        function xc_print($data, $printjobtitle, $printer, $settings = array())
        {
            $opts          = get_option('xc_woo_cloud_print_options', array());
            $printerid     = $printer['printer_id'];
            $contenttype   = "application/pdf";
			$ticket = array("version"=>"1.0","print"=>array());
			$ticket['print']['page_orientation'] = array("type"=>strtoupper($printer['orientation']));
			$ticket['print']['fit_to_page'] = array("type"=>"NO_FITTING");
			
			$ticket = apply_filters("xc_woo_cloud_printer_ticket", $ticket, $printer, $data, $printjobtitle, $settings);
			
			$ticket = json_encode($ticket);
			
			$printerid = apply_filters("xc_woo_cloud_print_order_printer",$printerid,$printer);
			
            $post_fields   = array(
                'printerid' => $printerid,
                'title' => $printjobtitle,
				'ticket' => $ticket,
                'contentTransferEncoding' => 'base64',
                'content' => base64_encode($data), // encode file content as base64
                'contentType' => $contenttype
            );
			global $xc_woo_printer_logs;
			$post_fields = apply_filters("xc_woo_cloud_printer_post_fields",$post_fields,$printer);
			
            $url           = "https://www.google.com/cloudprint/submit?printerid=" . urlencode($printerid) . "&output=json";
			for($i=1; $i<=$printer['copies']; $i++){
            	$ret           = $this->XC_WOO_CLOUD_Settings->process_request($url, $post_fields, $opts);
				$this->add_log('printer-response', array('ret' => $ret, 'printjobtitle' => $printjobtitle, 'printer' => $printer));
				
			}
			return $ret;
        }

        
        function xc_woo_printer_job()
        {
            $order_id      = $_REQUEST['order_id'];
            $document_type = $_REQUEST['document_type'];
            require_once XC_WOO_CLOUD_DIR . '/includes/woo_printer.php';
            $XC_WOO_Printer = new XC_WOO_Printer($order_id);
			$settings = $XC_WOO_Printer->get_settings();			
			$document_title = "Order " . $order_id . " " . $document_type;
			$document_title = apply_filters('xc_woo_cloud_print_document_title',$document_title,$order_id,$document_type);
			$XC_WOO_Printer->set_type($document_type);
			if(!empty($settings["{$document_type}_printers"])){
				foreach($settings["{$document_type}_printers"] as $k=>$v){
					if(isset($v['enable']) && $v['enable'] == '1'){
						$printer  = array();
						$printer['printer_id'] = $k;
						$printer['size'] = $v['size'];
						$printer['orientation'] = $v['orientation'];
						$printer['copies'] = $v['copies'];
						
						$path = $XC_WOO_Printer->get_documents_path($document_type);
						$file_name = $order_id ."_".$document_type.'_'.$printer['size'].'_'.$printer['orientation'].".pdf";
						$file_path=$path['dir'].$file_name;

						$XC_WOO_Printer->set_size($printer['size']);
						$XC_WOO_Printer->set_orientation($printer['orientation']);
						$file           = $XC_WOO_Printer->xc_woo_prepare_pdf(true);
							
						$data           = file_get_contents($file);
						
						$note = apply_filters("xc_woo_cloud_print_order_note", __("{$document_type} sent to google cloud print manually",XC_WOO_CLOUD), $order_id, $document_type, $document_title,$printer);						
					    $this->xc_woo_cloud_print_add_order_note($order_id,$note);
						
						$ret = $this->xc_print($data, $document_title,$printer, $settings);
						do_action("xc_woo_cloud_print_manually_printed",$order_id,$document_type,$printer,$ret);
						echo ($ret);
					}
				}
			}
			die();
        }
		
		function xc_woo_printer_preview(){
			if(isset($_REQUEST['order_id'])){
				$order_id= 	$_REQUEST['order_id'];
			}else{
				$order_id      = get_option('xc_woo_cloud_sample_order',"");
				$order = wc_get_order($order_id);
				if(!$order){
					echo 'Please select sample order';
					die();	
				}
			}
            $document_type = $_REQUEST['document_type'];
			require_once XC_WOO_CLOUD_DIR . '/includes/woo_printer.php';
            $XC_WOO_Printer = new XC_WOO_Printer($order_id);
			$settings = $XC_WOO_Printer->get_settings();
			$XC_WOO_Printer->set_type($document_type);
			$XC_WOO_Printer->set_size($settings['size']);
			$XC_WOO_Printer->set_orientation($settings['orientation']);			
			$return = (isset($_REQUEST['t']) && $_REQUEST['t'] =="print")? true : false;
            $file = $XC_WOO_Printer->xc_woo_prepare_pdf($return);
			if($return){ 
				$opts          = get_option('xc_woo_cloud_print_options', array());
				$printerid     = $opts['printer'];
				$printer  = array();
				$printer['printer_id'] = $printerid;
				$printer['size'] = $settings['size'];
				$printer['orientation'] = $settings['orientation'];
				$printer['copies'] = $settings["{$document_type}_copies"];
				$document_title = "Order " . $order_id . " " . $document_type;
				$document_title = apply_filters('xc_woo_cloud_print_document_title',$document_title,$order_id,$document_type);
				$data = file_get_contents($file);
				$this->xc_print($data, $document_title,$printer, $settings);
				wp_redirect(admin_url("/admin.php?page=wc-settings&tab=xc_woo_cloud&section=".$document_type."&message=1"));
			}
            die();
		}
		
		function get_available_payment_methods(){
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			$methods = array();
			foreach($available_gateways as $v){
				$methods[] = $v->id;
			}
			return $methods;
		}
		
		public function xc_woo_cloud_print_order_upon_process_paymnt_methods($payment_methods){
			$payment_check = get_option('xc_woo_cloud_payment_check',array());
			if(sizeof($payment_check) > 0){
				$payment_methods = array_diff($payment_methods, $payment_check);	
			}
			return $payment_methods;
		}
		
		
		function xc_order_processed( $order_id, $posted_data, $order ){
			
			$payment_method = is_callable(array($order, 'get_payment_method')) ? $order->get_payment_method() : $order->payment_method;
			$payment_methods = $this->get_available_payment_methods();
			$print_order_upon_processed_methods = apply_filters("xc_woo_cloud_print_order_upon_process_paymnt_methods",$payment_methods);
			
			if(in_array($payment_method,$print_order_upon_processed_methods)) {
				$this->add_log('order-processed',array('payment_method' => $payment_method, 'order_id' => $order_id, 'order' => $order ));
				$this->xc_woo_print_order($order_id);				
			}else{
				$this->add_log('order-processed-skipped',array('payment_method' => $payment_method, 'order_id' => $order_id, 'order' => $order, 'print_order_upon_processed_methods' => $print_order_upon_processed_methods ));	
			}
		}
		
		function xc_order_payment_complete( $order_id ){
			if (apply_filters('xc_woo_cloud_print_orders_on_payment_complete', true, $order_id)) {
				$this->add_log('payment-complete',array('order_id' => $order_id));
				$this->xc_woo_print_order($order_id);
			}
		}
		
		function xc_woo_print_order( $order_id ){
			$cron = apply_filters('xc_woo_cloud_cron_print_orders', true);
			if ($cron) {
				$this->xc_woo_cron_print($order_id);				
			} else {
				$this->add_log('cron-job-skipped',array('order_id' => $order_id));
				$this->xc_woo_print_now($order_id);
			}
		}
		
		private function xc_woo_cron_print($order_id) {
			$time = apply_filters("xc_woo_cloud_print_cron_delay",time());
			$this->add_log('cron-job-added',array('order_id' => $order_id, 'cron_time'=>$time));
			$cron_queue = get_option("xc_woo_cloud_print_cron_queue");
			$cron_queue = (is_array($cron_queue))?$cron_queue:array();
			$cron_queue[$order_id] = $time;
			update_option('xc_woo_cloud_print_cron_queue',$cron_queue);
			wp_schedule_single_event($time, 'xc_woo_order_print_cron', array($order_id));
		}
		
		function xc_woo_order_print_cron( $order_id ){
			$this->add_log('cron-job-called',array('order_id' => $order_id));
			$this->xc_woo_print_now($order_id);
		}
		
		private function xc_woo_print_now( $order_id ){
			require_once XC_WOO_CLOUD_DIR . '/includes/woo_printer.php';
			$XC_WOO_Printer = new XC_WOO_Printer($order_id);
			$settings = $XC_WOO_Printer->get_settings();
			$cron_queue = get_option("xc_woo_cloud_print_cron_queue");
			if(is_array($cron_queue) && isset($cron_queue[$order_id])){
				unset($cron_queue[$order_id]);
				update_option('xc_woo_cloud_print_cron_queue',$cron_queue);
			}
			
			
            if($settings['invoice_enable'] == "yes" || $settings['packing-slip_enable'] == "yes"){    
				$xc_is_invoice_printed = get_post_meta($order_id,"xc_is_invoice_printed",true);
            	if ($settings['invoice_enable'] == 'yes' && empty($xc_is_invoice_printed)) {
                	$document_type  = 'invoice';
					$document_title = "Order " . $order_id . " " . $document_type;
					$document_title = apply_filters('xc_woo_cloud_print_document_title',$document_title,$order_id,$document_type);
                	$XC_WOO_Printer->set_type($document_type);
					$invoice_printers_selected = 0;
					if(!empty($settings['invoice_printers'])){
						update_post_meta($order_id,"xc_is_invoice_printed","yes");
						foreach($settings['invoice_printers'] as $k=>$v){
							if(isset($v['enable']) && $v['enable'] == '1'){
								$invoice_printers_selected++;
								$printer  = array();
								$printer['printer_id'] = $k;
								$printer['size'] = $v['size'];
								$printer['orientation'] = $v['orientation'];
								$printer['copies'] = $v['copies'];
								
								$path = $XC_WOO_Printer->get_documents_path($document_type);
								$file_name = $order_id ."_".$document_type.'_'.$printer['size'].'_'.$printer['orientation'].".pdf";
								$file_path=$path['dir'].$file_name;
								if(!file_exists($file_path)){
									$XC_WOO_Printer->set_size($printer['size']);
									$XC_WOO_Printer->set_orientation($printer['orientation']);
									$file           = $XC_WOO_Printer->xc_woo_prepare_pdf(true);
								}else{
									$file = $file_path;	
								}
								$data           = file_get_contents($file);
								
								$note = apply_filters("xc_woo_cloud_print_order_note", __("Invoice sent to google cloud print automatically",XC_WOO_CLOUD), $order_id, $document_type, $document_title,$printer);						
							    $this->xc_woo_cloud_print_add_order_note($order_id,$note);
								$this->xc_print($data, $document_title,$printer, $settings);
							}
						}
						
					}
					
					if(empty($invoice_printers_selected)){
						$this->add_log('no-printer',array('order_id' => $order_id, 'document_type' => $document_type));	
					}
					
            	}else{
					$this->add_log('print-disabled',array('order_id' => $order_id, 'document_type' => "Invoice"));	
				}
				
				$xc_is_slip_printed = get_post_meta($order_id,"xc_is_slip_printed",true);
            	if ($settings['packing-slip_enable'] == 'yes' && empty($xc_is_slip_printed)) {
                	$document_type  = 'packing-slip';
					$document_title = "Order " . $order_id . " " . $document_type;
					$document_title = apply_filters('xc_woo_cloud_print_document_title',$document_title,$order_id,$document_type);
                	$XC_WOO_Printer->set_type($document_type);
					$packing_slip_printers_selected = 0;
					if(!empty($settings['packing-slip_printers'])){
						update_post_meta($order_id,"xc_is_slip_printed","yes");
						foreach($settings['packing-slip_printers'] as $k=>$v){
							if(isset($v['enable']) && $v['enable'] == '1'){
								$packing_slip_printers_selected++;
								$printer  = array();
								$printer['printer_id'] = $k;
								$printer['size'] = $v['size'];
								$printer['orientation'] = $v['orientation'];
								$printer['copies'] = $v['copies'];
								
								$path = $XC_WOO_Printer->get_documents_path($document_type);
								$file_name = $order_id ."_".$document_type.'_'.$printer['size'].'_'.$printer['orientation'].".pdf";
								$file_path=$path['dir'].$file_name;
								if(!file_exists($file_path)){
									$XC_WOO_Printer->set_size($printer['size']);
									$XC_WOO_Printer->set_orientation($printer['orientation']);
									$file           = $XC_WOO_Printer->xc_woo_prepare_pdf(true);
								}else{
									$file = $file_path;	
								}
								$data           = file_get_contents($file);
								$note = apply_filters("xc_woo_cloud_print_order_note", __("Packing slip sent to google cloud print automatically",XC_WOO_CLOUD), $order_id, $document_type, $document_title,$printer);						
								$this->xc_woo_cloud_print_add_order_note($order_id,$note);
								
								$this->xc_print($data, $document_title,$printer, $settings);
							}
						}
						
						
					}
					if(empty($packing_slip_printers_selected)){
						$this->add_log('no-printer',array('order_id' => $order_id, 'document_type' => $document_type));	
					}
            	}else{
					$this->add_log('print-disabled',array('order_id' => $order_id, 'document_type' => "Packing-slip"));		
				}
			}else{
				$this->add_log('disabled',array('order_id' => $order_id));			
			}
			
			$cron_queue = get_option("xc_woo_cloud_print_cron_queue");
			if(!empty($cron_queue)){
				$next_print = '';
				if(is_array($cron_queue)){
					foreach($cron_queue as $order_id1=>$time){
						if(($time + 300) < time() && empty($next_print)){							
							$xc_is_invoice_printed = get_post_meta($order_id1,"xc_is_invoice_printed",true);
							$xc_is_slip_printed = get_post_meta($order_id1,"xc_is_slip_printed",true);
							if(empty($xc_is_invoice_printed) && empty($xc_is_slip_printed)){
								$next_print = $order_id1;	
							}else{
								unset($cron_queue[$order_id1]);
							}
						}
					}
				}
				if(!empty($next_print)){
					unset($cron_queue[$next_print]);
					update_option('xc_woo_cloud_print_cron_queue',$cron_queue);
					$this->xc_woo_print_now($next_print);	
				}
			}
			
		}
		
		function xc_woo_order_print_extra_data($order_id,$document_type){
			
			$is_printed = get_post_meta($order_id,"is_printed".$document_type,true);
			if(empty($is_printed)){
				require_once XC_WOO_CLOUD_DIR . '/includes/woo_printer.php';
				$XC_WOO_Printer = new XC_WOO_Printer($order_id);
				$settings = $XC_WOO_Printer->get_settings();
				$XC_WOO_Printer->set_type($document_type);
				$XC_WOO_Printer->set_size($settings['size']);
				$XC_WOO_Printer->set_orientation($settings['orientation']);	
				
				$opts          = get_option('xc_woo_cloud_print_options', array());
				$printerid     = $opts['printer'];
				$printer  = array();
				$printer['printer_id'] = $printerid;
				$printer['size'] = $settings['size'];
				$printer['orientation'] = $settings['orientation'];
				$printer['copies'] = $settings["{$document_type}_copies"];
				$document_title = "Order " . $order_id . " " . $document_type;
				$document_title = apply_filters('xc_woo_cloud_print_document_title',$document_title,$order_id,$document_type);
				$data = file_get_contents($file);
				$this->xc_print($data, $document_title,$printer, $settings);
				update_post_meta($order_id,"is_printed".$document_type,"yes");
			}
		}
		
		function xc_woo_print_other_data($data,$title,$printer){
			$this->xc_print($data, $title,$printer);
		}
		
		function xc_woo_cloud_print_add_order_note( $order_id, $note ){
			$order = wc_get_order(  $order_id );
			// Add the note
			$order->add_order_note( $note );			
			// Save the data
			$order->save();
			
			$this->add_log('order-note',array('order_id' => $order_id, 'note' => $note, 'order' => $order));			
			
		}
		
		function add_log($type, $arr){
			if('yes' != get_option('xc_woo_cloud_enable_logs')){
				return;	
			}
			try {
			global $xc_woo_printer_logs;
			$order_id = '';
			switch($type){
				case "printer-response":
					$ret = json_decode($arr['ret']);
					$message = $ret->message;
					$title = $arr['printjobtitle'];
					$data = $ret;		
				break;	
				case "order-processed":
					$message = 'Order status changed to Processed, and proceed to print  Payment gateway is '.$arr['payment_method'];
					$title = 'Order : '.$arr['order_id'];
					$data = $arr['order'];
					$order_id = $arr['order_id'];
				break;
				case "order-processed-skipped":
					$message = 'Order status changed to Processed, and skipped printing because  '.$arr['payment_method']. " selected only print after payment.";
					$title = 'Order : '.$arr['order_id'];
					$data = $order;
					$order_id = $arr['order_id'];
				break;
				case "payment-complete":
					$order_id = $arr['order_id'];
					$order = wc_get_order(  $order_id );
					$payment_method = is_callable(array($order, 'get_payment_method')) ? $order->get_payment_method() : $order->payment_method; 
					$message = 'Order payment completed, and proceed to print. Payment gateway : '.$payment_method;
					$title = 'Order : '.$arr['order_id'];
					$data = $order;
				break;
				case "cron-job-skipped":
					$message = "Cron skipped and called direct print.";
					$title = "Order : ".$arr['order_id'];
					$data = array();
					$order_id = $arr['order_id'];
				break;
				case "cron-job-added":
					$message = "Cron job added for printing. cron time : ".$arr['cron_time'];
					$title = "Order : ".$arr['order_id'];
					$data = array();
					$order_id = $arr['order_id'];
				break;
				case "cron-job-called":
					$message = "Cron job called ";
					$title = "Order : ".$arr['order_id'];
					$data = array();
					$order_id = $arr['order_id'];
				break;
				case "no-printer":
					$message = $arr['document_type']." printing skipped because no printer selected";
					$title = "Order : ".$arr['order_id'];
					$data = array();
					$order_id = $arr['order_id'];
				break;
				case "print-disabled":
					$message = $arr['document_type']." printing skipped because option disabled";
					$title = "Order : ".$arr['order_id'];
					$data = array();
					$order_id = $arr['order_id'];
				break;
				case "disabled":
					$message = " Printing skipped because options Disabled";
					$title = "Order : ".$arr['order_id'];
					$data = array();
					$order_id = $arr['order_id'];
				break;
				case "order-note":
					$message = $arr['note'];
					$title = "Order : ".$arr['order_id'];
					$data = $arr['order'];
					$order_id = $arr['order_id'];
				break;				
			}
			
			if(!empty($order_id)){
				$title = $this->xc_woo_cloud_print_order_id_to_order_number($title,$order_id);
			}
			
			$message = apply_filters("xc_woo_cloud_print_log_message",$message,$type,$arr);
			$title = apply_filters("xc_woo_cloud_print_log_title",$title,$type,$arr);			
			
			$xc_woo_printer_logs->add_log($message,$title,$type,$data);
			}catch(Exception $e) {
				
			}
		}
		
		function xc_woo_cloud_print_order_id_to_order_number($document_title,$order_id,$document_type=''){
			$order = wc_get_order(  $order_id );
			$order_number = $order->get_order_number();
			$document_title = str_replace($order_id,$order_number,$document_title);
			return $document_title;	
		}
		
		function xc_woo_cloud_print_custom_styles( $type, $obj ){
			if($obj->settings['size'] == "A5"){
			?>
            @page {
                margin-top: 0.5cm;
                margin-bottom: 2cm;
                margin-left: 1cm;
                margin-right: 1cm;
            }
            <?php	
			}else if(in_array($obj->settings['size'],array("A6","A7","A8"))){
				?>
				@page {
					margin-top: 10px;
					margin-bottom: 0.5cm;
					margin-left: 0.3cm;
					margin-right: 0.3cm;
				}
                body{
                font-size:8pt;
                }
                h1 {
                    font-size:10pt;
                    margin: 1mm 0;
                    padding:0px;
                }

h2 {
	font-size: 9pt;
}

h3, h4 {
	font-size: 8pt;
}
li,
ul {
	margin-bottom: 0.5em;
}
p + p {
	margin-top: 1em;
}

table.head {
	margin-bottom: 5mm;
}


td.header img {
	max-height:40px;
	width: auto;
    margin:auto;
    display:block;
}

td.header {
	font-size: 14pt;
	font-weight: 700;
}

td.shop-info {
	width: 60%;
    font-size:8pt;
}
.invoice .shipping-address {
	width: 50%;
    
    margin-bottom:5mm;
}

.packing-slip .billing-address {
	width: 50%;
    
    margin-bottom:5mm;
}
td.address{
     width: 50%;
    
    margin-bottom:5mm;
}
td.order-data {
	width: 100%;
    display:block;
}
table.order-data-addresses{
margin-bottom:10px;
}
table.order-data-table{
margin-bottom:15px;
}
#footer{
font-size:8pt;
bottom: -0.3cm;
height:0.3cm
}

                <?php	
			}
		}
		
    }
    new XC_WOO_CLOUD();
endif;