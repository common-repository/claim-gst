<?php
/**
 * Plugin Name: Claim Gst
 * Description: Claim GST for Input Tax Credit
 * Author:      Cozy Vision Technologies Pvt. Ltd.
 * Version:     1.3.3
 * Domain Path: /languages
 * WC requires at least: 3.7
 * WC tested up to: 7.5
 */
	if(!defined( 'ABSPATH' )) exit;
	if (!function_exists('is_woocommerce_active')){
		function is_woocommerce_active(){
			$active_plugins = (array) get_option('active_plugins', array());
			if(is_multisite()){
			   $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
			}
			return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
		}
	}

if(is_woocommerce_active()) {
	
	load_plugin_textdomain( 'cvcg', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	
	function cvcg_claim_gst() {
		global $supress_field_modification;
		$supress_field_modification = false;
		
		define('TH_WCCG_VERSION', '1.3.2');
		!defined('TH_WCCG_BASE_NAME') && define('TH_WCCG_BASE_NAME', plugin_basename( __FILE__ ));
		!defined('TH_WCCG_URL') && define('TH_WCCG_URL', plugins_url( '/', __FILE__ ));
		!defined('TH_WCCG_ASSETS_URL') && define('TH_WCCG_ASSETS_URL', TH_WCCG_URL . 'assets/');

		if(!class_exists('WC_Claim_Gst')){
			require_once('classes/class-wc-claim-gst.php');
		}

		$GLOBALS['WC_Claim_Gst'] = new WC_Claim_Gst();
	}
	add_action('init', 'cvcg_claim_gst');
	
	add_action( 'before_woocommerce_init', function () {
		if ( wp_doing_ajax() ) {
			return;
		}
		if ( class_exists( 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) && method_exists( 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil', 'declare_compatibility' ) ) {
			
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_basename( __FILE__ ), true );
		}
    });
	
/**
* Add custom field to the checkout page
*/

add_action('woocommerce_review_order_before_submit', 'cvcg_custom_checkout_field');

function cvcg_custom_checkout_field($checkout)
{
	if(get_woocommerce_currency()=="INR" && WC()->session->get('customer')['shipping_country']=="IN")
		{
		woocommerce_form_field('is_gstin', array(
		'type' => 'checkbox',
		'class' => array(
		'my-field-class form-row-wide'
		) ,
		'label' => __('Use GSTIN for claiming input tax') ,
		'placeholder' => __('GSTIN') ,
		) 
		);
		echo '<div id="custom_checkout_field" style="display:none;">';
		woocommerce_form_field('gstin', array(
		'type' => 'text',
		'required'=>true,
		'class' => array(
		'my-field-class form-row-wide'
		) ,
		'label' => __('GSTIN') ,
		'placeholder' => __('GSTIN') ,
		) 
		);
		$fields = get_option('wc_claim_gst', array());

		if((isset($fields['is_holder_name']) && ($fields['is_holder_name']=='on')) || empty($fields))
		{
		woocommerce_form_field('gstin_holder_name', array(
		'type' => 'text',
		'required'=>true,
		'class' => array(
		'my-field-class form-row-wide'
		) ,
		'label' => __('GSTIN Holder Name') ,
		'placeholder' => __('GSTIN Holder Name') ,
		) 
		);
		}
		if((isset($fields['is_holder_address']) && ($fields['is_holder_address']=='on')) || empty($fields))
		{
		woocommerce_form_field('gstin_holder_address', array(
		'type' => 'textarea',
		'required'=>true,
		'class' => array(
		'my-field-class form-row-wide'
		) ,
		'label' => __('GSTIN Holder Address') ,
		'placeholder' => __('GSTIN Holder Address') ,
		) 
		);
		}
		echo '</div>';

		echo '<script>
		jQuery(document).ready(function(){
			jQuery("#is_gstin").trigger("change");
		});
		jQuery("#is_gstin").change(function(event) {
		if(jQuery("#is_gstin").is(":checked"))
		{
			jQuery("#custom_checkout_field").find("input,textarea").removeAttr("disabled");
			jQuery("#custom_checkout_field").show();
		}
		else
		{
			jQuery("#custom_checkout_field").find("input,textarea").prop("disabled", true);
			jQuery("#custom_checkout_field").hide();
		}
		});
		</script>';
	}
}
	add_action('woocommerce_checkout_process', 'cvcg_customise_checkout_field_process');
	function cvcg_customise_checkout_field_process()
	{
		$fields = get_option('wc_claim_gst', array());
		if (isset($_POST['gstin_holder_name']) && $_POST['gstin_holder_name']=='') wc_add_notice(__('GSTIN Holder Name is required field.') , 'error');
		if (isset($_POST['gstin_holder_address']) && $_POST['gstin_holder_address']=='') wc_add_notice(__('GSTIN Holder Address is required field.') , 'error');
		if (isset($_POST['gstin']) && $_POST['gstin']=='') 
		{
			wc_add_notice(__('GSTIN is required field.') , 'error');
		}
		else if (isset($_POST['gstin']) && $_POST['gstin']!='')
		{	
			$gstno = sanitize_text_field($_POST['gstin']);
			if(preg_match("/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/", $gstno))
			{
				if(isset($fields['is_gst_match']) && ($fields['is_gst_match']=='on'))
		        {
					$state_code=substr($gstno,0,2);
					$selected_sate = sanitize_text_field($_POST['billing_state']);
					$class = new WC_Claim_Gst();
					$selected_sate_code = $class->cvcg_getStateCode($selected_sate);
					if($state_code!=$selected_sate_code)
					{
						wc_add_notice(__('Enter GSTIN does not match with selected state') , 'error');
						return false;				  
					}
					else
					{
						return true;
					}
				}
				else
				{
					return true;
				}
			}
			wc_add_notice(__('Enter GSTIN is not valid') , 'error');
		}
	}
	
	add_action( 'woocommerce_checkout_update_order_meta', 'cvcg__custom_checkout_field_update_order_meta' );

	function cvcg__custom_checkout_field_update_order_meta( $order_id ) {
		if (! empty( $_POST['gstin'] ) || ! empty( $_POST['gstin_holder_name'] ) || ! empty( $_POST['gstin_holder_address'] )) {
			$order = wc_get_order( $order_id );
			$order->update_meta_data( 'gstin', sanitize_text_field( $_POST['gstin'] ) );
			$order->update_meta_data( 'gstin_holder_name', sanitize_text_field( $_POST['gstin_holder_name'] ) );
			$order->update_meta_data( 'gstin_holder_address', sanitize_text_field( $_POST['gstin_holder_address'] ) );
			$order->save();
		}
	}

	add_filter( 'manage_edit-shop_order_columns','cvcg_add_column_order_list',10 );

	function cvcg_add_column_order_list($columns){
        $columns['gstin'] = __('Purchase Type','textdomain');
        return $columns;
    }
	
	add_action( 'manage_shop_order_posts_custom_column','cvcg_add_column_value_order_list',20,2 );
	
	function  cvcg_add_column_value_order_list($column, $order_id )
	{
		$order = wc_get_order( $order_id );
		$gstin = $order->get_meta('gstin');
		if($column=='gstin')
		{	
			$purchase_type = ($gstin!='')?'B2B':'B2C';
			echo esc_html($purchase_type);
		}
	}
	add_action( 'woocommerce_admin_order_data_after_shipping_address','cvcg_show_purchase_type',10,1);
	
	function cvcg_show_purchase_type($order) {
		$gstin = $order->get_meta('gstin');
		$isgst = ($gstin!='')?'B2B':'B2C'; 
		echo '<span style="padding: 4px;background: #ad5454;color: #fff;font-size: 14px;">'.esc_html($isgst).'</span>';
		
		if($gstin!='')
		{
			echo '<br /><br /><strong>GSTIN Details</strong><br /><br />'.
			'GSTIN Holder Name: '.$order->get_meta('gstin_holder_name').'<br />'.
			'GSTIN: '.$gstin.'<br />'.
			'GSTIN Holder Address: '.$order->get_meta('gstin_holder_address').'<br />';
		}
	}
	
	function cvcg_woo_version_check( $version = '3.0' ) {
		if(function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {
			global $woocommerce;
			if( version_compare( $woocommerce->version, $version, ">=" ) ) {
				return true;
			}
		}
		return false;
	} 
	
	add_action('wpo_wcpdf_after_billing_address','showGSTDetailsInInvoicePDF',100,2);
	function showGSTDetailsInInvoicePDF($type,$order)
	{
		$order_id = $order->get_id();
		if ( ! $order_id )
			return;
		$gst_no = $order->get_meta('gstin');
		if(!empty($gst_no)){
			echo '<div class="claim-gst">GSTIN: '.$gst_no.'</div>';
		}
	}
	
}
?>