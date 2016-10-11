<?php

namespace Frontend\Controllers;


use Common\Services\Sql;
use Entities\Factor;
use Entities\FactorAuth;
use Entities\FactorMeta;
use Entities\Role;
use Frontend\Module;
use Frontend\Services\Account;
use Frontend\Services\CustomValidators\BirthDateValidator;
use Frontend\Services\CustomValidators\FactorEmailValidator;
use Frontend\Services\CustomValidators\PasswordValidator;
use Frontend\Services\CustomValidators\RegionPostcodeValidator;
use Frontend\Services\Register;
use Frontend\Services\Registration;
use Frontend\Services\Session;
use Frontend\Services\Time;
use Frontend\Widgets\Translate;
use Phalcon\Exception;
use Phalcon\Mvc\View;
use Phalcon\Session\Bag;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\InclusionIn as InclusionInValidator;
use Phalcon\Validation\Validator\Regex as RegexValidator;
use Phalcon\Validation\Validator\StringLength as StringLengthValidator;

/**
 * Registration logic
 * Class RegistrationController
 *
 * @package Frontend\Controllers
 */
class RegistrationController extends ControllerBase
{

    public function initialize()
    {
        parent::initialize();
        $this->view->setVar('bodyClass', 'corporate');
    }

    /**
     * Order of registration partial views/steps
     *
     * @var array
     */
    protected $steps
        = [
            'account',
            'basic_data',
            'connect_social_networks',
            'confirmation'
        ];

