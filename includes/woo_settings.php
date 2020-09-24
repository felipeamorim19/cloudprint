<?php
/**
 * Woocommerce Settings
 */
class XC_WOO_Settings 
{
    /**
     * Holds the values to be used in the fields callbacks
     */
	 
	 
	 private $id;
	 
	 private $label;
	 
	 private $options;
	 
	 /**
     * Start up
     */
	 public function __construct()
    {	
		$this->id    = 'xc_woo_cloud';
		$this->label = _x( 'Cloud Print Options', 'Settings tab label', XC_WOO_CLOUD );
		
		
		add_filter( 'woocommerce_settings_tabs_array', array( &$this,"add_settings_tab"), 50 );
		add_action( 'woocommerce_settings_'. $this->id, array( &$this,"settings_tab"));
        add_action( 'woocommerce_update_options_'. $this->id, array( &$this,"update_settings") );
		
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		
		add_action( 'woocommerce_admin_field_image', array(&$this,'output_image_field') );
		
		//add_action( 'admin_print_footer_scripts',  array(&$this,'admin_add_wysiwyg_custom_field_textarea'), 99 );
		
		add_action( 'woocommerce_admin_field_texteditor', array(&$this,'output_texteditor_field') );
		
		add_action( 'woocommerce_admin_field_xc_printer_selector', array(&$this, 'xc_printer_selector_field'));
		
		add_action( 'woocommerce_admin_field_xc_system_status', array(&$this, 'xc_system_status_field'));
		
		
		
		
		
	}
		
