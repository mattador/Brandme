<?php
namespace Frontend\Services;


use Entities\FactorAuth;
use Entities\FactorSession;
use Phalcon\Session\Bag;

/**
 * Class Session
 *
 * @package Frontend\Services
 */
class Session extends AbstractService
{

    public static $roleNamespaces
        = [
            'creator'    => 'creador',
            'advertiser' => 'anunciante',
            'whitelabel' => 'agencia'
        ];

    /**
     * Creates a new login session
     *
     * @param $idFactor
     */
    public function login($idFactor)
    {
        //save session
        session_regenerate_id(true);
        $factorSession = new FactorSession();
        $factorSession->setIdFactor($idFactor);
        $factorSession->setSessionId(session_id());
        $factorSession->setLoggedIn(date('Y-m-d H:i:s'));
        $factorSession->setIpAddress(getenv('REMOTE_ADDR'));
        $factorSession->setUserClient(Browser::getBrowser());
        $factorSession->setCreatedAt(date('Y-m-d H:i:s'));
        $created = $factorSession->create();
        //add logged in flags to session
        $session = new Bag('user_session');
        $session->id = $idFactor;
        $session->logged = time();
    }

    /**
     * Creates a strong 128 char string used as a token for session persistence
     *
     * @see http://php.net/manual/en/function.openssl-random-pseudo-bytes.php
     * @param $idFactor
     * @return string
     */
    public function createNewPersistentToken($idFactor)
    {
        $bytes = openssl_random_pseudo_bytes(64, $cstrong);
        $hex = bin2hex($bytes);
        if (!$cstrong) {
            return $this->createNewPersistentToken($idFactor);
        }
        $expireAt = date('Y-m-d H:i:s', strtotime('+'.($this->getConfig()->persistent_cookie_life / 60).' MINUTES'));
        $phql
            = "UPDATE Entities\FactorAuth
                SET persistent_sess_token = '".$hex."',
                    persistent_sess_token_expire_at = '".$expireAt."',
                    persistent_sess_token_user_client = '".Browser::getBrowser()."'
                    WHERE id_factor = ".$idFactor." LIMIT 1";
        $this->getManager()->executeQuery($phql);

        return $hex;
    }

    /**
     * Purposefully destroys an existing token by factor id
     *
     * @param $idFactor
     */
    public function destroyPersistentToken($idFactor)
    {
        $phql
            = 'UPDATE Entities\FactorAuth
                SET persistent_sess_token = NULL,
                    persistent_sess_token_expire_at = NULL,
                    persistent_sess_token_user_client = NULL
                    WHERE id_factor = '.$idFactor.' LIMIT 1';
        $this->getManager()->executeQuery($phql);
    }

    /**
     * Attempts to initiate a persistent token
     *
     * @param String $token
     * @return String role type
     */
    public function attemptLoginByPersistentToken($token)
    {
        /** @var \Entities\FactorAuth $auth */
        $auth = FactorAuth::findFirst("persistent_sess_token = '".$token."'");
        if (!$auth) {
            return false;
        }

        //token has expired
        if (strtotime($auth->getPersistentSessTokenExpireAt()) < time()) {
            $this->destroyPersistentToken($auth->getIdFactor());

            return false;
        }
        //User client is not the same as the one used to create the token
        if ($auth->getPersistentSessTokenUserClient() != Browser::getBrowser()) {
            $this->destroyPersistentToken($auth->getIdFactor());

            return false;
        }
        $this->login($auth->getIdFactor());

        return $auth->Role->getName();
    }

}