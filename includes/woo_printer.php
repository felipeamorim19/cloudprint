<?php
use Dompdf\Dompdf;
use Dompdf\Options;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
/**
 * Main XC Woo Cloud Class.
 *
 * @class XC_WD
 * @version	1.0
 */
if (!class_exists("XC_WOO_Printer")):

class XC_WOO_Printer  {
		
		
	public $type;

	/**
	 * Document slug.
	 * @var String
	 */
	public $slug;

	/**
	 * Document title.
	 * @var string
	 */
	public $title;
	
	/**
	 * paper size.
	 * @var string
	 */
	public $size;
	
	/**
	 * paper orientation.
	 * @var string
	 */
	public $orientation;

	/**
	 * WC Order object
	 * @var object
	 */
	public $order;
	
	/**
	 * WC Order ID
	 * @var object
	 */
	public $order_id;

	/**
	 * Document settings.
	 * @var array
	 */
	public $settings;

	/**
	 * Core data for this object. Name value pairs (name + default value).
	 * @var array
	 */
	protected $data = array();

    protected static $_instance = null;
		

        /**
         * XC_WOO_CLOUD Constructor.
         */
        public function __construct($order_id) {
			
			$this->order_id = $order_id;
			//$this->type = $type;
			if(!empty($order_id)) $this->order = new WC_Order($this->order_id);
			else $this->order = new WC_Order();
			
			$this->settings = $this->get_settings();
			

            //add_action('init', array(&$this, 'init'));
			

        }

