<?php
/**
 * PayPal Express Checkout implementation
 * 1. request token with SetExpressCheckout
 * 2. redirect client to PayPal to make the payment
 * 3. i.  capture funds with DoExpressCheckout
 *    ii. cancel payment
 * 4. get transaction detail with GetExpressCheckout
 * 5. if transaction is complete and successful grant credit else cancel or leave in pending
 * 6. if left in pending use IPN notifications: https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNIntro/
 *
 * @see https://developer.paypal.com/docs/classic/express-checkout/integration-guide/ECGettingStarted/#id0832BA0605Z
 */
namespace Frontend\Controllers\Account\Common;

use Frontend\Controllers\Account\AccountControllerBase;
use Entities\FactorActivity;
use Entities\FactorTransaction;
use Entities\Transaction;
use Frontend\Widgets\Currency;
use Frontend\Widgets\Translate;
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\DoExpressCheckoutPaymentRequestDetailsType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentReq;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentRequestType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsReq;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsRequestType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator\Regex as RegexValidator;

/**
 * Prepare PayPal dependency
 */
require_once APPLICATION_VENDOR_DIR.'/autoload.php';

/**
 * Class PaymentController
 *
 * @package Frontend\Controllers\Account\Common
 */
class PaymentController extends AccountControllerBase
{
    const PAYMENT_PAYPAL_CURRENCY  = 'USD';
    const PAYMENT_PAYPAL_SALES_TAX = 0.16; //16%

    /**
     * Returns PayPal configuration
     *
     * @see https://github.com/paypal/sdk-core-php/wiki/Configuring-the-SDK
     * @return array
     */
    protected function getConfiguration()
    {
        $config = $this->getConfig('paypal');
        $mode = APPLICATION_ENV == 'production' ? 'live' : 'sandbox';

        return [
            'mode'            => $mode,
            'log.LogEnabled'  => $config->logEnabled,
            'log.FileName'    => $config->logFileName,
            'log.LogLevel'    => $config->logLevel,
            'acct1.UserName'  => $config->{$mode}->user,
            'acct1.Password'  => $config->{$mode}->password,
            'acct1.Signature' => $config->{$mode}->signature
        ];
    }

    /**
     * @return PayPalAPIInterfaceServiceService
     */
    protected function getService()
    {
        return new PayPalAPIInterfaceServiceService($this->getConfiguration());
    }

