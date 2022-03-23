"use strict";

function _classCallCheck(e, t) { if (!(e instanceof t)) throw new TypeError("Cannot call a class as a function") }

function _defineProperties(e, t) {
    for (var n = 0; n < t.length; n++) {
        var a = t[n];
        a.enumerable = a.enumerable || !1, a.configurable = !0, "value" in a && (a.writable = !0), Object.defineProperty(e, a.key, a)
    }
}

function _createClass(e, t, n) { return t && _defineProperties(e.prototype, t), n && _defineProperties(e, n), e }
var LatepointPaymentsCinetpayAddon = function() {
        function e(t) { _classCallCheck(this, e), this.cinetpayKey = t, this.cinetpayCore = null, this.cinetpayOrderId = null, this.ready() }
        return _createClass(e, [{
            key: "ready",
            value: function() {
                var e = this;
                jQuery(document).ready((function() {
                    jQuery("body").on("latepoint:submitBookingForm", ".latepoint-booking-form-element", (function(t, n) {
                        if (!latepoint_helper.demo_mode && n.is_final_submit && "next" == n.direction) {
                            var a = jQuery(t.currentTarget).find('input[name="booking[payment_method]"]').val();
                            switch (a) {
                                case "cinetpay_checkout":
                                    latepoint_add_action(n.callbacks_list, (function() { return e.initPaymentModal(jQuery(t.currentTarget), a) }))
                            }
                        }
                    })), jQuery("body").on("latepoint:nextStepClicked", ".latepoint-booking-form-element", (function(e, t) {
                        if (!latepoint_helper.demo_mode && "payment" == t.current_step) switch (jQuery(e.currentTarget).find('input[name="booking[payment_method]"]').val()) {
                            case "cinetpay_checkout":
                                latepoint_add_action(t.callbacks_list, (function() {}))
                        }
                    })), jQuery("body").on("latepoint:initPaymentMethod", ".latepoint-booking-form-element", (function(e, t) {
                        if ("cinetpay_checkout" == t.payment_method) {
                            var n = jQuery(e.currentTarget);
                            n.find(".latepoint-form");
                            latepoint_add_action(t.callbacks_list, (function() { latepoint_show_next_btn(n) }))
                        }
                    })), jQuery("body").on("latepoint:initStep:payment", ".latepoint-booking-form-element", (function(e, t) {}))
                }))
            }
        }, {
            key: "initPaymentModal",
            value: function(e, t) {
                var n = jQuery.Deferred(),
                    a = (e.find(".latepoint-form"), { action: "latepoint_route_call", route_name: latepoint_helper.cinetpay_payment_options_route, params: e.find(".latepoint-form").serialize(), layout: "none", return_format: "json" });
                return jQuery.ajax({ type: "post", dataType: "json", url: latepoint_helper.ajaxurl, data: a, success: function(t) { "success" === t.status ? t.amount > 0 && t.options ? (t.options.onClose = function() { n.reject({ message: "Checkout form closed" }) }, t.options.callback = function(t) { e.find('input[name="booking[payment_token]"]').length ? e.find('input[name="booking[payment_token]"]').val(t.reference) : e.find(".latepoint-booking-params-w").append('<input type="hidden" value="' + t.reference + '" name="booking[payment_token]" class="latepoint_payment_token"/>'), n.resolve() }, CinetpayPop.setup(t.options).openIframe()) : n.resolve() : n.reject({ message: t.message }) }, error: function(e, t, a) { n.reject({ message: result.error.message }) } }), n
            }
        }]), e
    }(),
    latepointPaymentsCinetpayAddon = new LatepointPaymentsCinetpayAddon(latepoint_helper.cinetpay_key);