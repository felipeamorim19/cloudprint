<?php
class XC_WOO_Printer_Licence {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	
	
	/**
	 * @var string plugin purchase code
	 * @since 1.0.0
	 */
	private $purchase_code = '';
	
	/**
	 * @var string xperts.club api uri
	 * @since 1.0.0
	 */
	private $api_uri = 'https://xperts.club';

	/**
	 * @var string The xperts.club api uri query args
	 * @since 1.0.0
	 */
	private $api_uri_query_args = '?request=%request%';
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;		
		$this->purchase_code = get_option("{$plugin_name}-purchase-code",'');
		
		$ajax_action = str_replace('-','_',$this->plugin_name)."_update_purchase_code";
		
		add_action('admin_init', array($this, 'updater'));
		
		add_action( 'admin_notices', array( $this, 'activate_license_notice' ), 15 );
		add_action( "wp_ajax_".$ajax_action, array( $this, 'update_purchase_code' ) );
		
	}
	
	/**
	 * Get The home url without protocol
	 *
	 * @return string the home url
	 *
	 * @since  1.0.0
	 */
	public function get_home_url() {
		$home_url = home_url();
		$schemes  = array( 'https://', 'http://', 'www.' );

		foreach ( $schemes as $scheme ) {
			$home_url = str_replace( $scheme, '', $home_url );
		}

		if ( strpos( $home_url, '?' ) !== false ) {
			list( $base, $query ) = explode( '?', $home_url, 2 );
			$home_url = $base;
		}

		$home_url = untrailingslashit( $home_url );

		return $home_url;
	}
	
	/**
	 * Get protected xperts.club api uri
	 *
	 *
	 * @return mixed array
	 *
	 * @since  1.0
	 */
	public function get_api_uri( $request ) {
		return str_replace( '%request%', $request, $this->api_uri . $this->api_uri_query_args );
	}
	
	
	/**
	* Update plugin if available
	*
	* @since 1.2;
	*/
	public function updater(){
		if (isset($this->purchase_code) && $this->purchase_code!='') {
			if(!class_exists('Puc_v4_Factory')){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/licence/updater.php';
			}
			
		$args = array(
			'purchase_code' => $this->purchase_code,
			'item_id'       => sanitize_text_field( $this->plugin_name ),
			'version'       => $this->version,
			'instance'      => $this->get_home_url()
		);
		
		$api_uri  = esc_url_raw( add_query_arg( $args, $this->get_api_uri( '__xc_plugin_update' ) ) );
			
			$updatechecker = Puc_v4_Factory::buildUpdateChecker( $api_uri, XC_WOO_CLOUD_FILE, $this->plugin_name  );
		}	
	}
	
	public function activate_license_notice(){
		$show_license_notice = current_user_can( 'update_plugins' );
		if ( apply_filters( 'xc_woo_cloud_show_activate_license_notice', $show_license_notice ) && $this->purchase_code == '' ) {
			$plugin_data = get_plugin_data( XC_WOO_CLOUD_FILE );
			$plugin_name = $plugin_data['Name'];
			?>
            <div class="notice notice-error xc-plugin-active-notice">
                <p><strong>Warning!</strong> You didn't set purchase code for <span style="display:inline-block; padding:3px 10px; margin: 0 5px 10px 5px; background: #f1f1f1; border-radius: 4px;"><?php echo $plugin_name ?></span> which means you're missing out on updates and support. <br>
<em>( How to find your purchase code from CodeCanyon <a href="https://xperts.club/how-to-find-your-purchase-code-from-codecanyon-or-themeforest/" target="_blank">click here</a> )</em> </p>
                <form method="post" action="" style="margin-bottom:10px;" id="<?php  echo str_replace('-','_',$this->plugin_name);?>_request_licence">
                	<input required size="36" placeholder="Purchase Code" type="text" name="<?php echo $this->plugin_name;?>-purchase-code" />
                    <input type="hidden" name="action" value="<?php  echo str_replace('-','_',$this->plugin_name);?>_update_purchase_code" />
                     <button type="submit" class="button button-primary" >Add Code</button> <span style="float:none" class="spinner"></span>
                     <div class="process" style="margin:10px 0px"></div>
                     
                </form>
            
            <script type="text/javascript">
			jQuery(document).ready(function($) {
                $(document).on("submit",'#<?php  echo str_replace('-','_',$this->plugin_name);?>_request_licence',function(event){
					event.preventDefault();
					var $form = $(this);
					$form.find('.spinner').addClass('is-active');
					$.post('<?php echo admin_url('admin-ajax.php');?>',$(this).serialize(),function($data){
						$form.find('.spinner').removeClass('is-active');
						if($data.status == 'success'){
							var $div = $form.parents("div.xc-plugin-active-notice");
							$div.removeClass("notice-error").addClass("notice-success");
							$div.html('<p>Purchase Code added successfully</p>');
							setTimeout(function(){$div.slideUp();},10000);
						}else{
							$form.find('.process').html($data.message);
						}
					},"JSON");
				});
            });
			</script>
            </div>
            
            <?php	
		}
	}
	
	public function update_purchase_code(){
		$posted_values = $_REQUEST;
        $result='';
		$plugin_name = $this->plugin_name;
		$field_name = $this->plugin_name."-purchase-code";
        if (isset($posted_values[$field_name])==false) {
            wp_send_json(array('code'=>401,'message'=>'Please enter valid purchase code'));
        }
        $purchase_code=sanitize_text_field($posted_values[$field_name]);
		
		$args = array(
			'purchase_code' => $purchase_code,
			'item_id'       => sanitize_text_field( $this->plugin_name ),
			'version'       => $this->version,
			'instance'      => $this->get_home_url()
		);
		
		$api_uri  = esc_url_raw( add_query_arg( $args, $this->get_api_uri( '__xc_plugin_activation' ) ) );
		$timeout  = apply_filters( 'xc_woo_twilio_licence_timeout', 30, __FUNCTION__ );
		$response = wp_remote_get( $api_uri, array( 'timeout' => $timeout ) );
		if ( is_wp_error( $response ) ) {
			$body = array('code'=>301,'message'=>'Connection problem! please contact <a href="mailto:admin@xperts.club">support</a>');
		} else {
			$body = json_decode( $response[ 'body' ] );
			$body = is_object( $body ) ? get_object_vars( $body ) : array('code'=>301,'message'=>'Connection problem! please contact <a href="mailto:admin@xperts.club">support</a>');;
		}
		
		if ( $body && is_array( $body ) && isset( $body[ 'status' ] ) && $body[ 'status' ] == 'success' ) {
			update_option("{$plugin_name}-purchase-code", $purchase_code);
		}
		wp_send_json( $body );
		
	}	
}