<?php
namespace Frontend\Services;

use Entities\FactorNetwork;

/**
 * Class Hybrid
 *
 * @package Frontend\Services
 */
class Hybrid extends AbstractService
{

    /**
     * Restore a social session
     *
     * @param $idFactorNetwork
     * @return Adapter
     * @throws Exception
     */
    public function getSocialAdapter($idFactorNetwork)
    {
        /** @var FactorNetwork $networkRecord */
        $networkRecord = FactorNetwork::findFirst('id = '.$idFactorNetwork);
        $networkName = $networkRecord->getNetwork()->getName();
        /** @var \Hybrid\Auth $hybrid */
        $hybrid = $this->di->get('hybrid');
        $hybrid->getAdapter($networkName)->logout();

        switch ($networkName) {
            case 'Twitter';
            case 'Linkedin':
            case 'Tumblr':
            case 'Foursquare':
                $session = array(
                    'hauth_session.twitter.is_logged_in'              => 'i:1;',
                    'hauth_session.twitter.token.access_token'        => serialize($networkRecord->getOauthToken()),
                    'hauth_session.twitter.token.access_token_secret' => serialize($networkRecord->getOauthTokenSecret())
                );
                break;
            case 'Instagram':
            case 'Facebook':
            case 'Google':
            case 'Youtube':
                $session = array
                (
                    'hauth_session.facebook.is_logged_in'       => 'i:1;',
                    'hauth_session.facebook.token.access_token' => serialize($networkRecord->getOauthToken())
                );
                break;
            default:
                throw new Exception($networkName.' not yet implemented.');
        }
        $hybrid->restoreSessionData(serialize($session));

        return $hybrid->getAdapter($networkName);
    }

    /**
     * Checks if a FactorNetwork account is actually active or not (i.e. token is valid)
     *
     * @param $idFactorNetwork
     * @return bool
     * @throws Exception
     */
    public function isSocialAccountConnectionAlive($idFactorNetwork)
    {
        /** @var Adapter $adaptor */
        $adaptor = $this->getSocialAdapter($idFactorNetwork);
        if (!$adaptor->isUserConnected()) {
            return false;
        }
        try {
            if ($adaptor->getUserProfile()) {
                return false;
            }

            return true;
        } catch (\FacebookApiException $e) {
            return false;
        } catch (\HybridException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

}