    /**
     * Registration controller
     *
     * @param null $reference
     * @throws Exception
     */
    public function indexAction($reference = null)
    {
        if ($this->isLogged()) {
            $this->redirect('/');
        }
        $post = $this->getPost();
        $registration = new Bag('registration');
        //User's account has been just been created, redirect to login page.
        if (isset($registration['complete'])) {
            $this->destroy('/');
        }
        /**
         * Multi step forms are tricky, we need to structure these flows a lot better
         */

        $validation = new Validation();
        if ($reference || $registration->reference) {
            $reference = is_null($reference) ? $registration->reference : $reference;
            /** @var FactorMeta $referenceMeta */
            $referenceMeta = FactorMeta::findFirst('referral_code = "'.$reference.'"');
            if ($referenceMeta) {
                $registration->reference = $reference;
                $this->view->setVar('referencer', $referenceMeta->getFirstName().' '.$referenceMeta->getLastName());
            }
        }
        $this->addAssets(
            'js/brandme/registration.js',
            'css/brandme/registration.css'
        );
        if (!empty($post)) {

            $step = $this->getPost('step');
            //invalid step or invalid token
            if (!$step || ($step && !in_array($step, $this->steps))
                || !$this->security->checkToken()
            ) {
                $this->redirect('/registro');
            }
            if (isset($post['back']) && in_array($post['step'], $this->steps)) {
                $steps = array_flip($this->steps);
                $lastStep = $steps[$post['step']] - 1;
                $step = $this->steps[$lastStep];
                unset($registration[$post['step']]);
            } else {
                //to overcome phalcon validation short comings we will use an array to store error messages
                $errors = [];
                if (isset($post['email'])) {
                    $validation->add(
                        'email',
                        new EmailValidator(
                            [
                                'message' => 'The email is not valid'
                            ]
                        )
                    );
                    $validation->add(
                        'email',
                        new FactorEmailValidator(
                            [
                                'message' => 'The email is already registered by another user'
                            ]
                        )
                    );
                }
                if (isset($post['password'])) {
                    $validation->add('password', new PasswordValidator());
                    /*    $validation->add('password', new IdenticalValidator([
                            'value' => $post['confirm_password'],
                            'message' => 'Your passwords do not match, please confirm your password again'
                        ]));*/
                }
                if (isset($post['account'])) {
                    $validation->add(
                        'account',
                        new RegexValidator(
                            [
                                'message' => 'Please select a valid account type',
                                'pattern' => '/creator|advertiser/'
                            ]
                        )
                    );
                }
                if (isset($post['first_name'])) {
                    $validation->add(
                        'first_name',
                        new StringLengthValidator(
                            [
                                'messageMinimum' => isset($post['is_brand'])
                                    ? 'A valid brand name should be at least 2 characters long'
                                    : 'A valid name should be at least 2 letters long',
                                'min'            => 2
                            ]
                        )
                    );
                }
                if (isset($post['last_name'])) {
                    $validation->add(
                        'last_name',
                        new StringLengthValidator(
                            [
                                'messageMinimum' => 'A valid surname should be at least 2 characters long',
                                'min'            => 2
                            ]
                        )
                    );
                }
                if (isset($post['date_of_birth_year'])) {
                    $post['date_of_birth'] = [
                        $post['date_of_birth_year'],
                        $post['date_of_birth_month'],
                        $post['date_of_birth_day']
                    ];
                    $validation->add(
                        'date_of_birth',
                        new BirthDateValidator(
                            ['message' => 'The date of birth is invalid']
                        )
                    );
                }
                if (isset($post['country'])) {
                    $validation->add(
                        'country',
                        new InclusionInValidator(
                            [
                                'message' => 'Please select a valid country',
                                'domain'  => Account::getCountries()
                            ]
                        )
                    );
                }
                if (isset($post['postcode'])) {
                    if ($post['country'] == 'Mexico') {
                        $validation->add(
                            'postcode',
                            new RegionPostcodeValidator(
                                [
                                    'message' => 'The postcode is incorrect',
                                ]
                            )
                        );
                    } else {
                        $validation->add(
                            'postcode',
                            new RegexValidator(
                                [
                                    'message' => 'The postcode is incorrect',
                                    'pattern' => '/\d{4,6}/'
                                ]
                            )
                        );
                    }
                }
                if ($step == 'connect_social_networks') {
                    if (!$registration->connected
                        && $registration->account['type'] == 'creator'
                    ) {
                        $errors[]
                            = 'You must link at least one social network to register as a creator';
                    }
                }
                if (!is_null($validation->getValidators())) {
                    $validation->validate($post);
                }
                if (!is_null($validation->getMessages())) {
                    foreach ($validation->getMessages() as $err) {
                        //if user is registering as a brand we can omit validation for the following fields
                        if (in_array(
                                $err->getField(),
                                ['last_name', 'gender', 'date_of_birth_year']
                            )
                            && isset($post['is_brand'])
                        ) {
                            continue;
                        }
                        $errors[] = $err;
                    }
                }
                if (count($errors)) {
                    //Some errors were detected
                    $this->view->setVar('messages', $errors);
                } else {
                    //Save data in session and proceed to the next step
                    $registration->{$post['step']} = $post;
                    $step = $this->forward();
                }
            }
        } else {
            $step = $this->forward();
        }
        if ($step == 'confirmation') {
            $registration->complete
                = true;//Set flag to only render the confirmation page once to not cause duplication issues
            $service = new Registration();
            /** @var Factor $factor */
            $factor = $service->create($registration);
            if (!$factor instanceof Factor) {
                $this->error(Translate::_('There was an error registering your account'));
                $this->redirect('/error');
            }
            $mail = $this->getMail();
            $mail->send(
                $factor->getEmail(),
                'confirmation',
                'Brandme - Confirmar correo',
                [
                    'confirmation_key' => APPLICATION_HOST.'/confirmacion/'.$factor->getFactorAuth()->getConfirmationKey(),
                    'name'             => $factor->getFactorMeta()->getFirstName(),
                    'referral_url'     => APPLICATION_HOST.'/registro/'.$factor->getFactorMeta()->getReferralCode(),
                    'avatar'           => !is_null($factor->getFactorMeta()->getAvatar()) ?
                        APPLICATION_HOST.$factor->getFactorMeta()->getAvatar() : null
                ]
            );
        }
        $this->view->setVars(
            [
                'type'            => $registration->account['type'],
                'defaultTimezone' => 'America/Mexico_City',
                'timezones'       => Time::getTimezones(),
                'countries'       => Account::getCountries(),
                'step'            => $step
            ]
        );
        $this->view->pick('Registration/'.strtolower($step));
        $this->view->setMainView('Layouts/site');
    }

