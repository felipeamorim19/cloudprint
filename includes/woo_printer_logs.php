<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
/**
 * XC Woo Cloud Class.
 *
 * @class XC_WOO_Printer_logs
 * @version	2.1
 */
if (!class_exists("XC_WOO_Printer_logs")):

class XC_WOO_Printer_logs  {
	public $table = 'xc_woo_cloud_print_logs';
	
	public function init(){
		$this->update_db_check();
		
		if('yes' == get_option('xc_woo_cloud_enable_logs')){
			add_filter("xc_woo_cloud_print_settings_sections", array($this,"log_section"),10,1);
			add_filter( 'xc_woo_cloud_print_sections_settings', array($this, 'log_section_options'), 10, 3 );		
			add_action( 'woocommerce_admin_field_xc_print_logs', array(&$this, 'xc_print_logs_field'));
		}
	}
	
	public function update_db_check(){
		if ( XC_WOO_CLOUD_VERSION != get_option( 'xc_woo_cloud_version' )   ) {
			$this->create_tables();
		}	
	}
	
	public function create_tables(){
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table;
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			message text NULL,
			title varchar(100) NULL,
			type varchar(100) NULL,
			message_value text NULL,
			date_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		update_option( "xc_woo_cloud_version", XC_WOO_CLOUD_VERSION );
	}
	
	public function add_log($message,$title,$type,$data){
		global $wpdb;
		$wpdb->insert( 
			$wpdb->prefix.$this->table, 
			array( 
				'message' => $message, 
				'title' => $title,
				'type' => $type,
				'message_value' => maybe_serialize($data),
				'date_time' => current_time( 'mysql' )
			), 
			array( 
				'%s', 
				'%s',
				'%s',
				'%s',
				'%s' 
			) 
		);
	}
	
	public function log_section($sections){
		$sections['print-logs'] = __("Print Logs", XC_WOO_CLOUD);
		return $sections;
	}
	
	public function log_section_options( $settings,$current_section,$id ){
		$id = $id."_".$current_section;
		if($current_section == 'print-logs'){
			$settings = array(
            'section_title' => array(
                'name'     => __( 'Logs', XC_WOO_CLOUD ),
                'type'     => 'title',
				'desc'     => '<a href="'.admin_url('admin.php').'?page=wc-settings&tab=xc_woo_cloud&section=print-logs&clearlogs'.'" class="button button-primary">'.__('Clear Log', XC_WOO_CLOUD).'</a>',
                'id'       => $id.'_section_title'
            ),
            'enable' => array(
                'name' => __( 'Logs', XC_WOO_CLOUD ),
                'type' => 'xc_print_logs',
				'default' => '',
                'desc' => __( 'Print logs', XC_WOO_CLOUD ),
                'id'   => $id.'_print-logs'
            ),
			'section_end' => array(
                 'type' => 'sectionend',
                 'id' => $id.'_section_end'
            )
        );	

		}
		return $settings;
	}
	
	public function xc_print_logs_field(){
		
		wp_enqueue_style('dataTables', '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css');
		
		wp_enqueue_script('dataTables', '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', array('jquery'));
		
		global $wpdb;
		
		if(isset($_GET['clearlogs'])){
			$sql = 'delete from '.$wpdb->prefix.$this->table;
			$wpdb->query($sql);	
		}
		
		$sql = "select * from ".$wpdb->prefix.$this->table.' order by id';
		$results = $wpdb->get_results($sql);
		?>
        <tr valign="top">
  <td colspan="2" >
  
  <table class="" id="xc-print-logs-table">
  	<thead>
    	<th width="5%"><?php echo __("ID",XC_WOO_CLOUD );?></th>
        <th width="20%"><?php echo __("Type",XC_WOO_CLOUD );?></th>
        <th width="25%"><?php echo __("Title",XC_WOO_CLOUD );?></th>
        <th width="50%"><?php echo __("Message",XC_WOO_CLOUD );?></th>
        <th width="50%"><?php echo __("Time",XC_WOO_CLOUD );?></th>
    </thead>
    <tbody>
    	<?php $i=0; foreach($results as $res){ $i++; ?>
        	<tr>
            	<td><?php echo $i;?></td>
                <td><?php echo $res->type;?></td>
                <td><?php echo $res->title;?></td>
                <td><?php echo $res->message;?></td>
                <td><?php echo date_i18n( get_option( 'date_format' ).' '.get_option( 'time_format' ), strtotime( $res->date_time ) );?></td>
            </tr>
        <?php } ?>
    </tbody>
  </table>
  	<?php
	//print_r($results);
	?>
  </td>
  </tr>
  <script>
  jQuery(document).ready( function () {
    jQuery('#xc-print-logs-table').DataTable({
		"pageLength": 50
	});
} );
  </script>
  <style>
  p.submit .woocommerce-save-button{
	display:none !important;  
  }
  </style>
        <?php
	}
	
}
global $xc_woo_printer_logs;
$xc_woo_printer_logs = new XC_WOO_Printer_logs();
$xc_woo_printer_logs->init();
endif;