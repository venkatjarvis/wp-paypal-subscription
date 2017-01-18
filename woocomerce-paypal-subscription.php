<?php
	/*
	Plugin Name: Woocomerce Paypal Subscription
	Description: This plugin allows user to work with Paypal Subscription
	Version: 1.0
	Author: Coral Web Designs
	Author URI: http://coralwebdesigns.com/
	*/
	include_once('vendor/autoload.php');
	use PayPal\Rest\ApiContext;
   	use PayPal\Auth\OAuthTokenCredential;
    use PayPal\Api\PatchRequest;
    use PayPal\Api\Patch;
    use PayPal\Api\Plan;
    use PayPal\Api\Currency;
    use PayPal\Api\MerchantPreferences;
    use PayPal\Api\PaymentDefinition;
    use PayPal\Common\PayPalModel;
    use PayPal\Api\ChargeModel;
    use PayPal\Api\Agreement;
  	use PayPal\Api\Payer;
  	use PayPal\Api\PayerInfo;
  	use PayPal\Api\ShippingAddress;
if(get_option('woocommerce_paypalsubscription_settings')['enabled']=="yes"){
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'woocommerce_paypal_subscription_token';
	$sql="CREATE TABLE IF NOT EXISTS ".$table_name." (
	  `ID` int(11) NOT NULL AUTO_INCREMENT,
	  `token` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
	  `order_id` varchar(10) COLLATE utf8mb4_unicode_520_ci NOT NULL,
	  `agreement_id` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
	  PRIMARY KEY (`ID`)
	) $charset_collate;" ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	/**
	 * Register the custom product type after init
	 */
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		function register_paypal_subscription_product_type() {
			/**
			 * This should be in its own separate file.
			 */
			class WC_Product_Simple_Paypal_Subscription extends WC_Product {
				public function __construct( $product ) {
					$this->product_type = 'paypal_subscription';
					//$this->supports[]   = 'ajax_add_to_cart';
					parent::__construct( $product );
				}
			}
		}
		add_action( 'init', 'register_paypal_subscription_product_type' );
	}
	function add_paypal_subscription_product( $types ){
		// Key should be exactly the same as in the class product_type parameter
		$types[ 'paypal_subscription' ] = __( 'Paypal Subscription Product' );
		return $types;
	}
	add_filter( 'product_type_selector', 'add_paypal_subscription_product' );
	function custom_product_tabs( $tabs) {
		$tabs['paypal_subscription'] = array(
			'label'		=> __( 'Paypal Subscription', 'woocommerce' ),
			'target'	=> 'paypal_subscription_options',
			'class'		=> array( 'show_if_paypal_subscription', 'active'  ),
		);
		return $tabs;
	}
	add_filter( 'woocommerce_product_data_tabs', 'custom_product_tabs' );
	function rental_options_product_tab_content() {
		global $post;
		?>
		<div id='paypal_subscription_options' class='panel woocommerce_options_panel'>
			<div class="options_group create_plan">
				<p class="form-field">
					<label for="plan_create_enable">Create Plan</label>
					<input type="checkbox" name="plan_create_enable" id="plan_create_enable" value="0">
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_text_input(array(
							'id'			=> 'plan_name',
							'label'			=> __( 'Name', 'woocommerce' ),
							'desc_tip'		=> 'true',
							'description'	=> __( 'Plan name for reference', 'woocommerce' ),
							'type' 			=> 'text',
						) );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_text_input(array(
							'id'			=> 'plan_description',
							'label'			=> __( 'Description', 'woocommerce' ),
							'desc_tip'		=> 'true',
							'description'	=> __( 'This will appear on the paypal checkout page, i.e. Pro plan at $15/month etc', 'woocommerce' ),
							'type' 			=> 'text',
						) );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_select(array(
			                'id' => 'frequency',
			                'label' => __('Frequency', 'woocommerce'),
			                'options' => array(
			                    'MONTH' => 'MONTH',
			                    'YEAR' => 'YEAR',
			                    'WEEK' => 'WEEK',
			                    'DAY'	=>	'DAY'
			                ))
			            );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_text_input(array(
							'id'			=> 'frequency_interval',
							'label'			=> __( 'Frequency Interval', 'woocommerce' ),
							'desc_tip'		=> 'true',
							'description'	=> __( 'Frequency Interval is number of payments made in a year i.e for day maximum frequency interval is 365, for week its 52 and for month its 12 etc', 'woocommerce' ),
							'type' 			=> 'text',
						) );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_text_input(array(
							'id'			=> 'cycles',
							'label'			=> __( 'Cycles', 'woocommerce' ),
							'desc_tip'		=> 'true',
							'description'	=> __( 'Set 0 for infinite.Cycle is number of payments made with the plan. i.e if frequency is month, interval is 2, cycle is 10. The payment is collected for every 2months for 10 times. If you want the plan to be always active set it to 0.', 'woocommerce' ),
							'type' 			=> 'text',
						) );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_text_input(array(
							'id'			=> 'price',
							'label'			=> __( 'Amount', 'woocommerce' ),
							'desc_tip'		=> 'true',
							'description'	=> __( 'Recurring price.', 'woocommerce' ),
							'type' 			=> 'text',
						) );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_text_input(array(
							'id'			=> 'setup_price',
							'label'			=> __( 'Set Up Fee', 'woocommerce' ),
							'desc_tip'		=> 'true',
							'description'	=> __( 'Optional Initial charge for the plan. Agreements without setup fee will appear a day after signup for some countries due to timezone.', 'woocommerce' ),
							'type' 			=> 'text',
						) );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_text_input(array(
							'id'			=> 'shipping_fee',
							'label'			=> __( 'Shipping Fee', 'woocommerce' ),
							'desc_tip'		=> 'true',
							'description'	=> __( 'Shipping fee in recurring.', 'woocommerce' ),
							'type' 			=> 'text',
						) );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<?php
						woocommerce_wp_text_input(array(
							'id'			=> 'plan_tax',
							'label'			=> __( 'Tax', 'woocommerce' ),
							'desc_tip'		=> 'true',
							'description'	=> __( 'Tax fee in recurring.', 'woocommerce' ),
							'type' 			=> 'text',
						) );
					?>
				</p>
			</div>
			<div class="options_group create_plan_form">
				<p class="form-field">
					<span id="create_plan_spinner" class="spinner"></span>
					<input id="create_plan" class="ed_button button button-small" title="Create a new plan" value="Create Plan" type="button">
				</p>
			</div>
			<?php
				$client_id=get_option('woocommerce_paypalsubscription_settings')['paypal_client_id'];
				$client_secret=get_option('woocommerce_paypalsubscription_settings')['paypal_client_secret'];
				$test_mode=get_option('woocommerce_paypalsubscription_settings')['sandbox_mode'];
				$apiContext = new ApiContext( new OAuthTokenCredential( $client_id, $client_secret ) );
				if($test_mode=="yes"){
			        $apiContext->setConfig(array('mode'=>'sandbox'));
			    }else{
			       	$apiContext->setConfig(array('mode'=>'live'));
			    }
				try {
	              	$params = array('page_size' => '5','status'=>'active');
	              	$planList = Plan::all($params,$apiContext);
	              	if(is_array($planList->plans)){
	              		$plan_count=0;
	              		?>
	              		<div class="add_plan">
	              		<?php
	              		foreach($planList->plans as $plan){
	              			$plan_data = Plan::get($plan->id, $apiContext);
	              			?>
	              			<div class='options_group plan_list <?php echo $plan_data->id; ?>'>
	              				<p class="form-field <?php echo $plan_data->id; ?>">
	              					<input type="radio" id="plan" name="plan" <?php if(get_post_meta( get_the_ID(), 'plan_id', true )==$plan_data->id){echo 'checked="checked"';} ?> data-value="<?php echo $plan_data->id; ?>">
	              					<label for="plan"><?php echo $plan_data->name." - ".get_woocommerce_currency_symbol()."".$plan_data->payment_definitions[0]->amount->value;?></label>
	              				</p>
	              			</div>
	              			<?php
	              			$plan_count++;
	              		}
	              		?>
	              		</div>
	              		<div class='options_group load_more_plans'>
	              			<p class="form-field">
	              				<span id="load_more_plan_spinner" class="spinner"></span>
	              				<input type="hidden" id="plan_id" name="plan_id" value="<?php echo get_post_meta( get_the_ID(), 'plan_id', true ); ?>">
	              				<input id="save_plan" class="ed_button button button-small save_plan" type="button" title="Save plans" value="Save Plan">
	              				<?php
	              					if($plan_count>4){
	              				?>
	              				<input id="load_plan" class="ed_button button button-small load_more_plan" post-id="<?php echo get_the_ID(); ?>" page-data="1" title="Load more plans" value="Load Plan" type="button">
	              				<?php
	              					}
	              				?>
	              			</p>
	              		</div>
	              		<?php
	              	}
	              	else{
	              		?>
	              		<div class="add_plan">
		              		<div id="empty_plans" class="options_group no_plans">
								<p class="form-field">
									<label for="no_plan">No Plans</label>
								</p>
							</div>
						</div>
						<div class='options_group load_more_plans' style="display:none">
	              			<p class="form-field">
	              				<span id="load_more_plan_spinner" class="spinner"></span>
	              				<input type="hidden" id="plan_id" name="plan_id" value="<?php echo get_post_meta( get_the_ID(), 'plan_id', true ); ?>">
	              				<input id="save_plan" class="ed_button button button-small save_plan" type="button" title="Save plans" value="Save Plan">
	              			</p>
	              		</div>
	              		<?php
	              	}
	            }
	            catch (Exception $ex) {
	            	echo "<pre>".print_r($ex->getdata(),1)."</pre>";
	             }
			?>
		</div>
		<?php
	}
	add_action( 'woocommerce_product_data_panels', 'rental_options_product_tab_content' );
	/**
	 * Save the custom fields.
	 */
	function save_paypal_option_field( $post_id ) {
		update_post_meta($post_id, "plan_id", $_POST['plan_id']);
		update_post_meta($post_id,"product_type",$_POST['product-type']);
		if($_POST['product-type']=='paypal_subscription'){
			update_post_meta($post_id,'_virtual','yes');
		}
	}
	add_action('save_post', 'save_paypal_option_field');
	function hide_attributes_data_panel( $tabs) {
		$tabs['general']['class'][] = 'hide_if_paypal_subscription hide_if_variable_rental';
		return $tabs;
	}
	add_filter( 'woocommerce_product_data_tabs', 'hide_attributes_data_panel',10,5);
	add_action( 'init', 'my_script_enqueuer' );
	function my_script_enqueuer() {
	   wp_register_script( "paypal_subscription_custom_script", WP_PLUGIN_URL.'/woocomerce-paypal-subscription/paypal_subscription_custom_script.js', array('jquery') );
	   wp_localize_script( 'paypal_subscription_custom_script', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	   wp_enqueue_script( 'jquery' );
	   wp_enqueue_script( 'paypal_subscription_custom_script' );
	}
	add_action("wp_ajax_nopriv_create_paypal_subscription_plan", "create_paypal_subscription_plan");
	add_action("wp_ajax_create_paypal_subscription_plan", "create_paypal_subscription_plan");
	function create_paypal_subscription_plan(){	
		$client_id=get_option('woocommerce_paypalsubscription_settings')['paypal_client_id'];
		$client_secret=get_option('woocommerce_paypalsubscription_settings')['paypal_client_secret'];
		$test_mode=get_option('woocommerce_paypalsubscription_settings')['sandbox_mode'];
		$apiContext = new ApiContext( new OAuthTokenCredential( $client_id, $client_secret ) );
		if ( $test_mode == "yes" ) {
	        $apiContext->setConfig( array( 'mode' => 'sandbox' ) );
	    }else{
	     	$apiContext->setConfig( array( 'mode' => 'live' ) );
	   	}
	   	$plan = new Plan();
	    if($_POST['cycles']==0){
	        $type = 'INFINITE';
	    }else{
	        $type = 'FIXED';
	    }
	    $plan->setName($_POST['plan_name'])->setDescription($_POST['plan_description'])->setType($type);
	    $paymentDefinition = new PaymentDefinition();
	    $paymentDefinition->setName($_POST['plan_name'])
	        ->setType('REGULAR')
	        ->setFrequency($_POST['frequency'])
	        ->setFrequencyInterval($_POST['frequency_interval'])
	        ->setCycles($_POST['cycles'])
	        ->setAmount(new Currency(array('value' => $_POST['price'], 'currency' => get_option('woocommerce_currency'))));
	    if(isset($_POST['shipping_fee'])){
	    	if($_POST['shipping_fee']>0){
			    $shipping_fee=$_POST['shipping_fee'];
		    }
		    else{
		    	$shipping_fee=0;
		    }
		    $chargeModel = new ChargeModel();
			    $chargeModel->setType('SHIPPING')
			        ->setAmount(new Currency(array('value' => $shipping_fee, 'currency' => get_option('woocommerce_currency'))));
	    }
	    if(isset($_POST['plan_tax'])){
	    	if($_POST['plan_tax']>0){
			    $plan_tax=$_POST['plan_tax'];
		    }
		    else{
		    	$plan_tax=0;
		    }
		    $chargeModel1 = new ChargeModel();
			    $chargeModel1->setType('TAX')
			      	->setAmount(new Currency(array('value' => $plan_tax, 'currency' => get_option('woocommerce_currency'))));
	    }
	    if($_POST['plan_tax']>0 || $_POST['shipping_fee']>0){
	    	$paymentDefinition->setChargeModels(array($chargeModel,$chargeModel1));
	    }
	    if(isset($_POST['setup_price'])){
	      	if(empty($_POST['setup_price'])){
	        	$setup_price=0;
	      	}
	      	else{
	        	$setup_price=$_POST['setup_price'];
	      	}
	    }
	    else{
	      	$setup_price=0;
	    }
	    $merchantPreferences = new MerchantPreferences();
	    $baseUrl = home_url()."/checkout/order-received";
	    $cancelUrl=home_url()."/checkout/";
	    if($setup_price > 0){
		    $merchantPreferences->setReturnUrl("$baseUrl/?paypal=true&success=true&action=paypal_subscription")->setCancelUrl("$cancelUrl/?paypal=true&success=false")
		        ->setAutoBillAmount("yes")
		        ->setInitialFailAmountAction("CONTINUE")
		        ->setMaxFailAttempts("0")
		        ->setSetupFee(new Currency(array('value' => $setup_price, 'currency' => get_option('woocommerce_currency'))));
	    }else{
		    $merchantPreferences->setReturnUrl("$baseUrl/?paypal=true&success=true&action=paypal_subscription")->setCancelUrl("$cancelUrl/?paypal=true&success=false")
		        ->setAutoBillAmount("yes")
		        ->setInitialFailAmountAction("CONTINUE")
		        ->setMaxFailAttempts("0");
	    }
	    $plan->setPaymentDefinitions(array($paymentDefinition));
	    $plan->setMerchantPreferences($merchantPreferences);
	    $request = clone $plan;
	    try {
	        $createdPlan = $plan->create($apiContext);
	    }catch(Exception $ex){
	    	$result['action']="failed";
	    	$result['data']=json_decode($ex->getData());
	    	$result=json_encode($result);
			echo $result;
			die();
	    }
	    if(strlen($createdPlan->id)>0){
		    $patch = new Patch();
		    $value = new PayPalModel('{
		    	       "state":"ACTIVE"
		    	     }');
		    $patch->setOp('replace')
		          ->setPath('/')
		          ->setValue($value);
		    $patchRequest = new PatchRequest();
		    $patchRequest->addPatch($patch);
		    $plan1 = Plan::get($createdPlan->id, $apiContext);
		    try {
		        $plan1->update($patchRequest, $apiContext);
		        $result['action']="success";
		        $result['data']='<div class="options_group plan_list '.$plan1->id.'">
							<p class="form-field '.$plan1->id.'">
								<input type="radio" id="plan" name="plan" data-value="'.$plan1->id.'">
								<label for="plan">'.$plan1->name." - ".get_woocommerce_currency_symbol()."".$plan1->payment_definitions[0]->amount->value.'</label>
							</p>
						</div>';
				$result=json_encode($result);
				echo $result;
		        ?>	        
		        <?php
		    } catch (Exception $ex) {
		    	$result['action']="failed";
		    	$result['data']=json_decode($ex->getData());
		    	$result=json_encode($result);
				echo $result;
				die();
		    }
		}
	    die();
	}
	add_action("wp_ajax_nopriv_load_paypal_subscription_plan", "load_paypal_subscription_plan");
	add_action("wp_ajax_load_paypal_subscription_plan", "load_paypal_subscription_plan");
	function load_paypal_subscription_plan(){
		$page=$_POST['page'];
		$client_id=get_option('woocommerce_paypalsubscription_settings')['paypal_client_id'];
		$client_secret=get_option('woocommerce_paypalsubscription_settings')['paypal_client_secret'];
		$test_mode=get_option('woocommerce_paypalsubscription_settings')['sandbox_mode'];
		$apiContext = new ApiContext( new OAuthTokenCredential( $client_id, $client_secret ) );
		if($test_mode=="yes"){
	        $apiContext->setConfig(array('mode'=>'sandbox'));
	    }else{
	       	$apiContext->setConfig(array('mode'=>'live'));
	    }
	    try {
		  	$params = array('page_size' => '5','page'=>$page,'status'=>'active');
		  	$planList = Plan::all($params,$apiContext);
		  	if(is_array($planList->plans)){
		  		$result['action']="success";
		  		$result['data']="";
	        	foreach($planList->plans as $key=>$plan){
		      		try {	
		          		$plan_data = Plan::get($plan->id, $apiContext);
		          		$result['data'].='<div class="options_group plan_list '.$plan_data->id.'">
							<p class="form-field '.$plan_data->id.'">
								<input type="radio" id="plan" name="plan" ';
						if(get_post_meta( $_POST['post_id'], 'plan_id', true )==$plan_data->id){
							$result['data'].='checked="checked" ';
						}
						$result['data'].='data-value="'.$plan_data->id.'">
								<label for="plan">'.$plan_data->name." - ".get_woocommerce_currency_symbol()."".$plan_data->payment_definitions[0]->amount->value.'</label>
							</p>
						</div>';
		          		?>
		          		<?php
		          	}catch (Exception $ex) {
	          			echo $ex->getMessage();
	     			}
	      		}
	      		$result=json_encode($result);
	      		echo $result;
	     	}
	     	else{
	     		$result['action']="complete";
	     		$result['data']='<p id="no_more_plans" class="form-field">No more plans.</p>';
	     		$result=json_encode($result);
	     		echo $result;
	     	}
	    }catch(Exception $ex){
	    	echo $ex->getMessage();
	    }
		die();
	}
	add_action("wp_ajax_nopriv_save_paypal_subscription_plan", "save_paypal_subscription_plan");
	add_action("wp_ajax_save_paypal_subscription_plan", "save_paypal_subscription_plan");
	function save_paypal_subscription_plan(){
		$post_id=$_POST['post_id'];		
		update_post_meta($post_id,'_virtual','yes');
		update_post_meta($post_id, "plan_id", $_POST['paln_id']);
		$client_id=get_option('woocommerce_paypalsubscription_settings')['paypal_client_id'];
		$client_secret=get_option('woocommerce_paypalsubscription_settings')['paypal_client_secret'];
		$test_mode=get_option('woocommerce_paypalsubscription_settings')['sandbox_mode'];
		$apiContext = new ApiContext( new OAuthTokenCredential( $client_id, $client_secret ) );
		if($test_mode=="yes"){
	        $apiContext->setConfig(array('mode'=>'sandbox'));
	    }else{
	       	$apiContext->setConfig(array('mode'=>'live'));
	    }
	    try {
	    	$plan = Plan::get($_POST['plan_id'], $apiContext);
	    	$price=$plan->payment_definitions[0]->amount->value;    	
	    	update_post_meta($post_id,"_regular_price",$price);    	
	    	$result['action']='success';
	    	$result['data']=$plan->payment_definitions[0]->amount->value;
			$result=json_encode($result);
			echo $result;
			die();
	    }catch(Exception $ex){
	    	$result['action']='failed';
	    	$result['data']='<p class="form-field save_plan_error">'.$ex->getMessage().'</p>';
			$result=json_encode($result);
			echo $result;
	    	die();
	    }
	    die();		
	}
}
if(!in_array('woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins')))) 
	return;
