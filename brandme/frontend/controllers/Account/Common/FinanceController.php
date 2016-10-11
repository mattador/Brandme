<?php

namespace Frontend\Controllers\Account\Common;

use Entities\FactorActivity;
use Entities\FactorFiscal;
use Entities\FactorTransaction;
use Entities\FactorTxn;
use Entities\Transaction;
use Frontend\Controllers\Account\AccountControllerBase;
use Frontend\Module;
use Frontend\Services\Finance;
use Frontend\Widgets\Translate;
use Phalcon\Paginator\Adapter\NativeArray as PaginatorArray;
use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\Regex as RegexValidator;

class FinanceController extends AccountControllerBase
{

    public function indexAction()
    {
        if ($this->request->isPost()) {
            $post = $this->getPost();

            if (!$this->security->checkToken() || !isset($post['finanzas'])
                || !in_array(
                    $post['finanzas'],
                    ['paypal', 'configuration', 'withdraw', 'deposit']
                )
            ) {
                $this->redirect('/'.$this->zone.'/finanzas');
            }
            /** @var \Entities\Factor $factor */
            $factor = $this->get('account');
            /** @var \Entities\FactorMeta $meta */
            $meta = $this->get('account.meta');
            $validation = new Validation();
            $errors = [];
            switch ($post['finanzas']) {
                case 'paypal':
                    $validation->add(
                        'paypal_email',
                        new EmailValidator(
                            array(
                                'message' => 'You you must enter a valid email associated with your PayPal account'
                            )
                        )
                    );
                    break;
                case 'configuration':
                    if (strlen(trim($post['rfc'])) > 0) {
                        $post['rfc'] = preg_replace('/\s/', '', $post['rfc']);
                        $validation->add(
                            'rfc',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[A-Za-z]{3,4}[ |\-]{0,1}[0-9]{6}[ |\-]{0,1}[0-9A-Za-z]{3}$/',
                                    'message' => 'Your chosen RFC is invalid'
                                )
                            )
                        );
                    }
                    break;
                case 'withdraw':
                    $this->redirect('/'.$this->zone.'/finanzas'); //temporarily redirect
                    //@todo implement plan specific rules
                    $validation->add(
                        'withdraw',
                        new RegexValidator(
                            array(
                                'pattern' => '/^\$?\d+(,\d{3})*\.?[0-9]?[0-9]?$/',
                                'message' => 'Please enter a valid withdrawal amount'
                            )
                        )
                    );
                    $post['withdraw'] = preg_replace('/[^\d\.]/', '', $post['withdraw']);
                    $currentBalance = $this->get('account.balance')->getBalance();
                    if ((float)$post['withdraw'] > $currentBalance) {
                        $errors[] = 'You cannot withdraw more than your current balance';
                    }
                    if ((float)$post['withdraw'] < 25) {
                        $errors[] = Translate::_('You cannot withdraw less than $%amount%', ['amount' => 25.00]);
                    }
                    break;
            }


