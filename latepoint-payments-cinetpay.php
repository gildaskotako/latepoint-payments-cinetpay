<?php
/**
 * Plugin Name: LatePoint Addon - Payments Hiboutik
 * Plugin URI:  https://latepoint.com/
 * Description: LatePoint addon for payments via Hiboutik
 * Version:     1.0.2
 * Author:      LatePoint
 * Author URI:  https://latepoint.com/
 * Text Domain: latepoint-payments-hiboutik
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

// If no LatePoint class exists - exit, because LatePoint plugin is required for this addon

if ( ! class_exists( 'LatePointPaymentsHiboutik' ) ) :

/**
 * Main Addon Class.
 *
 */

class LatePointPaymentsHiboutik {

  /**
   * Addon version.
   *
   */
  public $version = '1.0.0';
  public $db_version = '1.0.0';
  public $addon_name = 'latepoint-payments-hiboutik';

  public $processor_code = 'hiboutik';



  /**
   * LatePoint Constructor.
   */
  public function __construct() {
    $this->define_constants();
    $this->init_hooks();
  }

  /**
   * Define LatePoint Constants.
   */
  public function define_constants() {
  }


  public static function public_stylesheets() {
    return plugin_dir_url( __FILE__ ) . 'public/stylesheets/';
  }

  public static function public_javascripts() {
    return plugin_dir_url( __FILE__ ) . 'public/javascripts/';
  }

  public static function images_url() {
    return plugin_dir_url( __FILE__ ) . 'public/images/';
  }

  /**
   * Define constant if not already set.
   *
   */
  public function define( $name, $value ) {
    if ( ! defined( $name ) ) {
      define( $name, $value );
    }
  }

  /**
   * Include required core files used in admin and on the frontend.
   */
  public function includes() {

     // COMPOSER AUTOLOAD
     require (dirname( __FILE__ ) . '/vendor/autoload.php');

    // CONTROLLERS
    include_once( dirname( __FILE__ ) . '/lib/controllers/payments_cinetpay_controller.php' );

    // HELPERS
    include_once( dirname( __FILE__ ) . '/lib/helpers/payments_cinetpay_helper.php' );

    // MODELS

  }


  public function init_hooks(){
    add_action('latepoint_init', [$this, 'latepoint_init']);
    add_action('latepoint_includes', [$this, 'includes']);
    //add_action('latepoint_admin_enqueue_scripts', [$this, 'load_admin_scripts_and_styles']);
    add_action('latepoint_payment_processor_settings',[$this, 'add_settings_fields'], 10);
    add_filter('latepoint_installed_addons', [$this, 'register_addon']);
    add_filter('latepoint_payment_processors', [$this, 'register_payment_processor'], 10, 2);
    add_filter('latepoint_all_payment_methods', [$this, 'register_payment_methods']);
    add_filter('latepoint_enabled_payment_methods', [$this, 'register_enabled_payment_methods']);
    add_filter('latepoint_encrypted_settings', [$this, 'add_encrypted_settings']);

    add_filter('latepoint_localized_vars_front', [$this, 'localized_vars_for_front']);
    //add_filter('latepoint_localized_vars_admin', [$this, 'localized_vars_for_admin']);

    //add_filter('latepoint_convert_charge_amount_to_requirements', [$this, 'convert_charge_amount_to_requirements'], 10, 2);


    //add_action('latepoint_wp_enqueue_scripts', [$this, 'load_front_scripts_and_styles']);
    //add_filter('latepoint_prepare_step_vars_for_view', [$this, 'add_vars_for_payment_step'], 10, 3);
    //add_filter('latepoint_prepare_step_booking_object', [$this, 'prepare_booking_object_for_step'], 10, 2);
    add_filter('latepoint_payment_sub_step_for_payment_step', [$this, 'sub_step_for_payment_step']);

    add_action('latepoint_payment_step_content',[$this, 'output_payment_step_contents'], 10, 2);
    add_filter('latepoint_process_payment_for_booking', [$this, 'process_payment'], 10, 3);


    // addon specific filters

    add_action( 'init', array( $this, 'init' ), 0 );

    register_activation_hook(__FILE__, [$this, 'on_activate']);
    register_deactivation_hook(__FILE__, [$this, 'on_deactivate']);
  }