    /**
     * @see https://developer.paypal.com/docs/classic/api/merchant/SetExpressCheckout_API_Operation_NVP/
     */
    public function setCheckoutExpressAction()
    {
        //First run through some basic validation
        if (!$this->request->isPost()) {
            $this->redirect('/'.$this->zone.'/finanzas');
        }
        $errors = [];
        $post = $this->getPost();
        $post['deposit'] = (float)preg_replace('/[^\d\.]/', '', $post['deposit']);
        if ((float)$post['deposit'] < 25) { //@todo make this quantities configurable
            $errors[] = Translate::_('The minimum depositable amount is $%amount%', ['amount' => '25.00']);
        }
        if ((float)$post['deposit'] > 10000) {
            $errors[] = Translate::_('The maximum depositable amount is $%amount%', ['amount' => '10,000.00']);
        }
        if (count($errors)) {
            foreach ($errors as $e) {
                $this->error($e);
            }
            $this->redirect('/'.$this->zone.'/finanzas');
        }
        $baseUrl = 'http://'.$_SERVER['SERVER_NAME'];
        $notifyUrl = $baseUrl.'/callback/paypal'; //outside of session scope so we can recieve information from PayPal directly
        $returnUrl = $baseUrl.'/'.$this->zone.'/finanzas/deposito/paypal/pagar';
        $cancelUrl = $baseUrl.'/'.$this->zone.'/finanzas/deposito/paypal/cancelar';

        /** @var \PayPal\EBLBaseComponents\PaymentDetailsItemType $itemDetails */
        $itemDetails = new PaymentDetailsItemType();
        $itemDetails->Name = 'Compra de crédito';
        $itemDetails->Amount = new BasicAmountType(self::PAYMENT_PAYPAL_CURRENCY, $post['deposit']);
        $itemDetails->Quantity = 1;
        $itemDetails->ItemCategory = 'Digital';
        $itemDetails->Tax = new BasicAmountType(self::PAYMENT_PAYPAL_CURRENCY, self::PAYMENT_PAYPAL_SALES_TAX * $post['deposit']);

        /** @var \PayPal\EBLBaseComponents\PaymentDetailsType $paymentDetails */
        $paymentDetails = new PaymentDetailsType();
        $paymentDetails->PaymentDetailsItem[0] = $itemDetails; // there's only one product

        $itemTotalValue = $post['deposit'];
        $taxTotalValue = self::PAYMENT_PAYPAL_SALES_TAX * $post['deposit'];
        $paymentDetails->ItemTotal = new BasicAmountType(self::PAYMENT_PAYPAL_CURRENCY, $itemTotalValue);
        $paymentDetails->TaxTotal = new BasicAmountType(self::PAYMENT_PAYPAL_CURRENCY, $taxTotalValue);
        $paymentDetails->OrderTotal = new BasicAmountType(self::PAYMENT_PAYPAL_CURRENCY, $itemTotalValue + $taxTotalValue);

        $paymentDetails->PaymentAction = 'Sale';

        $paymentDetails->HandlingTotal = 0.00;
        $paymentDetails->InsuranceTotal = 0.00;
        $paymentDetails->ShippingTotal = 0.00;

        //@todo implement post order manipulation logic
        $paymentDetails->NotifyURL = $notifyUrl;

        $setECReqDetails = new SetExpressCheckoutRequestDetailsType();
        $setECReqDetails->PaymentDetails[0] = $paymentDetails;
        $setECReqDetails->CancelURL = $cancelUrl;
        $setECReqDetails->ReturnURL = $returnUrl;
        $setECReqDetails->BuyerEmail = $this->get('account.fiscal')->getPaypalEmail();
        $setECReqDetails->NoShipping = 1; //don't display shipping details
        $setECReqDetails->cppheaderbordercolor = '63457F';
        $setECReqDetails->cppcartbordercolor = '63457F';
        $setECReqDetails->cpplogoimage = $baseUrl.'/img/logo-brandme-paypal.jpg';
        $setECReqDetails->BrandName = 'BrandMe Latin America - Crowd Marketing';
        $setECReqDetails->AllowNote = 1;

        $setECReqType = new SetExpressCheckoutRequestType();
        $setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
        $setECReq = new SetExpressCheckoutReq();
        $setECReq->SetExpressCheckoutRequest = $setECReqType;

        $setECResponse = $this->getService()->SetExpressCheckout($setECReq);
        if ($setECResponse->Ack != 'Success') {
            //@todo log error response
            $this->error(Translate::_('PayPal is not responding right now, please try again later'));
            $this->redirect('/'.$this->zone.'/finanzas');
        }

        //Store token, create transaction but don't increment money, leave it in pending
        /** @var Transaction $txn */
        $txn = new Transaction();
        $txn
            ->setAmount($post['deposit'])
            ->setTax($taxTotalValue)
            ->setStatus('pending')
            ->setType('deposit')
            ->setPaypalToken($setECResponse->Token)
            ->setPaypalSetExpressCheckoutResponse(serialize($setECResponse))
            ->setDescription(Translate::_('Deposit from PayPal'))
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->create();

        $txnFactor = new FactorTransaction();
        $txnFactor
            ->setIdFactor($this->get('id'))
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setIdTransaction($txn->getId())
            ->create();

        $payPalURL = 'https://www.'.($this->getConfiguration()['mode'] == 'sandbox' ? 'sandbox.' : '')
            .'paypal.com/webscr?cmd=_express-checkout&token='.$setECResponse->Token;
        $this->redirect($payPalURL);
    }