add_action( 'plugins_loaded', 'wc_paypal_subscription_init', 11 );
function wc_paypal_subscription_init() {
    if (!class_exists('WC_Payment_Gateway')) {
	    ?>
	    <div id="message" class="error">
	      <p><?php printf(__('%sWooCommerce Paypal Subscription Extension is inactive.%s The %sWooCommerce plugin%s must be active for the WooCommerce Paypal Subscription Extension to work. Please %sinstall & activate WooCommerce%s', 'wc_paypalsubscription'), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url('plugins.php') . '">', '&nbsp;&raquo;</a>'); ?></p>
	    </div>
	    <?php
	    return;
	}
	global $woocommerce;
	// Check the WooCommerce version...
	if (!version_compare($woocommerce->version, '2.1', ">=")) {
	    ?>
	    <div id="message" class="error">
	      <p><?php printf(__('%sWooCommerce Paypal Subscription Extension is inactive.%s The version of WooCommerce you are using is not compatible with this verion of the Paypal Subscription Extension. Please update WooCommerce to version 2.1 or greater, or remove this version of the Paypal Subscription Extension and install an older version.', 'wc_paypalsubscription'), '<strong>', '</strong>'); ?></p>
	    </div>
	    <?php
	    return;
	}
	//paypal payment gatway
	class WC_Paypal_Subscription_Gateway extends WC_Payment_Gateway {
		public function __construct() {
			$this->id = 'paypalsubscription';			
      		$this->has_fields = true;
      		$this->icon = plugins_url('woocomerce-paypal-subscription/img/Paypal-logo.png');
      		$this->method_title = __('Paypal Subscription', 'woocommerce');
      		$this->version = "1.0.0";
      		$this->api_version = "1.0";
      		$this->supports = array('subscriptions', 'products', 'refunds', 'subscription_cancellation', 'subscription_reactivation', 'subscription_suspension', 'subscription_amount_changes', 'subscription_payment_method_change', 'subscription_date_changes');
      		$this->title = get_option('woocommerce_paypalsubscription_settings')['subscription_title'];
      		$this->description = get_option('woocommerce_paypalsubscription_settings')['subscription_description'];
      		$this->init_form_fields();
		    $this->init_settings();
		    add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options')); // < 2.0
      		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		}
	    /**
	     * Initialise Gateway Settings Form Fields
	     */
	    function init_form_fields()
	    {
	      $this->form_fields = array(
	        'enabled' => array(
	          'title' => __('Enable/Disable', 'woocommerce'),
	          'type' => 'checkbox',
	          'label' => __('Enable Paypal Subscription', 'woocommerce'),
	          'default' => 'yes'
	        ),
	        'sandbox_mode' => array(
	          'title' => __('Sandbox Mode', 'woocommerce'),
	          'type' => "checkbox",
	          'description' => __('Switches the gateway to the sandbox', "woocommerce"),
	          'default' => "yes"
	        ),
	        'paypal_client_id' => array(
	          'title' => __('Client ID', 'woocommerce'),
	          'type' => 'text',
	          'description' => 'The Gateway Authentication Client ID',
	          'default' => 'test'
	        ),
	        'paypal_client_secret' => array(
	          'title' => __("Client Secret", 'woocommerce'),
	          'type' => 'text',
	          'description' => __("The Gateway Authentication Client Secret", "woocommerce"),
	          'default' => "test"
	        ),	        
	        'subscription_title' => array(
	          'title' => __("Gateway Name", "woocommerce"),
	          'type' => 'text',
	          'description' => __("The Gateway Name", "woocommerce"),
	          'default' => "Paypal Subscription"
	        ),
	        'subscription_description' => array(
	          'title' => __("Gateway Description", "woocommerce"),
	          'type' => "textarea",
	          'description' => __("The Gateway Description", "woocommerce"),
	          'default' => "Paypal Subscription payment method"
	        )
	      );
	    } // End init_form_fields()
	    function process_payment( $order_id ) {
	    	//date_default_timezone_set('Asia/Calcutta');
	    	global $wpdb;	    	
	    	global $woocommerce;
			$order = new WC_Order( $order_id );
			$items = $order->get_items();
			$client_id=get_option('woocommerce_paypalsubscription_settings')['paypal_client_id'];
			$client_secret=get_option('woocommerce_paypalsubscription_settings')['paypal_client_secret'];
			$test_mode=get_option('woocommerce_paypalsubscription_settings')['sandbox_mode'];
			$apiContext = new ApiContext( new OAuthTokenCredential( $client_id, $client_secret ) );
			if($test_mode=="yes"){
		        $apiContext->setConfig(array('mode'=>'sandbox'));
		    }else{
		       	$apiContext->setConfig(array('mode'=>'live'));
		    } 
	        foreach ( $items as $item ) {
	            $product_id = $item['product_id'];
	           	$order_date=explode(" ", $order->order_date);
	            $order_date=date('Y-m-d',strtotime("+1 day",strtotime($order_date[0])))."T".$order_date[1]."Z";	            
	            $paypal_plan_id=get_post_meta($product_id, "plan_id",true);	            
	            $plan1 = Plan::get($paypal_plan_id, $apiContext);
	            $agreement = new Agreement();
	            $agreement->setName($plan1->name)->setDescription($plan1->description)->setStartDate($order_date);
	            $plan = new Plan();
	            $plan->setId($paypal_plan_id);
        		$agreement->setPlan($plan);
        		$payer = new Payer();
		        $payer->setPaymentMethod('paypal');
		        $agreement->setPayer($payer);
		        $shippingAddress = new ShippingAddress();
		        $shippingAddress->setLine1($order->shipping_address_1)->setCity($order->shipping_city)->setState($order->shipping_state)->setPostalCode($order->shipping_postcode)->setCountryCode($order->shipping_country);
		        $agreement->setShippingAddress($shippingAddress);		        
		        $request = clone $agreement;
		        try {
		          	$agreement = $agreement->create($apiContext);
		          	$approvalUrl = $agreement->getApprovalLink();
		          	if($approvalUrl)
		          	{
		          		$parts = parse_url($approvalUrl);
	        			parse_str($parts['query'], $query);
	        			$wpdb->get_results("select * from wp_woocommerce_paypal_subscription_token where token='".$query['token']."'");
	        			if($wpdb->num_rows<1){
	        				$wpdb->insert('wp_woocommerce_paypal_subscription_token', array(
							    'ID' => null,
							    'token' => $query['token'],
							    'order_id' => $order_id,
							    //'agreement_id'=>$agreement->getId(),
							));
	        			}
	        			return array(
							'result' => 'success',
							'redirect' => $approvalUrl
						);
            			exit;
		          	}
		      	} catch (PayPal\Exception\PayPalConnectionException $ex) {
	                print_r($ex->getData()); // Prints the detailed error message
	                die($ex);
		        }
	        }
		}
	}
	function add_your_gateway_class( $methods ) {
		$methods[] = 'WC_Paypal_Subscription_Gateway';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_your_gateway_class' );	
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_paypal_subscription_action_links' );
	function add_paypal_subscription_action_links( $links ) {
		$setting_link = get_paypal_subscription_setting_link();
		$plugin_links = array(
			'<a href="' . $setting_link . '">' . __( 'Settings', 'woocommerce-gateway-paypal' ) . '</a>',					
		);
		return array_merge( $plugin_links, $links );
	}
	function get_paypal_subscription_setting_link() {
		$use_id_as_section = version_compare( WC()->version, '2.6', '>=' );
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paypalsubscription');
	}
}
add_action( 'init','paypal_thank_you_page');
function paypal_thank_you_page(){		
	if($_REQUEST['action']=='paypal_subscription'){
		global $wpdb;
		$token=$_REQUEST['token'];
		$sql="select * from wp_woocommerce_paypal_subscription_token where token='".$token."'";
		$results = $wpdb->get_results($sql);
		foreach( $results as $result ) {
			$order_id=$result->order_id;
		}
		$client_id=get_option('woocommerce_paypalsubscription_settings')['paypal_client_id'];
		$client_secret=get_option('woocommerce_paypalsubscription_settings')['paypal_client_secret'];
		$test_mode=get_option('woocommerce_paypalsubscription_settings')['sandbox_mode'];
		$apiContext = new ApiContext( new OAuthTokenCredential( $client_id, $client_secret ) );
		if($test_mode=="yes"){
	        $apiContext->setConfig(array('mode'=>'sandbox'));
	    }else{
	       	$apiContext->setConfig(array('mode'=>'live'));
	    }
	    $agreement = new Agreement();
	    try{
	    	$agreement->execute($token, $apiContext);
	    }catch(Exception $ex){
	    }	    
		$sql=$wpdb->update('wp_woocommerce_paypal_subscription_token',array('agreement_id'=>$agreement->getId()),array('token'=>$token));
		$test_order = new WC_Order($order_id);
		$test_order->reduce_order_stock();
		WC()->cart->empty_cart();
		$test_order_key = $test_order->order_key;
		$url=home_url()."/checkout/order-received/".$order_id."/?key=".$test_order_key;
		wp_redirect($url);
		exit;
	}
}
add_filter ( 'woocommerce_before_cart' , 'allow_single_quantity_in_cart' );
function allow_single_quantity_in_cart() {
	global $woocommerce;
	$cart_contents  =  $woocommerce->cart->get_cart();
	$i=0;
	$count=$woocommerce->cart->cart_contents_count;
	if($count>1){
		foreach ($cart_contents as $key => $value) {
			$post_id=$value['product_id'];
			$product_type=get_post_meta($post_id,'product_type',true);
			if($product_type=='paypal_subscription'){
				$woocommerce->cart->empty_cart();
				$woocommerce->cart->add_to_cart($post_id, 1);
			}
		}
	}
}
add_action('woocommerce_order_details_after_order_table','paypal_subscription_product_order_details');
function paypal_subscription_product_order_details($order_id){
	global $woocommerce;
	$order=wc_get_order( $order_id );	
	$items=$order->get_items();
	foreach ($items as $key => $item) {		
		$plan_id=get_post_meta($item['product_id'],'plan_id',true);
		$product_type=get_post_meta($item['product_id'],'product_type',true);
		if($product_type=='paypal_subscription'){
			$client_id=get_option('woocommerce_paypalsubscription_settings')['paypal_client_id'];
			$client_secret=get_option('woocommerce_paypalsubscription_settings')['paypal_client_secret'];
			$test_mode=get_option('woocommerce_paypalsubscription_settings')['sandbox_mode'];
			$apiContext = new ApiContext( new OAuthTokenCredential( $client_id, $client_secret ) );
			if($test_mode=="yes"){
		        $apiContext->setConfig(array('mode'=>'sandbox'));
		    }else{
		       	$apiContext->setConfig(array('mode'=>'live'));
		    }
		    $agreement = new Agreement();
		    $plan = new Plan();
			?>
			<h2>Agreement Details</h2>
			<table class="shop_table agreement_details">
				<?php
					try{
						global $wpdb;						
						$agreement_id=$wpdb->get_row( "SELECT * FROM wp_woocommerce_paypal_subscription_token WHERE order_id = '".$order->id."'");
						$agreement_id=$agreement_id->agreement_id;						
						$agreement = \PayPal\Api\Agreement::get($agreement_id, $apiContext);
						$plan = Plan::get($plan_id, $apiContext);						
				?>
				<tbody>
					<tr>
						<td>PLAN</td>
						<td><?php echo $plan->name; ?></td>
					</tr>
					<tr>
						<td>Description</td>
						<td><?php echo $agreement->description; ?></td>
					</tr>
					<tr>
						<td>AMOUNT</td>
						<td><?php echo get_woocommerce_currency_symbol($plan->payment_definitions[0]->amount->currency); echo $plan->payment_definitions[0]->amount->value; ?></td>
					</tr>
					<tr>
						<td>INTERVAL</td>
						<td><?php echo $agreement->plan->payment_definitions[0]->frequency."( Frequency-".$agreement->plan->payment_definitions[0]->frequency_interval." )"; ?></td>
					</tr>					
					<tr>
						<td>Last Payment Date</td>
						<td>
							<?php 
								$last_payment_date=$agreement->agreement_details->last_payment_date;
								$last_payment_date=explode("T", $last_payment_date);
								$last_payment_date=date("jS F, Y", strtotime($last_payment_date[0]));
								echo $last_payment_date; 
							?>
						</td>
					</tr>
					<tr>
						<td>Next Payment Date</td>
						<td>
							<?php 
								$next_billing_date=$agreement->agreement_details->next_billing_date;
								$next_billing_date=explode("T", $next_billing_date);
								$next_billing_date=date("jS F, Y", strtotime($next_billing_date[0]));
								echo $next_billing_date; 
							?>
						</td>
					</tr>
					<tr>
						<td>Final Payment Date</td>
						<td>
							<?php 
								$final_payment_date=$agreement->agreement_details->final_payment_date;
								$final_payment_date=explode("T", $final_payment_date);
								$final_payment_date=date("jS F, Y", strtotime($final_payment_date[0]));
								echo $final_payment_date; 
							?>
						</td>
					</tr>					
					<tr>
						<td>STATE</td>
						<td><?php echo $agreement->state; ?></td>
					</tr>
				</tbody>
				<?php
					}catch(Exception $ex){
						echo $ex->getMessage();
	    			}
				?>
			</table>
			<?php
		}
	}
}
function payment_gateway_disable_simple_product($available_gateways){
	global $woocommerce;
	$cart_contents  =  $woocommerce->cart->get_cart();
	$count=$woocommerce->cart->cart_contents_count;
	if($count>1){		
		unset( $available_gateways['paypalsubscription'] );		
	}
	if($count==1){
		foreach ($cart_contents as $key => $value) {
			$post_id=$value['product_id'];			
			$product_type=get_post_meta($post_id,'product_type',true);
			if($product_type=='paypal_subscription'){
				unset( $available_gateways['bacs'] );
				unset( $available_gateways['cheque'] );
				unset( $available_gateways['cod'] );
				unset( $available_gateways['paypal'] );
			}
			if($product_type!='paypal_subscription'){
				unset( $available_gateways['paypalsubscription'] );
			}
		}
	}
	return $available_gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'payment_gateway_disable_simple_product' );
?>