    protected function forward()
    {
        $registration = $this->session->get('registration');
        if (!$registration || (array_key_exists('account', $registration) && is_null($registration['account']))) {
            //render first step
            return $this->steps[0];
        }
        //get lowest step not yet complete
        $steps = array_diff_key(array_flip($this->steps), $registration);
        if ($steps) {
            if (isset($registration['account']['type'])
                && $registration['account']['type'] == 'advertiser'
            ) {
                //advertisers don't need to register or add social networks
                unset($steps['connect_social_networks']);
            }

            return key($steps);
        }
        $this->destroy();
    }

    /**
     * Post is for resending confirmation email.
     * Get is for attempting to confirm
     * User is logged in automatically if confirmed correctly
     *
     * @param null $confirmationKey
     */
    public function emailConfirmationAction($confirmationKey = null)
    {
        if ($this->request->isPost()) {
            if ($this->session->confirmation) {
                $this->view->setVar(
                    'messages',
                    ['A confirmation email already been sent, please check your inbox']
                );

                return;
            }
            if (!$this->security->checkToken()) {
                $this->destroy('/reenviar-confirmacion');
            }
            $post = $this->getPost();
            if (!isset($post['email'])) {
                $this->view->setVar('messages', ['Email is a required field']);

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
                //Some errors were detected
                $this->view->setVar('messages', $validationMessages);

                return;
            }
            $sql
                = 'SELECT
                    f.email, fa.confirmation_key, fm.first_name name, fm.referral_code, fm.avatar
                    FROM factor f
                    INNER JOIN factor_meta fm ON fm.id_factor = f.id
                    INNER JOIN factor_auth fa ON fa.id_factor = f.id
                    WHERE f.email = "'.$post['email'].'" AND fa.confirmed_at IS NULL LIMIT 1';
            $result = Sql::find($sql);

            if (empty($result)) {
                $this->warning(
                    Translate::_('Email already confirmed or not registered')
                );

                return;
            }
            $result = $result[0];
            $mail = $this->getMail();
            $mail->send(
                $result['email'],
                'confirmation',
                'Brandme - Confirmar correo',
                [
                    'confirmation_key' => APPLICATION_HOST.'/confirmacion/'.$result['confirmation_key'],
                    'name'             => $result['name'],
                    'referral_url'     => APPLICATION_HOST.'/registro/'.$result['referral_code'],
                    'avatar'           => !is_null($result['avatar']) ?
                        APPLICATION_HOST.$result['avatar'] : null
                ]
            );
            $this->session->confirmation = time();
            $this->success(Translate::_('Resent confirmation email'));
        } else {
            if (!is_null($confirmationKey)) {
                $factorAuth = FactorAuth::findFirst(
                    'confirmation_key = "'.$confirmationKey
                    .'" AND confirmed_at IS NULL'.($this->isLogged() ?
                        ' AND id_factor = '.$_SESSION['user_session']['id']
                        : '')
                );
                if (!$factorAuth) {
                    $this->view->setVar(
                        'messages',
                        ['Invalid confirmation key']
                    );

                    return;
                } else {
                    $factorAuth->setConfirmedAt(date('Y-m-d H:i:s'));
                    $factorAuth->update();
                    $this->success(Translate::_('Thanks for confirming'));
                    $this->session->remove(
                        'registration'
                    ); //just in case user had began registration and then decided to login
                    //Login and redirect
                    Module::getService('Session')->login(
                        $factorAuth->getIdFactor()
                    );
                    $this->redirect(
                        '/'.Session::$roleNamespaces[Role::findFirst(
                            'id ='.$factorAuth->getIdRole()
                        )->getName()]
                    );
                }
            }
        }

    }

}

