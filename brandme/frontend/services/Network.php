<?php
namespace Frontend\Services;


use Frontend\Exception;

class Network extends AbstractService
{

    const NETWORK_BRANDME = 'Brandme';
    const NETWORK_FACEBOOK = 'Facebook';
    const NETWORK_TWITTER = 'Twitter';
    const NETWORK_TWITTER_CONTENT_LIMIT = 140;

    /**
     * Loads network adaptor
     *
     * @todo This is kind of repeated in the HybridcliController, merge the two
     *
     * @param $network
     * @param $token
     * @param null $secret
     * @return Adapter
     * @throws Exception
     */
    public function getHybridNetworkAdapter($network, $token, $secret = null)
    {
        switch ($network) {
            case 'twitter':
            case 'Twitter';
                $session = array(
                    'hauth_session.twitter.token.access_token' => serialize($token),
                    'hauth_session.twitter.token.access_token_secret' => serialize($secret),
                    'hauth_session.twitter.is_logged_in' => 'i:1;'
                );
                break;
            case 'facebook':
            case 'Facebook':
                $session = array
                (
                    'hauth_session.facebook.is_logged_in' => 'i:1;',
                    'hauth_session.facebook.token.access_token' => serialize($token)
                );
                break;
            default:
                throw new Exception($network . ' not yet implemented.');
        }

        /** @var \Hybrid\Auth $hybrid */
        $hybrid = $this->di->get('hybrid');
        $hybrid->logoutAllProviders();
        $hybrid->restoreSessionData(serialize($session));
        /** @var Adapter $networkAdapter */
        $networkAdapter = $hybrid->getAdapter($network);
        return $networkAdapter;
    }

}