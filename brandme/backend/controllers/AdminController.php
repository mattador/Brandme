<?php

namespace Backend\Controllers;

use Common\Services\Campaign\Statuses;
use Common\Services\Sql;
use Entities\CampaignOpportunity;
use Entities\Factor;
use Entities\FactorActivity;
use Entities\FactorAdminAuth;
use Entities\FactorBalance;
use Entities\FactorTransaction;
use Entities\Transaction;
use Frontend\Services\Campaign\Pricing;
use Frontend\Widgets\Currency;
use Frontend\Widgets\Translate;
use Phalcon\Validation;

/**
 * Class AdminController
 *
 * @package Backend\Controllers
 */
class AdminController extends BaseController
{

    public function indexAction()
    {
    }

    /**
     * Add a monetary USD value to a Factor's balance
     */
    public function createTransactionAction()
    {
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            $errors = [];
            if (!isset($post['deposit'])) {
                $errors[] = Translate::_('Please specify an amount');
            }
            if (!isset($post['email']) || !Factor::count('email = "'.$post['email'].'"')) {
                $errors[] = Translate::_('Please a valid email which exists');
            }
            $post['deposit'] = (float)preg_replace('/[^\d\.]/', '', $post['deposit']);
            if ((float)$post['deposit'] < Pricing::STANDARD_MARKUP) { //@todo make this quantities configurable
                $errors[] = Translate::_('The minimum depositable amount is $%amount%', ['amount' => Pricing::STANDARD_MARKUP]);
            }
            if (count($errors)) {
                foreach ($errors as $err) {
                    $this->error($err);
                }
            } else {
                /** @var Factor $factor */
                $factor = Factor::findFirst('email = "'.$post['email'].'"');
                /** @var Transaction $txn */
                $txn = new Transaction();
                $txn
                    ->setAmount((float)$post['deposit'])
                    ->setTax(0.0000)
                    ->setStatus('approved')
                    ->setType('deposit')
                    ->setDescription(Translate::_('Deposit from BrandMe'))
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->create();
                /** @var FactorTransaction $txnFactor */
                $txnFactor = new FactorTransaction();
                $txnFactor
                    ->setIdFactor($factor->getId())
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->setIdTransaction($txn->getId())
                    ->create();
                /** @var FactorBalance $balance */
                $balance = $factor->getFactorBalance();
                $balance
                    ->setBalance($balance->getBalance() + $txn->getAmount())
                    ->update();
                $this->success('$'.Currency::format($txn->getAmount()).' USD ha sido depositado a la cuenta de '.$factor->getEmail());
            }
        }
    }

    /**
     * Creates a single-sign-on password, allowing access to ANY account, within one minute of creation
     */
    public function securityAction()
    {
        if ($this->request->isPost()) {
            //kill existing active accesses
            $phql = 'UPDATE Entities\FactorAdminAuth SET is_usable = 0 WHERE id_factor = '.$this->s->get('id');
            $this->getDI()->get('models')->executeQuery($phql);
            //create new token
            $passhash = substr(md5(microtime()), rand(0, 26), 12);
            $auth = new FactorAdminAuth();
            $auth
                ->setIdFactor($this->s->get('id'))
                ->setIsUsable(1)
                ->setPasshash($this->getDI()->get('security')->hash($passhash))
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->create();
            $this->view->setVar('temporary_key', $passhash);
        }
    }

    public function opportunitiesAction()
    {

        if ($this->request->isPost()) {
            $post = $this->getPost();
            if (!isset($post['id_opportunity']) || !is_numeric($post['id_opportunity'])) {
                $this->redirect('/admin/oportunidades');
            }
            /** @var CampaignOpportunity $opportunity */
            $opportunity = CampaignOpportunity::findFirst('id = '.$post['id_opportunity']);
            $advertiserFactor = $opportunity->getCampaign()->getFactor();
            $advertiserFactorMeta = $advertiserFactor->getFactorMeta();
            if (isset($post['approve'])) {
                $status = strtotime($opportunity->getDateStart()) > time() ? Statuses::OPPORTUNITY_PENDING_EXECUTION
                    : Statuses::OPPORTUNITY_EXECUTING;
                $opportunity->setStatus($status)->update();
                $this->success(Translate::_('Opportunity Accepted'));
                $activity = new FactorActivity();
                $activity
                    ->setIdFactor($advertiserFactor->getId())
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->setMessage(
                        Translate::_(
                            'The opportunity %opportunity_name% has been approved and is now active in the marketplace',
                            ['opportunity_name' => $opportunity->getName()]
                        )
                    )
                    ->create();
                $mail = $this->getMail();
                $mail->send(
                    $advertiserFactor->getEmail(),
                    'opportunity_accepted',
                    'Brandme - Oportunidad Aprobada',
                    [
                        'name'             => $advertiserFactorMeta->getFirstName(),
                        'opportunity_name' => $opportunity->getName(),
                        'referral_url'     => $advertiserFactorMeta->getReferralCode()
                    ]
                );
            } elseif (isset($post['reject'])) {
                if (!isset($post['rejection_message']) || !strlen(trim($post['rejection_message']))) {
                    $this->error(Translate::_('You must include a valid rejection motive'));
                    $this->redirect('/admin/oportunidades');
                }
                if (strlen(trim($post['rejection_message'])) > 100) {
                    $this->error(Translate::_('Your rejection message must not exceed 100 characters'));
                    $this->redirect('/admin/oportunidades');
                }
                $opportunity
                    ->setRejectionStatusMessage(trim($post['rejection_message']))
                    ->setStatus(Statuses::OPPORTUNITY_REVIEW_REJECTED)
                    ->setUpdatedAt(date('Y-m-d H:i: s'))
                    ->update();
                $this->success(Translate::_('Opportunity Rejected'));
                $activity = new FactorActivity();
                $activity
                    ->setIdFactor($advertiserFactor->getId())
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->setMessage(
                        Translate::_(
                            'The opportunity %opportunity_name% has been rejected',
                            ['opportunity_name' => $opportunity->getName()]
                        )
                    )
                    ->create();
                $mail = $this->getMail();
                $mail->send(
                    $advertiserFactor->getEmail(),
                    'opportunity_rejected',
                    'Brandme - Oportunidad Rechazada',
                    [
                        'name'              => $advertiserFactorMeta->getFirstName(),
                        'opportunity_name'  => $opportunity->getName(),
                        'rejection_message' => trim($post['rejection_message']),
                        'referral_url'      => $advertiserFactorMeta->getReferralCode()
                    ]
                );
            }
        }
        $sql
            = 'SELECT
                    f.email,
                    c.id id_campaign,
                    c.budget,
                    co.id id_campaign_opportunity,
                    co.name opportunity_name,
                    co.logo,
                    c.name campaign_name,
                    co.description,
                    co.requirements,
                    co.ideal_creator,
                    co.created_at
                FROM campaign c
                INNER JOIN campaign_opportunity co ON co.id_campaign = c.id
                INNER JOIN factor f ON f.id = c.id_factor
                WHERE co.status IN ('.Statuses::OPPORTUNITY_IN_REVIEW.')
                ORDER BY co.created_at ASC';
        $campaigns = Sql::find($sql);
        $this->view->setVars(
            [
                'campaigns'         => $campaigns,
                'rejection_message' => Translate::_(
                    'This opportunity did not meet the BrandMe\'s standards and/or conditions of use'
                )
            ]
        );
    }


}

