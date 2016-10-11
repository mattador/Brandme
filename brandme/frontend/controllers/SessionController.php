<?php

namespace Frontend\Controllers;

use Entities\Factor;
use Entities\FactorAdminAuth;
use Entities\FactorAuth;
use Entities\FactorNetwork;
use Entities\FactorSession;
use Entities\Role;
use Frontend\Module;
use Frontend\Services\CustomValidators\PasswordValidator;
use Frontend\Services\Session;
use Frontend\Widgets\Translate;
use Phalcon\Crypt;
use Phalcon\Mvc\View;
use Phalcon\Session\Bag;
use Phalcon\Tag;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;

/**
 * Note: Phalcon cookie management doesn't work reliably as o v1.3.4,
 * which is why you will see native cookie management ahead.
 * Class SessionController
 *
 * @package Frontend\Controllers
 */
class SessionController extends ControllerBase
{

    public function initialize()
    {
        parent::initialize();
        $this->view->setVar('bodyClass', 'login');
        $this->addAssets('js/brandme/login-slide.js', 'assets/global/plugins/backstretch/jquery.backstretch.min.js');
    }

    /**
     * Login user to dashboard
     * Checks for remember me cookie, also
     */
    public function loginAction()
    {
        if ($this->isLogged() || $this->isRegistering()) {
            $role = $this->getRole();
            if (!$role) {
                //this most definitely should not happen
                $this->destroy('/');
            }
            $this->redirect('/'.Session::$roleNamespaces[$role]);
        }
        /** @var Session $session */
        $session = Module::getService('Session');

        //Verify if persistent token cookie exists
        if (isset($_COOKIE['token'])) {
            //Get the cookie
            $crypt = new Crypt();
            $token = $crypt->decrypt(base64_decode($_COOKIE['token']), $this->getConfig('token'));
            //attempt to login
            $roleType = $session->attemptLoginByPersistentToken($token);
            if ($roleType) {
                $this->redirect('/'.Session::$roleNamespaces[$roleType]);
            } else {
                //invalidate token
                setcookie('token', '', time() - 3600);
                $this->destroy('/');
            }
        }
        if ($this->request->isPost()) {
            if (!$this->security->checkToken()) {
                $this->destroy('/');
            }
            $post = $this->getPost();
            if (!isset($post['email'])) {
                $this->view->setVar('messages', ['Invalid email/password.']);

                return;
            }
            $validation = new Validation();
            $validation->add(
                'email',
                new EmailValidator(
                    [
                        'message' => 'The email is not valid'
                    ]
                )
            );
            $validationMessages = $validation->validate($post);
            if ($validationMessages->count()) {
                $this->view->setVar('messages', $validationMessages);

                return;
            }
            if (!isset($post['password']) || !strlen($post['password'])) {
                $this->error(Translate::_('The password is not valid'));

                return;
            }
            //now actually check that the password is real
            $authenticate
                = "SELECT f.id, fa.passhash, fa.confirmed_at, r.name role FROM Entities\Factor f
            JOIN Entities\FactorAuth fa ON f.id = fa.id_factor
            JOIN Entities\Role r ON r.id = fa.id_role
            WHERE f.email = '{$post['email']}' LIMIT 1";
            /** @var \Phalcon\Mvc\Model\Resultset\Simple $result */
            $factor = $this->getManager()->executeQuery($authenticate)->getFirst();
            if (!$factor) {
                $this->error(Translate::_('Invalid email/password'));

                return;
            }
            /**
             * There are 3 ways to authenticate by a password:
             * 1. check to see if password hash is valid and if so log straight in
             * 2. check to see if password is a valid legacy hash and if so update to more secure format, then login
             * 3. check to see if password is an admin generated single-sign-on hash generated
             */

            //1.
            if (!$this->security->checkHash($post['password'], $factor->passhash)) {
                //2.
                $sha256Hash = hash('sha256', $this->getConfig('security')->legacy_hash.$post['password']);
                if ($sha256Hash == $factor->passhash) {
                    //We have verified that the password is an older format, so now we need to update it
                    /** @var FactorAuth $factorAuth */
                    $factorAuth = FactorAuth::findFirst('id = '.$factor->id);
                    $factorAuth
                        ->setLastPasshash($factor->passhash)
                        ->setPasshash(
                            $this->security->hash($post['password'])
                        )->update();
                } else {
                    //3.
                    $adminAuths = FactorAdminAuth::find(
                        'is_usable = 1 AND created_at > "'.date('Y-m-d H:i:s', strtotime('-2 MINUTE')).'" AND id_factor_accessed IS NULL'
                    );
                    $adminAuthFound = false;
                    /** @var FactorAdminAuth $adminAuth */
                    foreach ($adminAuths as $adminAuth) {
                        if ($this->security->checkHash($post['password'], $adminAuth->getPasshash())) {
                            //We found a matching and valid single sign on passhash, now invalidate to prevent future use
                            $adminAuth
                                ->setIsUsable(0)
                                ->setIdFactorAccessed($factor['id'])
                                ->setUpdatedAt(date('Y-m-d H:i:s'))
                                ->update();
                            $adminAuthFound = true;
                            break;
                        }
                    }
                    if (!$adminAuthFound) {
                        $this->warning(Translate::_('Forgot your password?'));

                        return;
                    }
                }
            }
            //force user to confirm account
            if (is_null($factor['confirmed_at'])) {
                $this->view->setVars(
                    [
                        'messages'        => ["You need to confirm your account first"],
                        'confirm_account' => true
                    ]
                );

                return;
            }
            $session->login($factor->id);
            $this->session->remove('registration'); //just in case user had began registration and then decided to login
            if (isset($post['remember'])) { //If user has checked remember me create cookie
                //$this->cookies->set('token', $session->createNewPersistentToken($factor->id), time() + $this->getConfig('persistent_cookie_life'));

                $crypt = new Crypt(); //Take note that we also encrypt the token as an extra precaution in the actual cookie
                $encrypted = $crypt->encrypt($session->createNewPersistentToken($factor->id), $this->getConfig()->token);
                setcookie('token', base64_encode($encrypted), time() + $this->getConfig('persistent_cookie_life'));
            } else {
                $session->destroyPersistentToken($factor->id);
            }
            $this->redirect('/'.Session::$roleNamespaces[$factor->role]);
        }
    }