            if (!is_null($validation->getValidators())) {
                $validation->validate($post);
            }
            if (!is_null($validation->getMessages())) {
                foreach ($validation->getMessages() as $err) {
                    $errors[] = $err;
                }
            }
            if (count($errors)) {
                //Some errors were detected
                $this->view->setVar('messages', $errors);
            } else {
                switch ($post['finanzas']) {
                    case 'paypal':
                        $paypalEmail = strlen(trim($post['paypal_email'])) > 0 ? trim($post['paypal_email']) : null;
                        if ($paypalEmail) {
                            /** @var \Entities\FactorFiscal $fiscal */
                            $fiscal = $this->get('account.fiscal');
                            if (!$fiscal) {
                                $fiscal = new FactorFiscal();
                                $fiscal
                                    ->setCreatedAt(date('Y-m-d H:i:s'))
                                    ->setPaypalEmail($paypalEmail);
                                $factor->FactorFiscal = $fiscal;
                                $factor->save();
                            } else {
                                $fiscal->setPaypalEmail($paypalEmail);
                                $fiscal->save();
                            }
                            $activity = new FactorActivity();
                            $activity
                                ->setIdFactor($this->get('id'))
                                ->setMessage(
                                    Translate::_(
                                        'You have added %paypal_email% as your primary PayPal email',
                                        ['paypal_email' => $paypalEmail]
                                    )
                                )
                                ->setCreatedAt(date('Y-m-d H:i:s'))
                                ->create();
                            $mail = $this->getMail();
                            $mail->send(
                                $factor->getEmail(),
                                'added_paypal_email',
                                'Brandme - Cuenta de PayPal Guardada',
                                [
                                    'name'         => $factor->getFactorMeta()->getFirstName(),
                                    'paypal_email' => $paypalEmail,
                                    'referral_url' => APPLICATION_HOST.'/registro/'.$factor->getFactorMeta()->getReferralCode()
                                ]
                            );
                            $this->flash->success(Translate::_('PayPal Email added successfully'));
                        } else {
                            $this->flash->warning(Translate::_('You have no PayPal account associated with BrandM   e'));
                        }
                        break;
                    case 'configuration':
                        $tax = strlen(trim($post['rfc'])) > 0 ? trim($post['rfc']) : null;
                        /** @var \Entities\FactorFiscal $fiscal */
                        $fiscal = $this->get('account.fiscal');
                        if (!$fiscal) {
                            $fiscal = new FactorFiscal();
                            $fiscal
                                ->setCreatedAt(date('Y-m-d H:i:s'))
                                ->setTaxId($tax);
                            $factor->FactorFiscal = $fiscal;
                            $factor->update();
                        } else {
                            $fiscal
                                ->setTaxId($tax)
                                ->update();
                        }
                        $this->flash->success(Translate::_('Configuration updated successfully'));
                        break;
                    case 'withdraw':
                        /** @var \Entities\FactorBalance $balance */
                        $balance = $this->get('account.balance');
                        $newBalance = $balance->getBalance() - $post['withdraw'];
                        //@todo implement paypal
                        $balance->setBalance($newBalance);
                        $balance->update();

                        $txn = new Transaction();
                        $txn
                            ->setAmount(-$post['withdraw'])
                            ->setStatus('complete')
                            ->setType('withdrawal')
                            ->setDescription(Translate::_('Withdrawal to PayPal'))
                            ->setCreatedAt(date('Y-m-d H:i:s'))
                            ->create();

                        $txnFactor = new FactorTransaction();
                        $txnFactor
                            ->setIdFactor($this->get('id'))
                            ->setIdTransaction($txn->getId())
                            ->setCreatedAt(date('Y-m-d H:i:s'))
                            ->create();

                        $activity = new FactorActivity();
                        $activity
                            ->setIdFactor($this->get('id'))
                            ->setMessage(
                                Translate::_(
                                    'You have withdrawn $%amount% USD from your account balance to PayPal',
                                    ['amount', number_format($post['withdraw'], 2)]
                                )
                            )
                            ->setCreatedAt(date('Y-m-d H:i:s'))
                            ->create();

                        $this->flash->success(
                            Translate::_(
                                '$%amount% USD will be transferred to your configured PayPal account',
                                ['amount' => number_format($post['withdraw'], 2)]
                            )
                        );
                        break;
                }
                $this->refreshSession('/'.$this->zone.'/finanzas');
            }
        }
        $this->addAssets('js/jquery.maskMoney.min.js', 'js/brandme/common-finance.js');
        /** @var Finance $financeService */
        $financeService = Module::getService('Finance');
        $conf = $financeService->getFactorFinanceData($this->get('id'));
        $transactions = $financeService->getFactorTransactions($this->get('id'));
        $currentPage = !isset($_GET["p"]) || (int)$_GET["p"] < 0 ? 1 : (int)$_GET["p"];
        $paginator = new PaginatorArray(
            array(
                "data"  => $transactions,
                "limit" => 25,
                "page"  => $currentPage
            )
        );
        $paginatedTransactions = $paginator->getPaginate();
        if ($currentPage > $paginatedTransactions->total_pages) {
            $paginator = new PaginatorArray(
                array(
                    "data"  => $transactions,
                    "limit" => 25,
                    "page"  => $paginatedTransactions->total_pages
                )
            );
            $paginatedTransactions = $paginator->getPaginate();
        }

        $this->view->setVars(
            [
                'address'          => [
                    'street'   => trim(
                        preg_replace('/\w+/', ' ', $conf['exterior_number'].' '.$conf['interior_number'].' '.$conf['street'])
                    ),
                    'suburb'   => $conf['suburb'],
                    'colony'   => $conf['colony'],
                    'state'    => $conf['state'],
                    'city'     => $conf['city'],
                    'postcode' => $conf['postcode'],
                    'country'  => $conf['country']
                ],
                'paypalEmail'      => $conf['paypal_email'],
                'rfc'              => $conf['tax_id'],
                'telephone'        => $conf['telephone'],
                'finance_balance'  => $this->get('account.balance')->getBalance(),
                'finance_reserved' => $this->get('account.balance')->getReserved(),
                'zone'             => $this->zone,
                'transactions'     => $paginatedTransactions
            ]
        );
    }
}