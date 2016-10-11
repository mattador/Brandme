<?php

/**
 * Generic non-session based callback controller
 */
namespace Frontend\Controllers;
use Phalcon\Http\Response;

/**
 * Class CallbackController
 * @package Frontend\Controllers
 */
class CallbackController extends ControllerBase
{

    /**
     * IPN listener for PayPal deposit confirmation
     */
    public function paypalAction()
    {
        $adaptor = new \Phalcon\Logger\Adapter\File(APPLICATION_LOG_DIR . '/payment-paypal-ipn-' . date('Y-m-d') . '.log');
        $adaptor->info(var_export($_REQUEST, true));
        $response = new Response();
        $response->setStatusCode(200, 'OK');
        return $response;
    }
}