    /**
     * Login user via google or facebook - this is only relevant of course for content creators
     *
     * @param $network
     */
    public function socialLoginAction($network)
    {
        if ($this->isLogged()) {
            $this->redirect('/'); //this will just redirect user to his logged in environment

            return;
        }
        $networkName = ucfirst(strtolower($network));
        $idNetwork = \Entities\Network::findFirst('name = "'.$networkName.'"')->getId();
        $errorMsg = Translate::_('Your %network% account could not be used to login', ['network' => $networkName]);
        try {
            $hybrid = $this->di->get('hybrid');
            if ($hybrid->isConnectedWith($network)) {
                $networkAdapter = $hybrid->getAdapter($network);
                $profile = $networkAdapter->getUserProfile();
                $factorNetwork = FactorNetwork::findFirst(
                    'account_id = "'.$profile->identifier.'" AND status = "linked" AND id_network = '.$idNetwork
                );
                if (!$factorNetwork) {
                    $networkAdapter->logout();
                    $this->error(Translate::_('This %network% account is not registered with BrandMe', ['network' => $networkName]));
                    $this->redirect('/');
                } else {
                    $networkAdapter->logout();
                    if (is_null($factorNetwork->Factor->FactorAuth->getConfirmedAt())) {
                        //@todo give the creator a way of confirming account, perhaps a link to resend confirmation email in error message
                        $this->error(Translate::_('You need to confirm your account first'));
                        $this->redirect('/');
                    }
                    $this->session->remove('registration');
                    $session = Module::getService('Session');
                    $session->login($factorNetwork->getIdFactor());
                    $this->redirect('/creador'); //logging in by this means the user is a creator
                }
            } else {
                $hybrid->authenticate($network);
            }
        } catch (\FacebookApiException $e) {
            $this->error($errorMsg);
        } catch (\HybridException $e) {
            $this->error($errorMsg);
        } catch (\Exception $e) {
            $this->error($errorMsg);
        }
        $this->redirect('/');
    }

