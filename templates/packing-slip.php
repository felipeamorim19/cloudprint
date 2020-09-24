<?php 
/**
 * Packing Slips template
 *
 * This template can be overridden by copying it to yourtheme/xc-woo-google-cloud-print/packing-slip.php.
 *
 *
 * @author 		XpertsClub
 * @package 	WoocommerceGoogleCloudPrint/Templates
 * @version     1.5
 */

do_action( 'xc_woo_cloud_print_before_document', $this->type, $this->order ); ?>
<?php
	  if(in_array($this->settings['size'],array("A8"))){
		  ?>
          <div class="small-header">
          <?php
		if( $this->has_header_logo() ) {
			$this->header_logo();
		} else {
			echo apply_filters( 'xc_woo_cloud_print_packing_slip_title', __( 'Packing Slip', XC_WOO_CLOUD ) );
		}
		?>
        
        <div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
      </div>  
          <?php  
	  }else{
	  ?> 
<table class="head container">
	<tr>
		<td class="header">
		<?php
		if( $this->has_header_logo() ) {
			$this->header_logo();
		} else {
			echo apply_filters( 'xc_woo_cloud_print_packing_slip_title', __( 'Packing Slip', XC_WOO_CLOUD ) );
		}
		?>
		</td>
		<td class="shop-info">
			<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
		</td>
	</tr>
</table>
<?php
	  } ?>
<h1 class="document-type-label">
<?php if( $this->has_header_logo() ) echo apply_filters( 'xc_woo_cloud_print_packing_slip_title', __( 'Packing Slip', XC_WOO_CLOUD ) ); ?>
</h1>

<?php do_action( 'xc_woo_cloud_print_after_document_label', $this->type, $this->order ); ?>

<table class="order-data-addresses">
	<tr>
    
    	<?php if($this->settings['packing-slip_shipping_billing_layout'] == "billing-shipping"){ ?>
        	<td class="address billing-address">
				<?php if ( $this->settings['packing-slip_show_billing'] == 'yes') { ?>
                <h3><?php echo apply_filters('xc_woo_cloud_print_packing_slip_billing_address', __( 'Billing Address:', XC_WOO_CLOUD )); ?></h3>
                <?php $this->billing_address(); ?>
                <?php } ?>
            </td>
        <?php } ?>
        
		<td class="address shipping-address">
			<h3><?php  echo apply_filters('xc_woo_cloud_print_packing_slip_shipping_address', __( 'Shipping Address:', XC_WOO_CLOUD )); ?></h3>
			<?php $this->shipping_address(); ?>
			<?php if ( $this->settings['packing-slip_show_email'] == 'yes' ) { ?>
			<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php } ?>
			<?php if ( $this->settings['packing-slip_show_phone'] == 'yes' ) { ?>
			<div class="billing-phone"><?php $this->billing_phone(); ?></div>
			<?php } ?>
		</td>
        <?php if($this->settings['packing-slip_shipping_billing_layout'] == "shipping-billing"){ ?>
		<td class="address billing-address">
			<?php if ( $this->settings['packing-slip_show_billing'] == 'yes') { ?>
			<h3><?php echo apply_filters('xc_woo_cloud_print_packing_slip_billing_address', __( 'Billing Address:', XC_WOO_CLOUD )); ?></h3>
			<?php $this->billing_address(); ?>
			<?php } ?>
		</td>
        <?php } ?>
		 <?php
	  if(in_array($this->settings['size'],array("A6","A7","A8"))){
			echo '</tr>';  
			echo '</table>';  
	  }
	  ?>
  <?php if(!in_array($this->settings['size'],array("A6","A7","A8"))){ ?>  <td class="order-data" > <?php } ?>
        <table class="order-data-table">
				<?php do_action( 'xc_woo_cloud_print_before_order_data', $this->type, $this->order ); ?>
				<tr class="order-number">
					<th><?php _e( 'Order Number:', XC_WOO_CLOUD ); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php _e( 'Order Date:', XC_WOO_CLOUD ); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
				<tr class="shipping-method">
					<th><?php _e( 'Shipping Method:', XC_WOO_CLOUD ); ?></th>
					<td><?php $this->shipping_method(); ?></td>
				</tr>
                <?php if($this->settings['packing-slip_show_payment_method'] == "yes") { ?>
                <tr class="shipping-method">
					<th><?php _e( 'Payment Method:', XC_WOO_CLOUD ); ?></th>
					<td><?php $this->payment_method(); ?></td>
				</tr>
                <?php } ?>
                
                <?php do_action( 'xc_woo_cloud_print_before_barcode', $this->type, $this->order ); ?>
                
                 <?php if($this->settings['packing-slip_show_barcode'] == "yes"){ ?>
                <tr class="shipping-method">
					<td colspan="2" align="center" style="padding:5px;">
                    <img alt="Code" src="<?php echo $this->get_barcode();?>" /></td>
				</tr>
                <?php } ?>
                
                
				<?php do_action( 'xc_woo_cloud_print_after_order_data', $this->type, $this->order ); ?>
			</table>			
		<?php if(!in_array($this->settings['size'],array("A6","A7","A8"))){ ?>
      </td>
  </tr>
</table>
<?php } ?>

