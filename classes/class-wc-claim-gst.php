<?php
if(!defined( 'ABSPATH' )) exit;

/**
 * WC_Claim_Gst class.
 */
class WC_Claim_Gst {

	/**
	 * __construct function.
	 */
	public function __construct() {
		
		add_action('admin_menu', array($this, 'admin_menu'));
		add_filter('plugin_action_links_'.TH_WCCG_BASE_NAME, array($this, 'add_settings_link'));
		add_filter( 'sa_wc_variables' , array( $this,'addTokensInWCTemplates'),10,2 );
	}
	
	
	public function addTokensInWCTemplates($variables,$status)
	{
		$variables = array_merge($variables, array(
				'[gstin]' 					=> 'GST Number',
				'[gstin_holder_name]' 		=> 'GST Holder Name',
				'[gstin_holder_address]' 	=> 'GST Holder Address',
		));
		return $variables;
	}		
	
	/**
	 * menu function.
	 */
	public function admin_menu() {
		$this->screen_id = add_submenu_page('woocommerce', __('WooCommerce Checkout Claim GST', 'cvcg'), __('Claim GST', 'cvcg'), 
		'manage_woocommerce', 'claim_gst', array($this, 'the_designer'));
	}
	
	public function add_settings_link($links) {
		$settings_link = '<a href="'.admin_url('admin.php?page=claim_gst').'">'. __('Settings') .'</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
	
	
	public function get_current_tab(){
		return isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'fields';
	}
	
	public function the_designer() {
		$tab = $this->get_current_tab();
		if($tab === 'fields'){
			$this->get_settings();
		}
	}
	public function render_actions_row(){
		?>
        
        <th colspan="4">
        	<input type="submit" name="save_fields" class="button-primary" value="<?php _e( 'Save changes', 'woo-claim-gst' ) ?>"/>
        </th>  
    	<?php 
	}
	public function get_settings() {
		if ( isset( $_POST['save_fields'] ) )
				echo $this->save_options();
			$fields = get_option('wc_claim_gst', array());
			$checked = empty($fields)?'checked':'';
			
		?>         
			<form method="post" id="wcfd_checkout_fields_form" action="">
            	<table id="wcfd_checkout_fields" class="wc_gateways widefat thpladmin_fields_table" cellspacing="0">	
                    <tfoot>     
						<tr><?php $this->render_actions_row(); ?></tr>
					</tfoot>
					<tbody class="ui-sortable">
                    <tr>
                        	
                            <td class="td_select"><label><input type="checkbox" name="is_holder_name" <?php echo (isset($fields['is_holder_name']) && ($fields['is_holder_name']=='on'))?'checked':$checked; ?>/>Ask GST holder name</label></td>
							</tr>
							<tr>
							<td class="td_select"><label><input type="checkbox" name="is_gst_match" <?php echo (isset($fields['is_gst_match']) && ($fields['is_gst_match']=='on'))?'checked':$checked; ?>/>GST Number should match with billing state</label></td>
							</tr>
							<tr>
							<td class="td_select"><label><input type="checkbox" name="is_holder_address" <?php echo (isset($fields['is_holder_address']) && ($fields['is_holder_address']=='on'))?'checked':$checked; ?>/>Ask GST holder address</label></td>
                    	</tr>
                	</tbody>
				</table> 
            </form>
    	</div>
    <?php 		
	}
	public function save_options() {
		
		$is_holder_name       = ! empty( $_POST['is_holder_name'] ) ? sanitize_text_field($_POST['is_holder_name']) : false;
		$is_gst_match       = ! empty( $_POST['is_gst_match'] ) ? sanitize_text_field($_POST['is_gst_match']) : false;
		$is_holder_address       = ! empty( $_POST['is_holder_address'] ) ? sanitize_text_field($_POST['is_holder_address']) : false;
		$fields = array('is_holder_name'=>$is_holder_name,'is_gst_match'=>$is_gst_match,'is_holder_address'=>$is_holder_address);
		$result = update_option( 'wc_claim_gst', $fields );

		if ( $result == true ) {
			echo '<div class="updated"><p>' . __( 'Your changes were saved.', 'woo-claim-gst' ) . '</p></div>';
		} else {
			echo '<div class="error"><p> ' . __( 'Your changes were not saved due to an error (or you made none!).', 'woo-claim-gst' ) . '</p></div>';
		}
	}
	public function get_current_section(){
		$tab = $this->get_current_tab();
		$section = '';
		if($tab === 'fields'){
			$section = isset( $_GET['section'] ) ? esc_attr( $_GET['section'] ) : '';
		}
		return $section;
	}
	public function cvcg_getStateCode($state=NULL)
	{
		$state = strtoupper($state);
		$states=array(
							'AN'=>'35',
							'AP'=>'37',
							'AR'=>'12',
							'AS'=>'18',
							'BR'=>'10',
							'CH'=>'04',
							'CT'=>'22',
							'DN'=>'26',
							'DD'=>'25',
							'DL'=>'07',
							'GA'=>'30',
							'GJ'=>'24',
							'HR'=>'06',
							'HP'=>'02', 
							'JK'=>'01',
							'JH'=>'20',
							'KA'=>'29',
							'KL'=>'32',
							'LD'=>'31',
							'MP'=>'23',
							'MH'=>'27',
							'MN'=>'14',
							'ML'=>'17',
							'MZ'=>'15',
							'NL'=>'13',
							'OR'=>'21',
							'PY'=>'34',
							'PB'=>'03',
							'RJ'=>'08',
							'SK'=>'11',
							'TN'=>'33',
							'TS'=>'36',
							'TR'=>'16',
							'UP'=>'09',
							'UK'=>'05',
							'WB'=>'19',
				 );
				
				return array_key_exists($state,$states)?$states[$state]:'';
	}
}