	/**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs[$this->id] = $this->label;
        return $settings_tabs;
    }
	
		public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}
	
	public function get_sections() {
		$sections = array(
			'' => __( 'General options', XC_WOO_CLOUD ),
			'invoice' => __( 'Invoice options', XC_WOO_CLOUD ),
			'packing-slip' => __( 'Packing Slip options', XC_WOO_CLOUD ),
			'system-status' => __( 'System Status', XC_WOO_CLOUD ),
		);
		
		$sections = apply_filters("xc_woo_cloud_print_settings_sections",$sections);
		
		return $sections;
	}
	
	
	
	
	/**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public function settings_tab() {
		global $current_section;
        woocommerce_admin_fields( $this->get_settings($current_section) );
    }
	
	/**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public function update_settings() {
		global $current_section;
        woocommerce_update_options( $this->get_settings($current_section) );
    }
	
	public function paper_sizes(){
		$data = array(
			"A1"=>"A1 (594 x 841 mm)",
			"A2"=>"A2 (420 x 594 mm)",
			"A3"=>"A3 (297 x 420 mm)",
			"A4"=>"A4 (210 x 297 mm)",
			"A5"=>"A5 (148 x 210 mm)",
			"A6"=>"A6 (105 x 148 mm)",
			"A7"=>"A7 (74 x 105 mm)",
			"A8"=>"A8 (52 x 74 mm)",
			"LETTER" => "Letter (216 x 279 mm)",
		);	
		$data = apply_filters("xc_woo_paper_sizes",$data);
		return $data;
	}
	
	public function get_orders(){
		$args = array(
			'numberposts' => 10,
			'post_type'   => wc_get_order_types(),
			'post_status' => array_keys( wc_get_order_statuses() ),
		);
		$data = array();
		$args = apply_filters("xc_woo_get_orders_for_sample_print_args",$args);
		$orders = get_posts( $args );
		foreach($orders as $order){
			$_order = new WC_Order($order->ID);
			if($_order->get_customer_id() > 0){
				$customer = get_userdata($_order->get_customer_id());
				$c_email = $customer->data->user_email;
				$c_name = $customer->data->user_login;
			}else{
				$c_name = $_order->get_billing_first_name();
				$c_email = $_order->get_billing_email();	
			}
			$order_number = $_order->get_order_number();
			$data[$order->ID] = __("Order #",XC_WOO_CLOUD).$order_number.__(" by ",XC_WOO_CLOUD).$c_name." ".$c_email;
		}
		
		return $data;
				
	}
	
	function get_available_payment_methods(){
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$methods = array();
		foreach($available_gateways as $v){
			$methods[$v->id] =$v->title;
		}
		return $methods;
	}
	
	/**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public function get_settings($current_section = '') {
		$settings = array();
		$id = $this->id."_".$current_section;
		//$printers = get_xc_cloud_printers();
		if($current_section == ''){
			$id = $this->id;
        $settings = array(
			
            'section_title' => array(
                'name'     => __( 'Shop Details', XC_WOO_CLOUD ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => $id.'_section_title'
            ),
			'paper_size' => array(
                'name' => __( 'Paper Size', XC_WOO_CLOUD ),
                'type' => 'select',
				'default' => 'A4',
				'css'     => 'width:150px',
                'desc_tip' => __( 'Select Printer paper size', XC_WOO_CLOUD ),
				'options' => $this->paper_sizes(),
                'id'   => $id.'_paper_size'
            ),
			'paper_orientation' => array(
                'name' => __( 'Paper Orientation', XC_WOO_CLOUD ),
                'type' => 'select',
				'default' => 'portrait',
				'css'     => 'width:150px',
                'desc_tip' => __( 'Select Printer paper orientation', XC_WOO_CLOUD ),
				'options' => array("portrait"=>"Portrait","landscape"=>"Landscape"),
                'id'   => $id.'_paper_orientation'
            ),			
			'payment_check' => array(
                'name' => __( 'Only Print After Payment', XC_WOO_CLOUD ),
                'type' => 'multiselect',
				'default' => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
                'desc_tip' => __( 'Select Payment gateways (these payment gateway orders will print after payment complete. remaining orders will print when order placed) leave blank if you need to print every order when order placed.', XC_WOO_CLOUD ),
				'options' => $this->get_available_payment_methods(),
                'id'   => $id.'_payment_check'
            ),			
			'sample_order' => array(
                'name' => __( 'Sample Order', XC_WOO_CLOUD ),
                'type' => 'select',
				'default' => '',
				'css'     => 'width:250px',
                'desc_tip' => __( 'Select Order for samples (sample invoices and sample packing slips)', XC_WOO_CLOUD ),
				'options' => $this->get_orders(),
                'id'   => $id.'_sample_order'
            ),
            'logo' => array(
                'name' => __( 'Header Logo', XC_WOO_CLOUD ),
                'type' => 'image',
                'desc' => __( 'Header Logo', XC_WOO_CLOUD ),
                'id'   => $id.'_logo'
            ),
			'title' => array(
                'name' => __( 'Shop Name', XC_WOO_CLOUD ),
                'type' => 'text',
                'desc' => __( 'Shop Name', XC_WOO_CLOUD ),
				'class' => 'input-text regular-input ',
                'id'   => $id.'_shop_name'
            ),
            'address' => array(
                'name' => __( 'Shop Address', XC_WOO_CLOUD ),
                'type' => 'textarea',
                'desc' => __( 'Shop Address',XC_WOO_CLOUD ),
				'custom_attributes' => array("rows"=>10,"cols"=>100),
                'id'   => $id.'_shop_address'
            ),
			'footer' => array(
                'name' => __( 'Footer', XC_WOO_CLOUD ),
                'type' => 'textarea',
                'desc_tip' => __( 'terms & conditions, policies, etc. avalable placeholders {billing_first_name} , {billing_last_name}, {order_number} , {order_total}, {site_title}' ),
				'custom_attributes' => array("rows"=>6,"cols"=>100),
                'id'   => $id.'_footer'
            ),
			'enable' => array(
                'name' => __( 'Enable/Disable', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Enable Printing Logs', XC_WOO_CLOUD ),
                'id'   => $id.'_enable_logs'
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => $id.'_section_end'
            )
        );
		}
		
		if($current_section == 'invoice'){
			$settings = array(
            'section_title' => array(
                'name'     => __( 'Invoice Options', XC_WOO_CLOUD ),
                'type'     => 'title',
                'desc'     => "<a target='_blank' href='".admin_url( 'admin-ajax.php' )."?action=xc_woo_printer_preview&document_type=invoice'>".__("Invoice Preview",XC_WOO_CLOUD)."</a> | <a href='".admin_url( 'admin-ajax.php' )."?action=xc_woo_printer_preview&document_type=invoice&t=print'>  ".__("Print a Sample",XC_WOO_CLOUD)." </a>",
                'id'       => $id.'_section_title'
            ),
            'enable' => array(
                'name' => __( 'Enable/Disable', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Enable Invoice Printing', XC_WOO_CLOUD ),
                'id'   => $id.'_enable'
            ),
			
			array(
				'title'       => __( 'Select Printers ', XC_WOO_CLOUD ),
				'desc_tip'    => __( 'Selectt printers to print invoice', XC_WOO_CLOUD ),
				'id'          => $id.'_printers',
				'type'        => 'xc_printer_selector',
				'placeholder' => __( 'N/A', XC_WOO_CLOUD ),
				'default'     => array(
					'printerid' => '',
					'size' => '',
					'orientation'   => '',
					'copies'   => '1',
				),
				'value' => '',
				'autoload'    => false,
			),
						
			'invoice-copies' => array(
                'name' => __( 'Number of copies to print', XC_WOO_CLOUD ),
                'type' => 'select',
				'default' => '1',
				'css'     => 'width:150px',
                'desc_tip' => __( 'Select number of invoice copies to print through google cloud print', XC_WOO_CLOUD ),
				'options' => array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10),
                'id'   => $id.'_copies'
            ),
			
			'shipping_billing_layout' => array(
                'name' => __( 'Billing and Shipping Address Layout', XC_WOO_CLOUD ),
                'type' => 'select',
				'default' => 'billing-shipping',
                'desc_tip' => __( 'Billing and shipping address order in Invoice', XC_WOO_CLOUD ),
				'options' => array("billing-shipping" => __("Billing Address + Shipping Address",XC_WOO_CLOUD),
									"shipping-billing" => __("Shipping Address + Billing Address",XC_WOO_CLOUD)),
                'id'   => $id.'_shipping_billing_layout'
            ),
			'shipping_address' => array(
                'name' => __( 'Display shipping address', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display shipping address (in addition to the default billing address) if different from billing address', XC_WOO_CLOUD ),
                'id'   => $id.'_show_shipping_address'
            ),
			'shipping_method' => array(
                'name' => __( 'Display Shipping Method', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display Shipping Method', XC_WOO_CLOUD ),
                'id'   => $id.'_show_shipping_method'
            ),
			'email' => array(
                'name' => __( 'Display email address', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display email address', XC_WOO_CLOUD ),
                'id'   => $id.'_show_email'
            ),
			'phone' => array(
                'name' => __( 'Display phone number', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display phone number', XC_WOO_CLOUD ),
                'id'   => $id.'_show_phone_number'
            ),
			
			'barcode' => array(
                'name' => __( 'Display Barcode', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display Barcode', XC_WOO_CLOUD ),
                'id'   => $id.'_show_barcode'
            ),
			
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => $id.'_section_end'
            )
        );	
		}
		
		if($current_section == 'packing-slip'){
			$settings = array(
            'section_title' => array(
                'name'     => __( 'Packing Slip options', XC_WOO_CLOUD ),
                'type'     => 'title',
                'desc'     => "<a target='_blank' href='".admin_url( 'admin-ajax.php' )."?action=xc_woo_printer_preview&document_type=packing-slip'>".__("Packing Slip Preview",XC_WOO_CLOUD)."</a> | <a href='".admin_url( 'admin-ajax.php' )."?action=xc_woo_printer_preview&document_type=packing-slip&t=print'>  ".__("Print a Sample ",XC_WOO_CLOUD)."</a>",
                'id'       => $id.'_section_title'
            ),
            'enable' => array(
                'name' => __( 'Enable/Disable', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Enable Packing Slip Printing', XC_WOO_CLOUD ),
                'id'   => $id.'_enable'
            ),
			
			array(
				'title'       => __( 'Select Printers ', XC_WOO_CLOUD ),
				'desc_tip'    => __( 'Selectt printers to print packing-slips', XC_WOO_CLOUD ),
				'id'          => $id.'_printers',
				'type'        => 'xc_printer_selector',
				'placeholder' => __( 'N/A', XC_WOO_CLOUD ),
				'default'     => array(
					'printerid' => '',
					'size' => '',
					'orientation'   => '',
					'copies'   => '1',
				),
				'value' => '',
				'autoload'    => false,
			),
			
			'packing-slip-copies' => array(
                'name' => __( 'Number of copies to print', XC_WOO_CLOUD ),
                'type' => 'select',
				'default' => '1',
				'css'     => 'width:150px',
                'desc_tip' => __( 'Select number of packing slip copies to print through google cloud print', XC_WOO_CLOUD ),
				'options' => array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10),
                'id'   => $id.'_copies'
            ),
			
			'shipping_billing_layout' => array(
                'name' => __( 'Billing and Shipping Address Layout', XC_WOO_CLOUD ),
                'type' => 'select',
				'default' => 'shipping-billing',
                'desc_tip' => __( 'Billing and shipping address order in Invoice', XC_WOO_CLOUD ),
				'options' => array("billing-shipping" => __("Billing Address + Shipping Address",XC_WOO_CLOUD),
									"shipping-billing" => __("Shipping Address + Billing Address",XC_WOO_CLOUD)),
                'id'   => $id.'_shipping_billing_layout'
            ),
			
			'billing_address' => array(
                'name' => __( 'Display Billing address', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display billing address (in addition to the default shipping address) if different from shipping address', XC_WOO_CLOUD ),
                'id'   => $id.'_show_billing_address'
            ),
			'payment_method' => array(
                'name' => __( 'Display Payment Method', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display Payment Method', XC_WOO_CLOUD ),
                'id'   => $id.'_show_payment_method'
            ),
			'email' => array(
                'name' => __( 'Display email address', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display email address', XC_WOO_CLOUD ),
                'id'   => $id.'_show_email'
            ),
			'phone' => array(
                'name' => __( 'Display phone number', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display phone number', XC_WOO_CLOUD ),
                'id'   => $id.'_show_phone_number'
            ),
			'barcode' => array(
                'name' => __( 'Display Barcode', XC_WOO_CLOUD ),
                'type' => 'checkbox',
				'default' => 'no',
                'desc' => __( 'Display Barcode', XC_WOO_CLOUD ),
                'id'   => $id.'_show_barcode'
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => $id.'_section_end'
            )
        );	
		}
		if($current_section == 'system-status'){
			$settings = array(
            'section_title' => array(
                'name'     => __( 'Syatem Status', XC_WOO_CLOUD ),
                'type'     => 'title',
                'id'       => $id.'_section_title'
            ),
            'enable' => array(
                'name' => __( 'Enable/Disable', XC_WOO_CLOUD ),
                'type' => 'xc_system_status',
				'default' => '',
                'desc' => __( 'System Status', XC_WOO_CLOUD ),
                'id'   => $id.'_system-status'
            ),
			'section_end' => array(
                 'type' => 'sectionend',
                 'id' => $id.'_section_end'
            )
        );	

		}
		
		$settings = apply_filters("xc_woo_cloud_print_sections_settings",$settings,$current_section,$this->id);
		
        return apply_filters( 'wc_settings_tab_'.$this->id, $settings );
    }
	
	public function xc_printer_selector_field($value){
		$option_value = $this->get_option( $value['id'], $value['default'] );
		$description = $value['desc'];
		$tooltip_html = $value['desc_tip'];
		$tooltip_html = wc_help_tip( $tooltip_html );
		$paper_sizes      = $this->paper_sizes();
		$orientations = array("portrait"=>"Portrait","landscape"=>"Landscape");
		$printers = get_xc_cloud_printers();		
		?>

<tr valign="top">
  <th scope="row" class="titledesc"> <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
  </th>
  <td class="forminp"><?php
  if(is_wp_error($printers)){
				echo strip_tags($printers->get_error_message());
		}elseif (count($printers) == 0) {
			echo  __('Account either not connected, or no printers available)', XC_WOO_CLOUD);	
		}else{
			?>
    <table class="widefat">
    <thead>
    <tr>
    	<td width="10px"></td>
    	<th><?php echo __('Printer',XC_WOO_CLOUD);?></th>
        <th><?php echo __('Paper Size',XC_WOO_CLOUD);?></th>
        <th><?php echo __('Paper Orientation',XC_WOO_CLOUD);?></th>
        <th><?php echo __('Number of copies',XC_WOO_CLOUD);?></th>
        <?php do_action("xc_woo_google_cloud_print_printer_settings_th");?>
    </tr>
    </thead>
    <tbody>
      <?php
	  		$i = 0;
			foreach ($printers as $printer) {
				$i++;
				?>
      <tr>
      	<td></td>
        <td><label for="<?php echo esc_attr( $value['id'] ); ?>[<?php  echo $printer->id;?>][enable]">
            <input
									name="<?php echo esc_attr( $value['id'] ); ?>[<?php  echo $printer->id;?>][enable]"
									id="<?php echo esc_attr( $value['id'] ); ?>[<?php  echo $printer->id;?>][enable]"
									type="checkbox"
									class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
									value="1"
									<?php checked( (isset($option_value[$printer->id]['enable'])?'yes':''), 'yes' ); ?>
								/>
            <?php echo $printer->displayName; // WPCS: XSS ok. ?> </label></td>
        <td><select name="<?php echo esc_attr( $value['id'] ); ?>[<?php  echo $printer->id;?>][size]" style="width: auto;">
            <?php
									foreach ( $paper_sizes as $val => $label ) {
										echo '<option value="' . esc_attr( $val ) . '"' . selected( $option_value[$printer->id]['size'], $val, false ) . '>' . esc_html( $label ) . '</option>';
									}
									?>
          </select></td>
        <td><select name="<?php echo esc_attr( $value['id'] ); ?>[<?php echo $printer->id;?>][orientation]" style="width: auto;">
            <?php
									foreach ( $orientations as $val => $label ) {
										echo '<option value="' . esc_attr( $val ) . '"' . selected( $option_value[$printer->id]['orientation'], $val, false ) . '>' . esc_html( $label ) . '</option>';
									}
									?>
          </select></td>
          <td><select name="<?php echo esc_attr( $value['id'] ); ?>[<?php echo $printer->id;?>][copies]" style="width: auto;">
            <?php
									for ( $k = 1; $k <= 10; $k++) {
										echo '<option value="' . esc_attr( $k ) . '"' . selected( $option_value[$printer->id]['copies'], $k, false ) . '>' . esc_html( $k ) . '</option>';
									}
									?>
          </select></td>
          
          <?php do_action("xc_woo_google_cloud_print_printer_settings_td", $printer, $option_value, $value);?>
          
      </tr>
      <?php	
			}
			?>
            </tbody>
    </table>
    <?php
		}  
  ?>
    <?php echo ( $description ) ? $description : ''; // WPCS: XSS ok. ?></td>
</tr>
<?php
	}
	
	
	function admin_add_wysiwyg_custom_field_textarea()
{ ?>
<script type="text/javascript">/* <![CDATA[ */
	jQuery(function($){
		
		  var id = 'xc_woo_cloud_shop_address';
		  tinyMCE.execCommand("mceAddEditor", false, id);
		  tinyMCE.execCommand("mceAddControl", false, id);
		
	});
/* ]]> */</script>
<?php }

	
	function output_texteditor_field($value){
		$option_value = $this->get_option( $value['id'], $value['default'] );
		$description = $value['desc'];
		$tooltip_html = $value['desc_tip'];
		$tooltip_html = wc_help_tip( $tooltip_html );
		?>
<tr valign="top">
  <th scope="row" class="titledesc"> <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
    <?php echo $tooltip_html; ?> </th>
  <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>"><?php wp_editor( $option_value, $value['id'], $settings = array() ); ?></td>
</tr>
<?php
		
	}
	
	function output_image_field($value){
		add_action( 'admin_footer', array(&$this,'media_selector_print_scripts') );
		wp_enqueue_media();
		//kishore($value);
		$option_value = $this->get_option( $value['id'], $value['default'] );
		$description = $value['desc'];
		$tooltip_html = $value['desc_tip'];
		$button_display = "none";
	?>
<tr valign="top">
  <th scope="row" class="titledesc"> <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
    <?php echo $tooltip_html; ?> </th>
  <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>"><div class="woo_setings_header_logo">
      <?php
						if( $image_attributes = wp_get_attachment_image_src( $option_value, 'full' ) ) {
 
						echo '<img src="' . $image_attributes[0] . '" style="max-height:200px;" />';
 
 						$button_display = 'inline-block';
	} 
						?>
    </div>
    <div> <a href="#" id="upload_image_button" class="button">Set Image</a> <a href="#" class="button remove_image_button" style="display:<?php echo $button_display;?>;">Remove image</a> </div>
    <input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="hidden"
								value="<?php echo esc_attr( $option_value ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								/>
    <?php echo $description; ?></td>
</tr>
<?php	
	}
	
	public function get_option( $option_name, $default = '' ) {
		// Array value
		if ( strstr( $option_name, '[' ) ) {

			parse_str( $option_name, $option_array );

			// Option name is first key
			$option_name = current( array_keys( $option_array ) );

			// Get value
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}

		// Single value
		} else {
			$option_value = get_option( $option_name, null );
		}
		
		if ( is_array( $option_value ) ) {
			//$option_value = array_map( 'stripslashes', $option_value );
			foreach($option_value as $k=>$v){
				if(is_array($v)){
					$option_value[$k] = array_map( 'stripslashes', $v );
				}elseif ( ! is_null( $v ) ) {
					$option_value[$k] = stripslashes( $v );
				}
			}
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return ( null === $option_value ) ? $default : $option_value;
	}
	
	function media_selector_print_scripts() {

	?>
<script type='text/javascript'>
		jQuery( document ).ready( function( $ ) {
			// Uploading files
			var file_frame;
			var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
			var set_to_post_id = jQuery("#xc_woo_cloud_logo").val(); // Set this
			jQuery('#upload_image_button').on('click', function( event ){
				event.preventDefault();
				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					// Set the post ID to what we want
					file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					// Open frame
					file_frame.open();
					return;
				} else {
					// Set the wp.media post id so the uploader grabs the ID we want when initialised
					wp.media.model.settings.post.id = set_to_post_id;
				}
				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select a image to upload',
					button: {
						text: 'Use this image',
					},
					multiple: false	// Set to true to allow multiple files to be selected
				});
				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					// We set multiple to false so only get one image from the uploader
					attachment = file_frame.state().get('selection').first().toJSON();
					// Do something with attachment.id and/or attachment.url here
					var $img = '<img src="'+attachment.url+'" style="max-height:200px">';
					jQuery(".woo_setings_header_logo").html($img);
					jQuery( '#xc_woo_cloud_logo' ).val( attachment.id );
					jQuery('.remove_image_button').css({"display":"inline-block"});
					// Restore the main post ID
					wp.media.model.settings.post.id = wp_media_post_id;
				});
					// Finally, open the modal
					file_frame.open();
			});
			// Restore the main ID when the add media button is pressed
			jQuery( 'a.add_media' ).on( 'click', function() {
				wp.media.model.settings.post.id = wp_media_post_id;
			});
			jQuery('a.remove_image_button').on('click',function(){
				jQuery(".woo_setings_header_logo").html("");
				jQuery( '#xc_woo_cloud_logo' ).val("");
				jQuery('.remove_image_button').css({"display":"none"});	
			});
		});
	</script>
	<?php
    }
	
	public function xc_system_status_field(){
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'wc-admin-system-status', WC()->plugin_url() . '/assets/js/admin/system-status' . $suffix . '.js', array( 'wc-clipboard' ), WC_VERSION );
		wp_enqueue_script( 'wc-admin-system-status' );
		wp_localize_script(
				'wc-admin-system-status',
				'woocommerce_admin_system_status',
				array(
					'delete_log_confirmation' => esc_js( __( 'Are you sure you want to delete this log?', XC_WOO_CLOUD ) ),
				)
			)
		
		?>
        <tr valign="top">
  <td colspan="2" >
  	<?php
	include_once dirname( __FILE__ ) . '/views/html-admin-page-status-report.php';
	?>
  </td>
  </tr>
  <style>
  p.submit .woocommerce-save-button{
	display:none !important;  
  }
  </style>
        <?php	
	}


	
	
	 
}
new XC_WOO_Settings();