    /**
     * Cancel a transaction
     */
    public function cancelAction()
    {
        if (!is_null($this->request->get('token'))) {
            /** @var Transaction $transaction */
            $transaction = Transaction::findFirst(
                'paypal_token = "'.trim($this->request->get('token')).'" AND created_at >= "'.date(
                    'Y-m-d H:i:s',
                    strtotime('NOW -20 MINUTE')
                ).'"'
            );
            if (!$transaction) {
                $this->error(Translate::_('Invalid Transaction'));
                $this->redirect('/'.$this->zone.'/finanzas');
            }
            /** @var FactorTransaction $factorTransaction */
            $factorTransaction = FactorTransaction::findFirst(
                'id_transaction = '.$transaction->getId().' AND id_factor = '.$this->get('id')
            );
            if (!$factorTransaction) {
                $this->error(Translate::_('Invalid Transaction'));
                $this->redirect('/'.$this->zone.'/finanzas');
            }
            $transaction->setStatus('cancelled')->update();
            $this->error(Translate::_('The purchase has been aborted'));
        }
        $this->redirect('/'.$this->zone.'/finanzas');
    }

    /**
     * Returns the PayPal details regarding the transaction, from the token
     *
     * @param $token
     * @return \PayPal\Service\PayPalAPI\GetExpressCheckoutDetailsResponseType
     */
    public function getTransaction($token)
    {
        $getExpressCheckoutDetailsRequest = new GetExpressCheckoutDetailsRequestType($token);
        $getExpressCheckoutReq = new GetExpressCheckoutDetailsReq();
        $getExpressCheckoutReq->GetExpressCheckoutDetailsRequest = $getExpressCheckoutDetailsRequest;

        return $this->getService()->GetExpressCheckoutDetails($getExpressCheckoutReq);
    }

