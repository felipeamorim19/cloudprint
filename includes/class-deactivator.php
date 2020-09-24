<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Xc_Woo_Cloud
 * @author     XpertsClub <admin@xperts.club>
 */
class Xc_Woo_Cloud_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		$plugin_name = XC_WOO_CLOUD;
		
		$purchase_code = get_option("{$plugin_name}-purchase-code");
		
		if(!$purchase_code) return;
		
		delete_option("{$plugin_name}-purchase-code");
		
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
		
		$args = array(
			'request'       => '__xc_plugin_deactivation',
			'purchase_code' => $purchase_code,
			'item_id'       => sanitize_text_field( $plugin_name ),
			'instance'      => $home_url
		);
		
		$api_uri  = esc_url_raw( add_query_arg( $args, "https://xperts.club") );
		$timeout  = apply_filters( 'xc_woo_cloud_licence_timeout', 30, __FUNCTION__ );
		$response = wp_remote_get( $api_uri, array( 'timeout' => $timeout ) );
		
	}

}