<?php do_action( 'xc_woo_cloud_print_before_order_details', $this->type, $this->order ); ?>

<table class="order-details">
	<thead>
		<tr>
			<th class="product"><?php _e('Product', XC_WOO_CLOUD ); ?></th>
			<th class="quantity"><?php _e('Quantity', XC_WOO_CLOUD ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php $items = $this->get_order_items(); if( sizeof( $items ) > 0 ) : foreach( $items as $item_id => $item ) : ?>
		<tr class="<?php echo apply_filters( 'xc_woo_cloud_print_item_row_class', $item_id, $this->type, $this->order, $item_id ); ?>">
			<td class="product">
				<?php $description_label = __( 'Description', XC_WOO_CLOUD ); // registering alternate label translation ?>
				<span class="item-name"><?php echo $item['name']; ?></span>
				<?php do_action( 'xc_woo_cloud_print_before_item_meta', $this->type, $item, $this->order  ); ?>
				<span class="item-meta"><?php echo $item['meta']; ?></span>
				<dl class="meta">
					<?php $description_label = __( 'SKU', XC_WOO_CLOUD ); // registering alternate label translation ?>
					<?php if( !empty( $item['sku'] ) ) : ?><dt class="sku"><?php _e( 'SKU:', XC_WOO_CLOUD ); ?></dt><dd class="sku"><?php echo $item['sku']; ?></dd><?php endif; ?>
					<?php if( !empty( $item['weight'] ) ) : ?><dt class="weight"><?php _e( 'Weight:', XC_WOO_CLOUD ); ?></dt><dd class="weight"><?php echo $item['weight']; ?><?php echo get_option('woocommerce_weight_unit'); ?></dd><?php endif; ?>
				</dl>
				<?php do_action( 'xc_woo_cloud_print_after_item_meta', $this->type, $item, $this->order  ); ?>
			</td>
			<td class="quantity <?php echo (absint($item['quantity']) > 1)?"more":"";?>"><?php echo $item['quantity']; ?></td>
		</tr>
		<?php endforeach; endif; ?>
	</tbody>
</table>

<?php do_action( 'xc_woo_cloud_print_after_order_details', $this->type, $this->order ); ?>

<?php do_action( 'xc_woo_cloud_print_before_customer_notes', $this->type, $this->order ); ?>
<div class="customer-notes">
	<?php if ( $this->get_shipping_notes() ) : ?>
		<h3><?php _e( 'Customer Notes', XC_WOO_CLOUD ); ?></h3>
		<?php $this->shipping_notes(); ?>
	<?php endif; ?>
</div>
<?php do_action( 'xc_woo_cloud_print_after_customer_notes', $this->type, $this->order ); ?>

<?php if ( $this->get_footer() ): ?>
<div id="footer">
	<?php $this->footer(); ?>
</div><!-- #letter-footer -->
<?php endif; ?>

<?php do_action( 'xc_woo_cloud_print_after_document', $this->type, $this->order ); ?>