    /**
     * This is when the client has returned to the page, supposedly after having paid the deposit amount
     *
     * @see https://developer.paypal.com/docs/classic/api/merchant/DoExpressCheckoutPayment_API_Operation_NVP/
     */
    public function doCheckoutExpressAction()
    {
        if (!is_null($this->request->get('token')) && !is_null($this->request->get('PayerID'))) {
            /** @var Transaction $transaction */
            $transaction = Transaction::findFirst(
                'paypal_token = "'.trim($this->request->get('token')).'" AND created_at >= "'.date(
                    'Y-m-d H:i:s',
                    strtotime('NOW -50 MINUTE')
                ).'"'
            );
            if (!$transaction) {
                //No record of the transaction even exists
                $this->error(Translate::_('Invalid Transaction'));
                $this->redirect('/'.$this->zone.'/finanzas');
            }
            /** @var FactorTransaction $factorTransaction */
            $factorTransaction = FactorTransaction::findFirst(
                'id_transaction = '.$transaction->getId().' AND id_factor = '.$this->get('id')
            );
            if (!$factorTransaction) {
                //The transaction exists but is not associated with this user, careful we may be being kiddy-scripted
                $this->error(Translate::_('Invalid Transaction'));
                $this->redirect('/'.$this->zone.'/finanzas');
            }
            $transaction->setPaypalPayerId($this->request->get('PayerID'))->update();
            //Ok now let's get the transaction details
            $transactionDetails = $this->getTransaction($this->request->get('token'));
            $transaction->setPaypalGetExpressCheckoutResponse(serialize($transactionDetails))->update();
            if ($transactionDetails->Ack == 'Success') {
                //The client has supposedly dutifully paid, let's verify the amount is the same as the transaction amount first
                if ($transactionDetails->GetExpressCheckoutDetailsResponseDetails->PaymentDetails[0]->ItemTotal->value
                    != $transaction->getAmount()
                ) {
                    $this->error(Translate::_('Invalid transaction amount'));
                    $this->redirect('/'.$this->zone.'/finanzas');
                }

                //Good now lets capture the funds

                $orderTotalDetails = $transactionDetails->GetExpressCheckoutDetailsResponseDetails->PaymentDetails[0]->OrderTotal;
                $orderTotal = new BasicAmountType();
                $orderTotal->currencyID = $orderTotalDetails->currencyID; //we could just use our constant
                $orderTotal->value = $orderTotalDetails->value;

                $paymentDetails = new PaymentDetailsType();
                $paymentDetails->OrderTotal = $orderTotal;

                $baseUrl = 'http://'.$_SERVER['SERVER_NAME'];
                $notifyUrl = $baseUrl.'/callback/paypal'; //outside of session scope so we can recieve information from PayPal directly
                $paymentDetails->NotifyURL = $notifyUrl;

                $DoECRequestDetails = new DoExpressCheckoutPaymentRequestDetailsType();
                $DoECRequestDetails->PayerID = $this->request->get('PayerID');
                $DoECRequestDetails->Token = $this->request->get('token');
                $DoECRequestDetails->PaymentAction = 'Sale';
                $DoECRequestDetails->PaymentDetails[0] = $paymentDetails;

                $DoECRequest = new DoExpressCheckoutPaymentRequestType();
                $DoECRequest->DoExpressCheckoutPaymentRequestDetails = $DoECRequestDetails;

                $DoECReq = new DoExpressCheckoutPaymentReq();
                $DoECReq->DoExpressCheckoutPaymentRequest = $DoECRequest;

                $DoECResponse = $this->getService()->DoExpressCheckoutPayment($DoECReq);
                $transaction->setPaypalDoExpressCheckoutResponse(serialize($DoECResponse))->update();
                if ($DoECResponse->Ack != 'Success') {
                    $transaction->setStatus('cancelled')->update();
                    $this->error('The transaction was not successful');
                    $this->redirect('/'.$this->zone.'/finanzas');
                }
                $paymentInfo = $DoECResponse->DoExpressCheckoutPaymentResponseDetails->PaymentInfo[0];
                switch ($paymentInfo->PaymentStatus) {
                    case 'Completed':
                        //ideal scenario, we succesfully charged the client, now we need to add the credit to their respective account

                        /** @var \Entities\FactorBalance $balance */
                        $balance = $this->get('account.balance');
                        $newBalance = $balance->getBalance() + $transaction->getAmount();
                        $balance
                            ->setBalance($newBalance)
                            ->update();
                        $transaction
                            ->setAuthorization($paymentInfo->TransactionID)
                            ->setStatus('approved')
                            ->update();

                        $activity = new FactorActivity();
                        $activity
                            ->setIdFactor($this->get('id'))
                            ->setMessage(
                                Translate::_(
                                    'You have deposited $%amount% USD to your account balance',
                                    ['amount' => number_format($transaction->getAmount(), 2)]
                                )
                            )
                            ->setCreatedAt(date('Y-m-d H:i:s'))
                            ->create();

                        $factor = $this->get('account');
                        $mail = $this->getMail();
                        $mail->send(
                            $factor->getEmail(),
                            'payment_received',
                            'Brandme - Confirmación de Pago',
                            [
                                'name'         => $factor->getFactorMeta()->getFirstName(),
                                'referral_url' => APPLICATION_HOST.'/registro/'.$factor->getFactorMeta()->getReferralCode(),
                                'new_balance'  => Currency::format($newBalance),
                                'txn_code'     => $transaction->getAuthorization(),
                                'amount'       => Currency::format($transaction->getAmount())
                            ]
                        );
                        $this->success(
                            Translate::_("Your updated account balance is $%amount% USD", ['amount' => number_format($newBalance, 2)])
                        );
                        break;
                    case 'In-Progress':
                    case 'Completed-Funds-Held':
                    case 'Pending':
                        //less ideal but ok see @todo about activating IPN
                        $this->warning(
                            Translate::_('Your transaction is pending payment, please check your PayPal account for further information')
                        );
                        break;
                    case 'Denied':
                    case 'Failed':
                    case 'Expired':
                    case 'None':
                        $transaction
                            ->setAuthorization($paymentInfo->TransactionID)
                            ->setStatus('cancelled')
                            ->update();
                        $this->error(Translate::_('Your payment has been rejected'));
                        break;
                }
            } else {
                $transaction->setStatus('cancelled');
                $this->error(Translate::_('The purchase has been aborted'));
            }
        }
        $this->refreshSession();
        $this->redirect('/'.$this->zone.'/finanzas');
    }


}