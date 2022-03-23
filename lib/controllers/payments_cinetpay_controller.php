<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}


if ( ! class_exists( 'OsPaymentsCinetpayController' ) ) :


  class OsPaymentsCinetpayController extends OsController {


    function __construct(){
      parent::__construct();

      $this->action_access['customer'] = array_merge($this->action_access['customer'], ['get_payment_options']);
      $this->views_folder = plugin_dir_path( __FILE__ ) . '../views/payments_cinetpay/';
    }

    /* Generates payment options for Cinetpay payment modal */
    public function get_payment_options(){
      OsStepsHelper::set_booking_object($this->params['booking']);
      OsStepsHelper::set_restrictions($this->params['restrictions']);
      $customer = OsAuthHelper::get_logged_in_customer();

      $amount = OsStepsHelper::$booking_object->specs_calculate_price_to_charge();
      
      if($amount > 0){

        $cinetpay_customer_id = $customer->get_meta_by_key('cinetpay_customer_id');
        if(!$cinetpay_customer_id){
          $cinetpay_customer = OsPaymentsCinetpayHelper::create_customer($customer);
          $customer->save_meta_by_key('cinetpay_customer_id', $cinetpay_customer->id);
        }

        $options = [
              "key" => OsPaymentsCinetpayHelper::get_publishable_key(),
              "email" => $customer->email,
              "amount" => $amount,
              "currency" => OsPaymentsCinetpayHelper::get_currency_iso_code(),
        ];
        if($this->get_return_format() == 'json'){
          $this->send_json(array('status' => LATEPOINT_STATUS_SUCCESS, 'message' => __('Order Created', 'latepoint-payments-cinetpay'), 'options' => $options, 'amount' => $amount));
        }
      }else{
        // free booking, nothing to pay (probably coupon was applied)
        if($this->get_return_format() == 'json'){
          $this->send_json(array('status' => LATEPOINT_STATUS_SUCCESS, 'message' => __('Nothing to pay', 'latepoint-payments-cinetpay'), 'amount' => $amount));
        }
      }
    }
  }


endif;