    /**
     * Destroys current session and redirects to login page
     */
    public function logoutAction()
    {
        if ($this->isLogged()) {
            $session = new Bag('user_session');
            $sessionFactor = FactorSession::findFirst('session_id = "'.session_id().'" AND id_factor = '.$session->id.'');
            if ($sessionFactor) {
                $sessionFactor->setLoggedOut(date('Y-m-d H:i:s'));
                Module::getService('Session')->destroyPersistentToken($session->id);
                setcookie('token', '', time() - 3600);
                $sessionFactor->update();
            }
        }
        $this->destroy('/');
    }

    /**
     * Client enters email to receive reset link
     */
    public function passwordRecoveryAction()
    {
        if ($this->isLogged()) {
            $this->redirect('/salir');

            return;
        }
        if ($this->request->isPost()) {
            $post = $this->getPost();
            if (isset($post['return'])) {
                $this->destroy('/');
            }
            if (!$this->security->checkToken()) {
                $this->destroy('/recuperar-acceso');
            }
            $validation = new Validation();
            $validation->add(
                'email',
                new PresenceOfValidator(
                    [
                        'message' => 'Your email is a required field'
                    ]
                )
            );
            $validationMessages = $validation->validate($post);
            if ($validationMessages->count()) {
                $this->view->setVar('messages', $validationMessages);

                return;
            }
            $validation->add(
                'email',
                new EmailValidator(
                    [
                        'message' => 'The email entered is not valid'
                    ]
                )
            );
            $validationMessages = $validation->validate($post);
            if ($validationMessages->count()) {
                $this->view->setVar('messages', $validationMessages);

                return;
            }
            $factor = Factor::findFirst('email = "'.$post['email'].'"');
            if (!$factor) {
                $this->view->setVar('messages', ['Please contact Brandme for assistence']);
            } else {
                $recoveryKey = sha1(uniqid($post['email'], true));
                $auth = $factor->getFactorAuth();
                $auth->setRecoveryKey($recoveryKey);
                $auth->update();
                $mail = $this->getMail();
                $mail->send(
                    $post['email'],
                    'lost_password',
                    'Brandme - Contraseña perdida',
                    [
                        'recovery_key' => APPLICATION_HOST.'/recuperar-acceso/'.$recoveryKey,
                        'name'         => $factor->getFactorMeta()->getFirstName(),
                        'avatar'       => !is_null($factor->getFactorMeta()->getAvatar()) ?
                            APPLICATION_HOST.$factor->getFactorMeta()->getAvatar() : null,
                        'referral_url' => APPLICATION_HOST.'/registro/'.$factor->getFactorMeta()->getReferralCode()
                    ]
                );
                $this->success(Translate::_('A link to reset your password has been sent to your email'));
            }
        }
    }

    /**
     * Client resets password and gets logged in
     *
     * @param null $recoveryKey
     */
    public function resetPasswordAction($recoveryKey = null)
    {
        if ($this->isLogged()) {
            $this->redirect('/salir');

            return;
        }
        $auth = FactorAuth::findFirst('recovery_key = "'.$recoveryKey.'"');
        if (!$auth) {
            $this->destroy('/recuperar-acceso');

            return;
        }
        $this->view->setVar('url', 'recuperar-acceso/'.$recoveryKey);
        if ($this->request->isPost()) {
            $post = $this->getPost();
            if (!$this->security->checkToken()) {
                $this->destroy('/recuperar-acceso');
            }
            if (!isset($post['password'])) {
                $this->view->setVar('messages', ['Please enter a valid password.']);

                return;
            }
            $validation = new Validation();
            $validation->add('password', new PasswordValidator());
            /*$validation->add('password', new IdenticalValidator([
                'value' => $post['confirm_password'],
                'message' => 'Your passwords do not match, please confirm your password again'
            ]));*/
            $validationMessages = $validation->validate($post);
            if ($validationMessages->count()) {
                $this->view->setVar('messages', $validationMessages);

                return;
            }
            //@todo client shouldn't be allowed to choose and old password, ever
            $currentPasshash = $auth->getPasshash();
            $auth->setPasshash($this->security->hash($post['password']));
            $auth->setLastPasshash($currentPasshash);
            $auth->setRecoveryKey(null);
            $auth->update();
            $this->success(Translate::_('Your password has been reset successfully'));
            Module::getService('Session')->login($auth->getIdFactor());
            $this->redirect('/'.Session::$roleNamespaces[Role::findFirst('id ='.$auth->getIdRole())->getName()]);
        }
    }
}

