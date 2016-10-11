<?php
namespace Frontend\Services;

use Entities\Factor;
use Entities\FactorAuth;
use Entities\FactorBalance;
use Entities\FactorMeta;
use Entities\FactorNetwork;
use Entities\FactorNetworkMeta;
use Entities\FactorReference;
use Entities\FactorRegion;
use Entities\FactorRole;
use Entities\Network;
use Entities\Plan;
use Entities\Role;
use Entities\Social;
use Frontend\Services\Form\Upload\Image;
use Phalcon\Logger;

/**
 * Class Registration
 *
 * @package Frontend\Services
 */
class Registration extends AbstractService
{

    const REGISTRATION_DEFAULT_PLAN_TYPE = 'free';
    /** Brandme Exchange */
    const REGISTRATION_DEFAULT_EXCHANGE = 1;

    /**
     * Create a new factor
     *
     * @param $data
     * @return bool|Factor
     */
    public function create($data)
    {
        $factorAuth = new FactorAuth();
        $confirmationKey = sha1(uniqid($data->basic_data['email'], true));
        $factorAuth
            ->setPasshash($this->getSecurity()->hash($data->basic_data['password']))
            ->setConfirmationKey($confirmationKey)
            ->setRemoteSupport(0)
            ->setIdRole(Role::findFirst('name = "'.$data->account['type'].'"')->getId())
            ->setCreatedAt(date('Y-m-d H:i:s'));

        $referralCode = $this->generateReferralCode();
        $timezone = isset($data->basic_data['timezone']) && in_array($data->basic_data['timezone'], \DateTimeZone::listIdentifiers())
            ? $data->basic_data['timezone'] : Time::DEFAULT_REGIONAL_TIMEZONE;
        $isBrand = isset($data->basic_data['is_brand']);
        $factorMeta = new FactorMeta();
        if (!$isBrand) {
            $factorMeta
                ->setLastName($data->basic_data['last_name'])
                ->setBirthdate(
                    $data->basic_data['date_of_birth_year'].'-'.$data->basic_data['date_of_birth_month'].'-'
                    .$data->basic_data['date_of_birth_day']
                )
                ->setGender($data->basic_data['gender']);
        }
        $factorMeta
            ->setFirstName($data->basic_data['first_name'])
            ->setIsBrand($isBrand ? 1 : 0)
            ->setTimezone($timezone)
            ->setReferralCode($referralCode)
            ->setLocale('es')
            ->setIdPlan(Plan::findFirst('name = "'.self::REGISTRATION_DEFAULT_PLAN_TYPE.'"')->getId())
            ->setRecieveEmails(1)
            ->setCreatedAt(date('Y-m-d H:i:s'));

        $factorBalance = new FactorBalance();
        $factorBalance
            ->setBalance(0.0000)
            ->setReserved(0.0000)
            ->setPendingWithdrawal(0.0000)
            ->setCreatedAt(date('Y-m-d H:i:s'));

        $factorRegion = new FactorRegion();
        $factorRegion
            ->setPostcode($data->basic_data['postcode'])
            ->setCountry($data->basic_data['country'])
            ->setCreatedAt(date('Y-m-d H:i:s'));

        if ($data->account['type'] == 'creator') {
            $factorNetworkCollection = [];

            //every creator by default must belong to brandme so that we can register segments against their brandme account
            $factorNetwork = new FactorNetwork();
            //we just use a random string for this
            $factorNetwork
                ->setOauthToken(sha1(uniqid($data->basic_data['email'], true)))
                ->setAccountAlias($data->basic_data['first_name'].' '.$data->basic_data['last_name'])
                ->setAccountId($referralCode)//re-use the reference key
                ->setIdNetwork(1)//brandme profile id will always be #1
                ->setStatus('linked')
                ->setCreatedAt(date('Y-m-d H:i:s'));

            //fill up dummy data to meet sql constraints
            $factorNetworkMeta = new FactorNetworkMeta();
            $factorNetworkMeta
                ->setStatisticsFollowers(0)
                ->setStatisticsFollowing(0)
                ->setStatisticsStatusUpdates(0)
                ->setStatisticsLists(0)
                ->setCreatedAt(date('Y-m-d H:i:s'));
            $factorNetwork->FactorNetworkMeta = $factorNetworkMeta;
            $factorNetworkCollection[] = $factorNetwork;
            $bio = false;
            $avatar = false;
            if (isset($data->connected) && !empty($data->connected)) {
                foreach ($data->connected as $networkName => $account) {
                    /**
                     * Populate avatar and bio from first network if possible
                     */
                    if (!$bio && property_exists($account['meta_json'], 'description') && $account['meta_json']->description) {
                        $factorMeta->setBio($account['meta_json']->description);
                        $bio = true;
                    };
                    if (!$avatar && property_exists($account['meta_json'], 'photoURL') && $account['meta_json']->photoURL) {
                        $img = Image::uploadFromUrl($account['meta_json']->photoURL, [150, 150, 128, 128], '/content/profile/avatar');
                        if ($img !== false && is_string($img)) {
                            $factorMeta->setAvatar($img);
                        }
                        $avatar = true;
                    };
                    $factorNetwork = new FactorNetwork();
                    $factorNetwork->setOauthToken($account['oauth_token'])
                        ->setOauthTokenSecret($account['oauth_token_secret'])
                        ->setAccountAlias($account['account_alias'])
                        ->setAccountId($account['account_id'])
                        ->setIdNetwork(Network::findFirst('name = "'.ucfirst($networkName).'"')->getId())
                        ->setStatus('linked')
                        ->setCreatedAt(date('Y-m-d H:i:s'));
                    $factorNetworkMeta = new FactorNetworkMeta();
                    $factorNetworkMeta
                        ->setJson(json_encode($account['meta_json']))
                        ->setStatisticsFollowers($account['statistics_followers'])
                        ->setStatisticsFollowing($account['statistics_following'])
                        ->setStatisticsStatusUpdates($account['statistics_status_updates'])
                        ->setStatisticsLists($account['statistics_lists'])
                        ->setCreatedAt(date('Y-m-d H:i:s'));
                    $factorNetwork->FactorNetworkMeta = $factorNetworkMeta;
                    $factorNetworkCollection[] = $factorNetwork;
                }
            }
        }

        $factor = new Factor();
        $factor
            ->setIdExchange(self::REGISTRATION_DEFAULT_EXCHANGE)//this may be overriden if the user was referenced
            ->setEmail($data->basic_data['email'])
            ->setAcceptedTermsAt(date('Y-m-d H:i:s'))
            ->setIsLegacyUser(0)
            ->setCreatedAt(date('Y-m-d H:i:s'));

        $factor->FactorMeta = $factorMeta;
        $factor->FactorAuth = $factorAuth;
        $factor->FactorBalance = $factorBalance;
        $factor->FactorRegion = $factorRegion;
        if ($data->account['type'] == 'creator') {
            $factor->FactorNetwork = $factorNetworkCollection;
        }
        if (isset($data->reference) && !empty($data->reference)) {
            /** @var Factor $referencer */
            $referencer = FactorMeta::findFirst('referral_code = "'.$data->reference.'"')->getFactor();
            if ($referencer) {
                $factorReference = new FactorReference();
                $factorReference
                    ->setIdFactorReferencedBy($referencer->getId())
                    ->setCreatedAt(date('Y-m-d H:i:s'));
                $factor->FactorReference = $factorReference;

                //Assign new user to the same exchange which referencer belongs
                $factor->setIdExchange($referencer->getIdExchange());
            }
        }
        $result = $factor->create();
        if (!$result) {

            foreach ($factor->getMessages() as $err) {
                $this->log('Registration Error: '.$err->getMessage());
            }

            return false;
        }

        return $factor;
    }

    /**
     * @return string
     */
    public function generateReferralCode()
    {
        $reference = substr(md5(microtime()), rand(0, 26), 6);
        while (!preg_match('/\d/', $reference) || FactorMeta::count('referral_code = "'.$reference.'"') > 0) {
            $reference = $this->generateReferralCode();
        }

        return $reference;
    }
}