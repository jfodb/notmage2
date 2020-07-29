<?php

namespace Psuedo\Mpxdownload\Model\Stripe;

use StripeIntegration\Payments\Model\PaymentIntent;

class PaymentIntentData extends PaymentIntent
{
	public function confirmAndAssociateWithOrder($order, $payment)
	{
		$retval = parent::confirmAndAssociateWithOrder($order, $payment);

		if(!empty($this->paymentIntent))
			$pi = $this->paymentIntent;
		else
			$pi = $retval;

		if(!empty($pi->charges) && !empty($pi->charges->data)) {

			$chargedata = $pi->charges->data[0];
			$payment->setCcApproval($chargedata->id);

			if(!empty($chargedata->payment_method_details) && !empty($chargedata->payment_method_details->card)) {
				$card = $chargedata->payment_method_details->card;
				$payment->setCcType(strtoupper($card->brand));
				$payment->setCcLast4($card->last4);
				$payment->setCcExpMonth($card->exp_month);
				$payment->setCcExpYear($card->exp_year);
				$payment->setCcNumberEnc($card->fingerprint);
			}
		}

		return $retval;
	}
}