  public function add_encrypted_settings($encrypted_settings){
    $encrypted_settings[] = 'cinetpay_secret_key';
    $encrypted_settings[] = 'cinetpay_webhook_secret';
    return $encrypted_settings;
  }


  public function process_payment($result, $booking, $customer){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      switch($booking->payment_method){
        case 'cinetpay_checkout':
          if($booking->payment_token){
            $cinetpay_transaction = OsPaymentsCinetpayHelper::verify_transaction($booking->payment_token);
            if($cinetpay_transaction && in_array($cinetpay_transaction->status, ['success'])){
              $result['status'] = LATEPOINT_STATUS_SUCCESS;
              $result['charge_id'] = $cinetpay_transaction->id;
              $result['processor'] = $this->processor_code;
              $result['funds_status'] = $cinetpay_transaction->status == 'authorized' ? LATEPOINT_TRANSACTION_FUNDS_STATUS_AUTHORIZED : LATEPOINT_TRANSACTION_FUNDS_STATUS_CAPTURED;
            }else{
              $result['status'] = LATEPOINT_STATUS_ERROR;
              $result['message'] = __('Payment Error', 'latepoint-payments-cinetpay');
              $booking->add_error('payment_error', $result['message']);
              $booking->add_error('send_to_step', $result['message'], 'payment');
            }
          }else{
            $result['status'] = LATEPOINT_STATUS_ERROR;
            $result['message'] = __('Payment Error 23JDF38', 'latepoint-payments-cinetpay');
            $booking->add_error('payment_error', $result['message']);
          }
        break;
      }
    }
    return $result;
  }


  public function convert_charge_amount_to_requirements($charge_amount, $payment_method){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      if(in_array($payment_method, array_keys($this->get_supported_payment_methods()))){
        $charge_amount = OsPaymentsCinetpayHelper::convert_charge_amount_to_requirements($charge_amount);
      }
    }
    return $charge_amount;
  }


  public function output_payment_step_contents($booking, $enabled_payment_times){
    if(!OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)) return;
  }

  public function sub_step_for_payment_step($sub_step){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      $sub_step = 'payment-method-content';
    }
    return $sub_step;
  }

  public function prepare_booking_object_for_step($booking_object, $step_name){
    if($step_name == 'payment'){
      if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      }
    }
    return $booking_object;
  }

  public function add_vars_for_payment_step($vars, $booking_object, $step_name){
    if($step_name == 'payment'){
      if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){

      }
    }
    return $vars;
  }


  public function get_supported_payment_methods(){
    return [
            'cinetpay_checkout' => [
                        'name' => __('Checkout', 'latepoint-payments-cinetpay'), 
                        'label' => __('Checkout', 'latepoint-payments-cinetpay'), 
                        'image_url' => LATEPOINT_IMAGES_URL.'payment_cards.png',
                        'code' => 'cinetpay_checkout',
                        'time_type' => 'now'
                      ]
          ];
  }


  public function register_enabled_payment_methods($enabled_payment_methods){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      $enabled_payment_methods = array_merge($enabled_payment_methods, $this->get_supported_payment_methods());
    }
    return $enabled_payment_methods;
  }

  // adds payment method to payment settings
  public function register_payment_methods($payment_methods){
    $payment_methods = array_merge($payment_methods, $this->get_supported_payment_methods());
    return $payment_methods;
  }

  public function register_payment_processor($payment_processors, $enabled_only){
    $payment_processors[$this->processor_code] = ['code' => $this->processor_code, 'name' => __('Cinetpay', 'latepoint-payments-cinetpay'), 'image_url' => $this->images_url().'processor-logo.png'];
    return $payment_processors;
  }

  public function add_settings_fields($processor_code){
    if($processor_code != $this->processor_code) return false; ?>
      <h3 class="os-sub-header"><?php _e('API Keys', 'latepoint-payments-cinetpay'); ?></h3>
      <div class="os-row">
        <div class="os-col-6">
          <?php echo OsFormHelper::text_field('settings[cinetpay_site_id]', __('Site Id', 'latepoint-payments-cinetpay'), OsSettingsHelper::get_settings_value('cinetpay_site_id')); ?>
        </div>
        <div class="os-col-6">
          <?php echo OsFormHelper::password_field('settings[cinetpay_api_key]', __('Api Key', 'latepoint-payments-cinetpay'), OsSettingsHelper::get_settings_value('cinetpay_api_key')); ?>
        </div>
      </div>
      <h3 class="os-sub-header"><?php _e('Other Settings', 'latepoint-payments-cinetpay'); ?></h3>
      <?php  
      $selected_cinetpay_country_code = OsSettingsHelper::get_settings_value('cinetpay_country_code', 'GH');
      $country_currencies = OsPaymentsCinetpayHelper::load_currencies_list();
      $selected_cinetpay_currency_iso_code = OsSettingsHelper::get_settings_value('cinetpay_currency_iso_code', 'GHS'); ?>
      <div class="os-row">
        <div class="os-col-6">
          <?php echo OsFormHelper::select_field('settings[cinetpay_country_code]', __('Country', 'latepoint-payments-cinetpay'), OsPaymentsCinetpayHelper::load_countries_list(), $selected_cinetpay_country_code); ?>
        </div>
        <div class="os-col-6">
          <?php echo OsFormHelper::select_field('settings[cinetpay_currency_iso_code]', __('Currency Code', 'latepoint-payments-cinetpay'), $country_currencies, $selected_cinetpay_currency_iso_code); ?>
        </div>
      </div>
    <?php
  }

  /**
   * Init LatePoint when WordPress Initialises.
   */
  public function init() {
    // Set up localisation.
    $this->load_plugin_textdomain();
  }

  public function latepoint_init(){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)) OsPaymentsCinetpayHelper::init();
    LatePoint\Cerber\Router::init_addon();
  }


  public function load_plugin_textdomain() {
    load_plugin_textdomain('latepoint-payments-cinetpay', false, dirname(plugin_basename(__FILE__)) . '/languages');
  }



  public function on_deactivate(){
  }

  public function on_activate(){
    if(class_exists('OsDatabaseHelper')) OsDatabaseHelper::check_db_version_for_addons();
    do_action('latepoint_on_addon_activate', $this->addon_name, $this->version);
  }

  public function register_addon($installed_addons){
    $installed_addons[] = ['name' => $this->addon_name, 'db_version' => $this->db_version, 'version' => $this->version];
    return $installed_addons;
  }




  public function load_front_scripts_and_styles(){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      // Stylesheets

      // Javascripts
     // wp_enqueue_script( 'cinetpay', 'https://js.cinetpay.co/v1/inline.js', false, null );
      wp_enqueue_script( 'latepoint-payments-cinetpay',  $this->public_javascripts() . 'latepoint-payments-cinetpay.js', array('jquery', 'cinetpay', 'latepoint-main-front'), $this->version );
    }

  }

  public function load_admin_scripts_and_styles(){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      // Stylesheets
      wp_enqueue_style( 'latepoint-payments-cinetpay-back', $this->public_stylesheets() . 'latepoint-payments-cinetpay-back.css', false, $this->version );

      // Javascripts
    }
  }


  public function localized_vars_for_admin($localized_vars){
    return $localized_vars;
  }

  public function localized_vars_for_front($localized_vars){
    if(OsPaymentsHelper::is_payment_processor_enabled($this->processor_code)){
      $localized_vars['cinetpay_key'] = OsPaymentsCinetpayHelper::get_publishable_key();
      $localized_vars['is_cinetpay_active'] = true;
      $localized_vars['cinetpay_payment_options_route'] = OsRouterHelper::build_route_name('payments_cinetpay', 'get_payment_options');
    }else{
      $localized_vars['is_cinetpay_active'] = false;

    }
    return $localized_vars;
  }

}

endif;

if ( in_array( 'latepoint/latepoint.php', get_option( 'active_plugins', array() ) )  || array_key_exists('latepoint/latepoint.php', get_site_option('active_sitewide_plugins', array())) ) {
  $LATEPOINT_ADDON_PAYMENTS_CINETPAY = new LatePointPaymentsCinetpay();
}
$latepoint_session_salt = 'YWE4NDNkN2ItMzQwNC00ZGU3LTllNWEtYjBjM2VlODgzY2I5';