        /**
         * Main XC_WOO_CLOUD Instance.
         *
         * Ensures only one instance of XC_WOO_CLOUD is loaded or can be loaded.
         *
         * @return XC_WOO_CLOUD - Main instance.
         */
        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            } //is_null(self::$_instance)
            return self::$_instance;
        }	
		
		public function set_type($type){
			$this->type=$type;	
		}
		
		public function set_size($size){
			$this->size = $size;
			$this->settings['size'] = $size;
		}
		
		public function set_orientation($orientation){
			$this->orientation=$orientation;
			$this->settings['orientation'] = $orientation;
		}
		
		function get_settings(){
			$settings = array();
			 // default settings
			 $settings['header_logo'] = get_option('xc_woo_cloud_logo',"");
			 $settings['shop_name'] = get_option('xc_woo_cloud_shop_name',"");
			 $settings['shop_address'] = get_option('xc_woo_cloud_shop_address',"");
			 $settings['footer'] = get_option('xc_woo_cloud_footer',"");
			 $settings['size'] = get_option('xc_woo_cloud_paper_size',"A4");
			 $settings['orientation'] = get_option('xc_woo_cloud_paper_orientation',"portrait");
			 $settings['sample_order'] = get_option('xc_woo_cloud_sample_order',"");
			 $settings['payment_check'] = get_option('xc_woo_cloud_payment_check',array());
			 
			 
			 // invoice options
			 $settings['invoice_enable'] = get_option('xc_woo_cloud_invoice_enable',"");
			 $settings['invoice_printers'] = get_option('xc_woo_cloud_invoice_printers',"");
			 $settings['invoice_copies'] = get_option('xc_woo_cloud_invoice_copies',1);
			 $settings['invoice_show_shipping'] = get_option('xc_woo_cloud_invoice_show_shipping_address',"");
			 $settings['invoice_show_email'] = get_option('xc_woo_cloud_invoice_show_email',"");
			 $settings['invoice_show_phone'] = get_option('xc_woo_cloud_invoice_show_phone_number',"");	
			 $settings['invoice_shipping_billing_layout'] = get_option('xc_woo_cloud_invoice_shipping_billing_layout',"billing-shipping");	
			 $settings['invoice_show_shipping_method'] = get_option('xc_woo_cloud_invoice_show_shipping_method',"");
			 $settings['invoice_show_barcode'] = get_option('xc_woo_cloud_invoice_show_barcode',"");
			 
			 // Packing Slip options
			 $settings['packing-slip_enable'] = get_option('xc_woo_cloud_packing-slip_enable',"");
			 $settings['packing-slip_printers'] = get_option('xc_woo_cloud_packing-slip_printers',"");
			 $settings['packing-slip_copies'] = get_option('xc_woo_cloud_packing-slip_copies',1);
			 $settings['packing-slip_show_billing'] = get_option('xc_woo_cloud_packing-slip_show_billing_address',"");
			 $settings['packing-slip_show_payment_method'] = get_option('xc_woo_cloud_packing-slip_show_payment_method',"");
			 $settings['packing-slip_show_email'] = get_option('xc_woo_cloud_packing-slip_show_email',"");
			 $settings['packing-slip_show_phone'] = get_option('xc_woo_cloud_packing-slip_show_phone_number',"");	
			 $settings['packing-slip_shipping_billing_layout'] = get_option('xc_woo_cloud_packing-slip_shipping_billing_layout',"shipping-billing");	
			 $settings['packing-slip_show_barcode'] = get_option('xc_woo_cloud_packing-slip_show_barcode',"");	
			
			 $settings = apply_filters( 'xc_woo_cloud_print_settings', $settings, $this->type, $this->order );
			 
			 $settings['size_array'] = array(
			 								"A6" => array(0,0,297.64,419.53),
											"A7" => array(0,0,209.76,297.64),
											"A8" => array(0,0,147.40,209.76)
											);
											
			 if($this->size == ''){
					$this->size = $settings['size'];
					$this->orientation = $settings['orientation'];
			 }else{
				 $settings['size'] = $this->size;
				 $settings['orientation'] = $this->orientation;
			 }
			 return $settings;
				
		}
		
		function xc_woo_prepare_pdf($return = false){
			require_once XC_WOO_CLOUD_DIR . '/vendor/autoload.php';
			require_once XC_WOO_CLOUD_DIR . '/vendor/barcode/barcode.php';
			
			$dompdf_options = new Options();
			$dompdf_options->setdefaultFont('dejavu sans');
			$dompdf_options->setIsRemoteEnabled(true);
			$dompdf_options->setIsFontSubsettingEnabled(true);
			
			$html = $this->render_template( $this->locate_template_file( $this->type.".php" ) );
			$html = $this->wrap_html_content( $html );
			
			$dompdf = new Dompdf($dompdf_options);
			$dompdf->loadHtml($html);
			$dompdf->setPaper($this->size, $this->orientation);
			
			if(in_array($this->size, array("A6","A7","A8"))){ 
			
			$GLOBALS['xcbodyHeight'] = 0;

			$dompdf->setCallbacks(
			  array(
				'myCallbacks' => array(
				  'event' => 'end_frame', 'f' => function ($infos) {
					$frame = $infos["frame"];
					if (strtolower($frame->get_node()->nodeName) === "body") {
						$padding_box = $frame->get_padding_box();
						$GLOBALS['xcbodyHeight'] += $padding_box['h'];
					}
				  }
				)
			  )
			);
			
			
			$dompdf->loadHtml($html);
			$dompdf->render();
			unset($dompdf);
			
			$new_size = $this->settings['size_array'][$this->size];
			
			$new_size[3] = $GLOBALS['xcbodyHeight']+50;
			
			$dompdf = new Dompdf();
			$dompdf->set_paper($new_size,$this->orientation);
			$dompdf->loadHtml($html);
			
			}
			
			
			// Render the HTML as PDF
			$dompdf->render();
			if($return){
				$pdf_content = $dompdf->output();
				$path = $this->get_documents_path($this->type);
				$file_name = $this->order_id ."_".$this->type.'_'.$this->size.'_'.$this->orientation.".pdf";
				$file_path=$path['dir'].$file_name;
				file_put_contents($file_path, $pdf_content);
				return $file_path;				
			}else{			
				$dompdf->stream("dompdf_out.pdf", array("Attachment" => false));			
			}
		}
		
	
	public function wrap_html_content( $content ) {
		$html = $this->render_template( $this->locate_template_file( "html-document-wrapper.php" ), array(
				'content' => $content,
			)
		);
		return $html;
	}
	
	public function locate_template_file( $file ) {
		
		$template_path = get_stylesheet_directory()."/xc-woo-google-cloud-print";
		$file_path = "{$template_path}/{$file}";
		
		if(!file_exists($file_path)){
			$path = XC_WOO_CLOUD_DIR."/templates";
			$file_path = "{$path}/{$file}";	
		}

		$file_path = apply_filters( 'xc_woo_cloud_print_template_file', $file_path, $this->type, $this->order );

		return $file_path;
	}
	
	public function render_template( $file, $args = array() ) {

		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}
		ob_start();
		if (file_exists($file)) {
			include($file);
		}
		return ob_get_clean();
	}
	
	public function get_title(){
		return $this->get_shop_name()." ".$this->type;		
	}
	
	public function has_header_logo() {
		return !empty( $this->settings['header_logo'] );
	}
	
	public function get_header_logo_id() {
		if ( !empty( $this->settings['header_logo'] ) ) {
			return apply_filters( 'xc_woo_cloud_print_header_logo_id', $this->settings['header_logo'], $this );
		}
	}
	
	/**
	 * Show logo html
	 */
	public function header_logo() {
		if ($this->get_header_logo_id()) {
			$attachment_id = $this->get_header_logo_id();
			$company = $this->get_shop_name();
			if( $attachment_id ) {
				$attachment = wp_get_attachment_image_src( $attachment_id, 'full', false );
				
				$attachment_src = $attachment[0];
				$attachment_width = $attachment[1];
				$attachment_height = $attachment[2];

				$attachment_path = get_attached_file( $attachment_id );

				if ( apply_filters('xc_woo_cloud_print_use_path', false) && file_exists($attachment_path) ) {
					$src = $attachment_path;
				} else {
					$src = $attachment_src;
				}
				
				printf('<img src="%1$s" width="%2$d" height="%3$d" alt="%4$s" />', $src, $attachment_width, $attachment_height, esc_attr( $company ) );
			}
		}
	}
	
	public function get_shop_name() {
		$default = get_bloginfo( 'name' );
		return (!empty($this->settings['shop_name']))?$this->settings['shop_name']:$default;
	}
	public function shop_name() {
		echo $this->get_shop_name();
	}
	
	/**
	 * Return/Show shop/company address if provided
	 */
	public function get_shop_address() {
		return nl2br($this->settings['shop_address']);
	}
	public function shop_address() {
		echo $this->get_shop_address();
	}
	
	public function get_footer() {
		$footer = $this->settings[ 'footer' ];
		$order_id = $this->order_id;
		if ( $order_id) {
			$order = wc_get_order( $order_id );
			$placeholders = array();
			if ( is_a( $order, 'WC_Order' ) ) {
				$placeholders['{order_number}'] = $order->get_order_number();
				$placeholders['{billing_first_name}'] = $order->get_billing_first_name();
				$placeholders['{billing_last_name}'] = $order->get_billing_last_name();
				$placeholders['{order_total}'] = html_entity_decode(wc_price($order->get_total()));
				$placeholders['{order_date}'] = $order->get_date_created();
				$placeholders['{site_title}'] = $this->get_shop_name();
				$footer = strtr($footer, $placeholders);
			}
		}
		return nl2br($footer);
	}
	public function footer() {
		echo $this->get_footer();		
	}
	
	
	public function billing_address(){
		$address = $this->order->get_formatted_billing_address();
		$address = apply_filters( 'xc_woo_cloud_print_billing_address', $address, $this );
		echo $address;
	}
	
	
	
	
	
	public function order_number(){
		$number = method_exists( $this->order, 'get_order_number' ) ? $this->order->get_order_number() : '';	
		echo $number;
	}
	
	public function order_date(){
		if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
			$order_date= $this->order->date_created;
		}else{
			$order_date= $this->order->get_date_created();
		}
		$date_format = apply_filters('xc_woo_cloud_print_date_format',get_option( 'date_format' ));
		echo $date = $order_date->date_i18n( $date_format );
		//echo $mysql_date = $order_date->date( "Y-m-d H:i:s" );	
	}
	public function payment_method(){
		if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
			echo $this->order->payment_method_title;
		}else{
			echo $this->order->get_payment_method_title();
		}
	}
	
	public function get_order_items() {
		$items = $this->order->get_items();
		$data_list = array();
	
		if( sizeof( $items ) > 0 ) {
			foreach ( $items as $item_id => $item ) {
				// Array with data for the pdf template
				$data = array();

				// Set the item_id
				$data['item_id'] = $item_id;
				
				// Set the id
				$data['product_id'] = $item['product_id'];
				$data['variation_id'] = $item['variation_id'];

				// Set item name
				$data['name'] = $item['name'];
				
				// Set item quantity
				$data['quantity'] = $item['qty'];

				// Set the line total (=after discount)
				$data['line_total'] = $this->format_price( $item['line_total'] );
				$data['single_line_total'] = $this->format_price( $item['line_total'] / max( 1, abs( $item['qty'] ) ) );
				$data['line_tax'] = $this->format_price( $item['line_tax'] );
				$data['single_line_tax'] = $this->format_price( $item['line_tax'] / max( 1, abs( $item['qty'] ) ) );
				
				$line_tax_data = maybe_unserialize( isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '' );
				$data['tax_rates'] = $this->get_tax_rate( $item['tax_class'], $item['line_total'], $item['line_tax'], $line_tax_data );
				
				// Set the line subtotal (=before discount)
				$data['line_subtotal'] = $this->format_price( $item['line_subtotal'] );
				$data['line_subtotal_tax'] = $this->format_price( $item['line_subtotal_tax'] );
				$data['ex_price'] = $this->get_formatted_item_price( $item, 'total', 'excl' );
				$data['price'] = $this->get_formatted_item_price( $item, 'total' );
				$data['order_price'] = $this->order->get_formatted_line_subtotal( $item ); // formatted according to WC settings

				// Calculate the single price with the same rules as the formatted line subtotal (!)
				// = before discount
				$data['ex_single_price'] = $this->get_formatted_item_price( $item, 'single', 'excl' );
				$data['single_price'] = $this->get_formatted_item_price( $item, 'single' );

				// Pass complete item array
				$data['item'] = $item;
				
				// Get the product to add more info
				$product = $this->order->get_product_from_item( $item );
				
				// Checking fo existance, thanks to MDesigner0 
				if( !empty( $product ) ) {
					// Thumbnail (full img tag)
					
					$data['thumbnail'] = $this->get_thumbnail( $product );

					// Set item SKU
					$data['sku'] = $product->get_sku();
	
					// Set item weight
					$data['weight'] = $product->get_weight();
					
					// Set item dimensions
					//$data['dimensions'] = WC_Product::get_dimensions( $product );
				
					// Pass complete product object
					$data['product'] = $product;
				
				} else {
					$data['product'] = null;
				}
				
				
				// Set item meta
				if (function_exists('wc_display_item_meta')) { // WC3.0+
					$data['meta'] = wc_display_item_meta( $item, array(
						'echo'      => false,
					) );
				} else {
					if ( version_compare( WOOCOMMERCE_VERSION, '2.4', '<' ) ) {
						$meta = new \WC_Order_Item_Meta( $item['item_meta'], $product );
					} else { // pass complete item for WC2.4+
						$meta = new \WC_Order_Item_Meta( $item, $product );
					}
					$data['meta'] = $meta->display( false, true );
				}
				
					

				$data_list[$item_id] = apply_filters( 'xc_woo_cloud_print_order_item_data', $data, $this->order, $this->type );
			}
		}
		
		

		return apply_filters( 'xc_woo_cloud_print_order_items_data', $data_list, $this->order, $this->type );
	}
	
	public function format_price( $price, $args = array() ) {
		if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
			$formatted_price = woocommerce_price( $price );
		}else{
			$formatted_price = wc_price( $price );
		}

		return $formatted_price;
	}
	public function wc_price( $price, $args = array() ) {
		return $this->format_price( $price, $args );
	}
	
	public function get_formatted_item_price ( $item, $type, $tax_display = '' ) {
		if ( ! isset( $item['line_subtotal'] ) || ! isset( $item['line_subtotal_tax'] ) ) {
			return;
		}

		$divide_by = ($type == 'single' && $item['qty'] != 0 )?abs($item['qty']):1; //divide by 1 if $type is not 'single' (thus 'total')
		if ( $tax_display == 'excl' ) {
			$item_price = $this->format_price( ($this->order->get_line_subtotal( $item )) / $divide_by );
		} else {
			$item_price = $this->format_price( ($this->order->get_line_subtotal( $item, true )) / $divide_by );
		}

		return $item_price;
	}
	
	public function get_tax_rate( $tax_class, $line_total, $line_tax, $line_tax_data = '' ) {
		// first try the easy wc2.2+ way, using line_tax_data
		if ( !empty( $line_tax_data ) && isset($line_tax_data['total']) ) {
			$tax_rates = array();

			$line_taxes = $line_tax_data['subtotal'];
			foreach ( $line_taxes as $tax_id => $tax ) {
				if ( isset($tax) && $tax !== '' ) {
					$tax_rates[] = $this->get_tax_rate_by_id( $tax_id ) . ' %';
				}
			}

			$tax_rates = implode(' ,', $tax_rates );
			return $tax_rates;
		}

		if ( $line_tax == 0 ) {
			return '-'; // no need to determine tax rate...
		}

		if ( version_compare( WOOCOMMERCE_VERSION, '2.1' ) >= 0 && !apply_filters( 'xc_woo_cloud_print_calculate_tax_rate', false ) ) {
			// WC 2.1 or newer is used
			$tax = new \WC_Tax();
			$taxes = $tax->get_rates( $tax_class );

			$tax_rates = array();

			foreach ($taxes as $tax) {
				$tax_rates[$tax['label']] = round( $tax['rate'], 2 ).' %';
			}

			if (empty($tax_rates)) {
				// one last try: manually calculate
				if ( $line_total != 0) {
					$tax_rates[] = round( ($line_tax / $line_total)*100, 1 ).' %';
				} else {
					$tax_rates[] = '-';
				}
			}

			$tax_rates = implode(' ,', $tax_rates );
		} else {
			// Backwards compatibility/fallback: calculate tax from line items
			if ( $line_total != 0) {
				$tax_rates = round( ($line_tax / $line_total)*100, 1 ).' %';
			} else {
				$tax_rates = '-';
			}
		}
		
		return $tax_rates;
	}
	
	public function get_tax_rate_by_id( $rate_id ) {
		global $wpdb;
		$rate = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d;", $rate_id ) );
		return (float) $rate;
	}

	/**
	 * Returns a an array with rate_id => tax rate data (array) of all tax rates in woocommerce
	 * @return array  $tax_rate_ids  keyed by id
	 */
	public function get_tax_rate_ids() {
		global $wpdb;
		$rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates" );

		$tax_rate_ids = array();
		foreach ($rates as $rate) {
			// var_dump($rate->tax_rate_id);
			// die($rate);
			$rate_id = $rate->tax_rate_id;
			unset($rate->tax_rate_id);
			$tax_rate_ids[$rate_id] = (array) $rate;
		}

		return $tax_rate_ids;
	}
	
	public function get_thumbnail_id ( $product ) {
		global $woocommerce;

		//$product_id = WCX_Product::get_id( $product );
		
		$product_id = $product->id;

		if ( has_post_thumbnail( $product_id ) ) {
			$thumbnail_id = get_post_thumbnail_id ( $product_id );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $product_id ) ) && has_post_thumbnail( $parent_id ) ) {
			$thumbnail_id = get_post_thumbnail_id ( $parent_id );
		} else {
			$thumbnail_id = false;
		}

		return $thumbnail_id;
	}

	/**
	 * Returns the thumbnail image tag
	 * 
	 * uses the internal WooCommerce/WP functions and extracts the image url or path
	 * rather than the thumbnail ID, to simplify the code and make it possible to
	 * filter for different thumbnail sizes
	 *
	 * @access public
	 * @return string
	 */
	public function get_thumbnail ( $product ) {
		// Get default WooCommerce img tag (url/http)
		$size = apply_filters( 'xc_woo_cloud_print_thumbnail_size', 'shop_thumbnail' );
		$thumbnail_img_tag_url = $product->get_image( $size, array( 'title' => '' ) );
		
		// Extract the url from img
		preg_match('/<img(.*)src(.*)=(.*)"(.*)"/U', $thumbnail_img_tag_url, $thumbnail_url );
		$thumbnail_url = array_pop($thumbnail_url);
		// remove http/https from image tag url to avoid mixed origin conflicts
		$contextless_thumbnail_url = ltrim( str_replace(array('http://','https://'), '', $thumbnail_url ), '/' );

		// convert url to path
		if ( defined('WP_CONTENT_DIR') && strpos( WP_CONTENT_DIR, ABSPATH ) !== false ) {
			$forwardslash_basepath = str_replace('\\','/', ABSPATH);
			$contextless_site_url = str_replace(array('http://','https://'), '', trailingslashit(get_site_url()));
		} else {
			// bedrock e.a
			$forwardslash_basepath = str_replace('\\','/', WP_CONTENT_DIR);
			$contextless_site_url = str_replace(array('http://','https://'), '', trailingslashit(WP_CONTENT_URL));
		}
		$thumbnail_path = str_replace( $contextless_site_url, trailingslashit( $forwardslash_basepath ), $contextless_thumbnail_url);
		
		// fallback if thumbnail file doesn't exist
		if (apply_filters('xc_woo_cloud_print_use_path', true) && !file_exists($thumbnail_path)) {
			if ($thumbnail_id = $this->get_thumbnail_id( $product ) ) {
				$thumbnail_path = get_attached_file( $thumbnail_id );
			}
		}

		// Thumbnail (full img tag)
		if (apply_filters('xc_woo_cloud_print_use_path', true) && file_exists($thumbnail_path)) {
			// load img with server path by default
			$thumbnail = sprintf('<img width="90" height="90" src="%s" class="attachment-shop_thumbnail wp-post-image">', $thumbnail_path );
		} else {
			// load img with http url when filtered
			$thumbnail = $thumbnail_img_tag_url;
		}

		// die($thumbnail);
		return $thumbnail;
	}
	
	public function get_woocommerce_totals() {
		// get totals and remove the semicolon
		$totals = apply_filters( 'xc_woo_cloud_print_raw_order_totals', $this->order->get_order_item_totals(), $this->order );
		
		// remove the colon for every label
		foreach ( $totals as $key => $total ) {
			$label = $total['label'];
			$colon = strrpos( $label, ':' );
			if( $colon !== false ) {
				$label = substr_replace( $label, '', $colon, 1 );
			}		
			$totals[$key]['label'] = $label;
		}
		
		return apply_filters( 'xc_woo_cloud_print_woocommerce_totals', $totals, $this->order, $this->type );
	}
	
	public function get_shipping_notes() {
			
			if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
				$shipping_notes = wpautop( wptexturize($this->order->customer_note ) );
			}else{
				$shipping_notes = wpautop( wptexturize($this->order->get_customer_note()));
			}

		return apply_filters( 'xc_woo_cloud_print_shipping_notes', $shipping_notes, $this );
	}
	public function shipping_notes() {
		echo $this->get_shipping_notes();
	}
	
	public function get_billing_email() {
		if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
			$billing_email = $this->order->billing_email;
		}else{
			$billing_email = $this->order->get_billing_email();
		}
		
		
		return apply_filters( 'xc_woo_cloud_print_billing_email', $billing_email, $this );
	}
	
	
	public function billing_email() {
		echo $this->get_billing_email();
	}
	
	/**
	 * Return/Show billing phone
	 */
	public function get_billing_phone() {
		
		if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
			$billing_phone = $this->order->billing_phone;
		}else{
			$billing_phone = $this->order->get_billing_phone();
		}
		return apply_filters( 'xc_woo_cloud_print_billing_phone', $billing_phone, $this );
	}
	
	public function billing_phone() {
		echo $this->get_billing_phone();
	}
	
	/**
	 * Return/Show shipping address
	 */
	public function get_shipping_address() {
		if ( $address = $this->order->get_formatted_shipping_address() ) {
			// regular shop_order
			$address = apply_filters( 'xc_woo_cloud_print_shipping_address', $address, $this );
		} else {
			// no address
			$address = apply_filters( 'xc_woo_cloud_print_shipping_address', __('N/A', XC_WOO_CLOUD ), $this );
		}

		return $address;
	}
	public function shipping_address() {
		echo $this->get_shipping_address();
	}
	
	
	
	
	/**
	 * Output template styles
	 */
	public function template_styles() {
		$css = apply_filters( 'xc_woo_cloud_print_template_styles_file', $this->locate_template_file( "style.css" ) );

		ob_start();
		if (file_exists($css)) {
			include($css);
		}
		$css = ob_get_clean();
		$css = apply_filters( 'xc_woo_cloud_print_template_styles', $css, $this );
		
		echo $css;
	}
	
	public function get_shipping_method() {
		$shipping_method_label = __( 'Shipping method', XC_WOO_CLOUD );
		$shipping_method = __( $this->order->get_shipping_method(), 'woocommerce' );
		return apply_filters( 'xc_woo_cloud_print_shipping_method', $shipping_method, $this );
	}
	public function shipping_method() {
		echo $this->get_shipping_method();
	}
	
	public function get_barcode(){
		$order_id = $this->order_id;
		$path = $this->get_documents_path("barcode");
		$image = $order_id."_barcode.png";
		$filepath = $path['dir'].$image;
		$order_number = method_exists( $this->order, 'get_order_number' ) ? $this->order->get_order_number() : '';
		$order_number = apply_filters('xc_woo_cloud_print_barcode_order_id', $order_number, $this->order);
		
		xcbarcode( $filepath, $order_number, "30");
		
		
		return apply_filters("xc_woo_cloud_print_barcode_file", $path['url'].$image, $order_id, $path, $image,$filepath);
	}
	
	public function get_documents_path($type){
			$uploads = wp_upload_dir();
			$path=array("dir","url");
			switch($type){
				case "invoice":
					$path['dir'] = $uploads['basedir']."/xc_files/xc_invoices/";
					$path['url'] = $uploads['baseurl']."/xc_files/xc_invoices/";
				break;
				case "packing-slip":
					$path['dir'] = $uploads['basedir']."/xc_files/xc_packing_slips/";
					$path['url'] = $uploads['baseurl']."/xc_files/xc_packing_slips/";
				break;
				case "barcode":
					$path['dir'] = $uploads['basedir']."/xc_files/xc_barcodes/";
					$path['url'] = $uploads['baseurl']."/xc_files/xc_barcodes/";
				break;	
				default:
					$path['dir'] = $uploads['basedir']."/xc_files/xc_extradata/";
					$path['url'] = $uploads['baseurl']."/xc_files/xc_extradata/";
				break;
			}			
		return $path;
	}
	
	
	
		
	}
endif;
