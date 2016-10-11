<?php

namespace Frontend\Controllers\Account\Advertiser;

use Frontend\Module;
use Frontend\Controllers\Account\AccountControllerBase;
use Entities\FactorActivity;
use Entities\Region;
use Frontend\Services\Account;
use Frontend\Services\CustomValidators\BirthDateValidator;
use Frontend\Services\CustomValidators\FactorEmailValidator;
use Frontend\Services\CustomValidators\PasswordValidator;
use Frontend\Services\CustomValidators\RegionPostcodeValidator;
use Frontend\Services\Form\Upload\Image;
use Frontend\Services\Time;
use Phalcon\Exception;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\Identical as IdenticalValidator;
use Phalcon\Validation\Validator\InclusionIn as InclusionInValidator;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;
use Phalcon\Validation\Validator\Regex as RegexValidator;
use Phalcon\Validation\Validator\StringLength as StringLengthValidator;

/**
 * Class ProfileController
 *
 * @package Frontend\Controllers\Account\Advertiser
 */
class ProfileController extends AccountControllerBase
{

    public function initialize()
    {
        parent::initialize();
        $this->view->setVar(
            'meta',
            $this->get('account.meta')
        );
    }

    /**
     * Allows customer to configure their accounts
     */
    public function configurationAction()
    {
        $service = new Account();
        /** @var \Entities\FactorMeta $meta */
        $meta = $this->get('account.meta');
        if ($this->request->isPost()) {
            $post = $this->getPost();
            if (!$this->security->checkToken() || !isset($post['configuration'])
                || !in_array(
                    $post['configuration'],
                    ['system', 'contact', 'security', 'notifications']
                )
            ) {
                $this->redirect('/anunciante/perfil/configuracion');
            }

            $validation = new Validation();
            $errors = [];
            switch ($post['configuration']) {
                case 'system':
                    $validation->add(
                        'email',
                        new PresenceOfValidator(
                            array(
                                'message' => 'Email is a required field'
                            )
                        )
                    );
                    if ($post['email'] != $this->get('account')->getEmail()) {
                        $validation->add(
                            'email',
                            new EmailValidator(
                                array(
                                    'message' => 'The email is not valid'
                                )
                            )
                        );
                        $validation->add(
                            'email',
                            new FactorEmailValidator(
                                array(
                                    'message' => 'The email is already registered by another user'
                                )
                            )
                        );
                    }
                    if (!in_array($post['timezone'], \DateTimeZone::listIdentifiers())) {
                        $errors[] = "Invalid timezone";
                    }
                    break;
                case 'contact':
                    if (strlen(trim($post['telephone'])) > 0) {
                        $validation->add(
                            'telephone',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[\+\d\s\(\)]{8,12}$/',
                                    'message' => 'Please enter a valid telephone number'
                                )
                            )
                        );
                    }
                    $validation->add(
                        'country',
                        new InclusionInValidator(
                            [
                                'message' => 'Please select a valid country',
                                'domain'  => Account::getCountries()
                            ]
                        )
                    );
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
                    if (strlen(trim($post['street']))) {
                        $validation->add(
                            'street',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[a-zA-Z\s\-\.\d\/\#ÁÉÍÓÚáéíóuñÑ]{4,128}$/',
                                    'message' => 'Please use a valid street name'
                                )
                            )
                        );
                    }
                    if (strlen(trim($post['interior_number']))) {
                        $validation->add(
                            'interior_number',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[a-zA-Z\s\-\.\d\/\#ÁÉÍÓÚáéíóuñÑ]{1,11}$/',
                                    'message' => 'Please use a valid internal street number'
                                )
                            )
                        );
                    }
                    if (strlen(trim($post['exterior_number']))) {
                        $validation->add(
                            'exterior_number',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[a-zA-Z\s\-\.\d\/\#ÁÉÍÓÚáéíóuñÑ]{1,11}$/',
                                    'message' => 'Please use a valid external street number'
                                )
                            )
                        );
                    }
                    if (strlen(trim($post['suburb']))) {
                        $validation->add(
                            'suburb',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[a-zA-Z\s\-\.\/ÁÉÍÓÚáéíóuñÑ]{3,64}$/',
                                    'message' => 'Please enter a valid suburb name'
                                )
                            )
                        );
                    }
                    if (strlen(trim($post['colony']))) {
                        $validation->add(
                            'colony',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[a-zA-Z\s\-\.\/ÁÉÍÓÚáéíóuñÑ]{3,64}$/',
                                    'message' => 'Please enter a valid colony name'
                                )
                            )
                        );
                    }
                    if (strlen(trim($post['state']))) {
                        $validation->add(
                            'state',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[a-zA-Z\s\-\.\/ÁÉÍÓÚáéíóuñÑ]{3,64}$/',
                                    'message' => 'Please enter a valid state name'
                                )
                            )
                        );
                    }
                    if (strlen(trim($post['city']))) {
                        $validation->add(
                            'city',
                            new RegexValidator(
                                array(
                                    'pattern' => '/^[a-zA-Z\s\-\.\d\/\#ÁÉÍÓÚáéíóuñÑ]{3,64}$/',
                                    'message' => 'Please enter a valid city name'
                                )
                            )
                        );
                    }
                    break;
                case 'security':
                    $validation->add('old_password', new PresenceOfValidator(array('message' => 'Please enter your current password.')));
                    if (!$this->security->checkHash($post['old_password'], $this->get('account.auth')->getPasshash())) {
                        $errors[] = 'Your current password is incorrect';
                    } elseif ($this->security->checkHash($post['password'], $this->get('account.auth')->getPasshash())) {
                        $errors[] = 'Your new password must be different to your current password';
                    } else {
                        $validation->add('password', new PasswordValidator());
                        $validation->add(
                            'password',
                            new IdenticalValidator(
                                array(
                                    'value'   => $post['confirm_password'],
                                    'message' => 'Your passwords do not match, please confirm your password again'
                                )
                            )
                        );
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
                $this->view->errorMenu = $post['configuration'];
                $this->view->setVar('messages', $errors);
            } else {
                /** @var \Entities\Factor $factor */
                $factor = $this->get('account');
                /** @var \Entities\FactorAuth $auth */
                $auth = $this->get('account.auth');
                switch ($post['configuration']) {
                    case 'system':
                        if ($post['email'] != $this->get('account')->getEmail()) {
                            $factor
                                ->setEmail($post['email'])
                                ->update();
                            $confirmationKey = sha1(uniqid($post['email'], true));
                            $auth
                                ->setConfirmationKey($confirmationKey)
                                ->setConfirmedAt(null)
                                ->update();

                            $mail = $this->getMail();
                            $mail->send(
                                $post['email'],
                                'reconfirmation',
                                'Brandme - Confirmar correo',
                                [
                                    'confirmation_key' => APPLICATION_HOST.'/confirmacion/'.$confirmationKey,
                                    'name'             => $this->get('account.meta')->getFirstName(),
                                    'referral_url'     => APPLICATION_HOST.'/registro/'.$this->get('account.meta')->getReferralCode(),
                                    'avatar'           => !is_null($this->get('account.meta')->getAvatar()) ?
                                        APPLICATION_HOST.$this->get('account.meta')->getAvatar() : null
                                ]
                            );
                        }
                        $remoteSupport = isset($post['remote_support']) && !is_null($post['remote_support']) ? 1 : 0;
                        $auth
                            ->setRemoteSupport($remoteSupport)
                            ->update();
                        $meta
                            ->setTimezone($post['timezone'])
                            ->update();
                        break;
                    case 'contact':
                        //Prep contact number
                        $telephone = strlen(trim($post['telephone'])) ? trim($post['telephone']) : null;
                        $meta
                            ->setTelephone($telephone)
                            ->update();

                        //Prep address fields
                        $postcode = trim($post['postcode']);
                        $street = strlen(trim($post['street'])) ? trim($post['street']) : null;
                        $intNumber = strlen(trim($post['interior_number'])) ? trim($post['interior_number']) : null;
                        $extNumber = strlen(trim($post['exterior_number'])) ? trim($post['exterior_number']) : null;
                        $suburb = strlen(trim($post['suburb'])) ? trim($post['suburb']) : null;
                        $colony = strlen(trim($post['colony'])) ? trim($post['colony']) : null;
                        $state = strlen(trim($post['state'])) ? trim($post['state']) : null;
                        $city = strlen(trim($post['city'])) ? trim($post['city']) : null;
                        $country = trim($post['country']);

                        /**
                         * An address could technically not be set, but for now assume that it is,
                         * since postcode is a mandatory step of all possible registration flows
                         */
                        $region = $this->get('account.region');
                        $region
                            ->setPostcode($postcode)
                            ->setStreet($street)
                            ->setInteriorNumber($intNumber)
                            ->setExteriorNumber($extNumber)
                            ->setSuburb($suburb)
                            ->setColony($colony)
                            ->setCity($city)
                            ->setState($state)
                            ->setCountry($country)
                            ->setCreatedAt(date('Y-m-d H:i:s'))
                            ->update();
                        break;
                    case 'security':
                        $currentPasshash = $auth->getPasshash();
                        $auth
                            ->setLastPasshash($currentPasshash)
                            ->setPasshash($this->security->hash($post['password']))
                            ->update();
                        break;
                    case 'notifications':
                        //don't get tricked by inverse wording
                        $meta
                            ->setRecieveEmails(isset($post['recieve_emails']) ? 0 : 1)
                            ->update();
                        break;
                }
                $this->flash->success(\Frontend\Widgets\Translate::_('Configuration updated successfully'));
            }
            $this->refreshSession();
        }

        //load data for form view
        $form = [];
        $form['timezones'] = Time::getTimezones();
        $form['filter'] = Account::$filter;
        //system fields
        $form['system']['email'] = $this->get('account')->getEmail();
        $form['system']['timezone'] = $this->get('account.meta')->getTimezone();
        $form['system']['remote'] = $this->get('account.auth')->getRemoteSupport();

        //contact fields
        $form['contact']['telephone'] = $meta->getTelephone();

        /** @var \Entities\FactorRegion $region */
        $region = $this->get('account.region');
        if ($region->getCountry() == 'México' && !empty($region->getPostcode())) {
            //hint at probably field values (with placeholders) depending on post code
            $form['regionData'] = Region::findFirst('postcode = '.$region->getPostcode());
        }
        $form['countries'] = Account::getCountries();
        /** @var \Entities\Region $regionData */
        $form['contact']['postcode'] = $region->getPostcode();
        $form['contact']['street'] = $region->getStreet();
        $form['contact']['interior_number'] = $region->getInteriorNumber();
        $form['contact']['exterior_number'] = $region->getExteriorNumber();
        $form['contact']['suburb'] = $region->getSuburb();
        $form['contact']['colony'] = $region->getColony();
        $form['contact']['state'] = $region->getState();
        $form['contact']['city'] = $region->getCity();
        $form['contact']['country'] = $region->getCountry();

        $form['notifications']['recieve_emails'] = $meta->getRecieveEmails();
        $this->view->setVars($form);
    }

    /**
     * Updates plan
     *
     * @todo attach payment + insert rules into session
     */
    public function plansAction()
    {
        $service = new Account();
        /** @var \Entities\FactorMeta $meta */
        $role = $this->get('account.auth')->getRole()->getId();
        if ($this->request->isPost()) {
            $currentPlan = $service->getPlanByFactorId($this->get('id'));
            $post = $this->getPost();
            if (!$this->security->checkToken() || !isset($post['type'])) {
                $this->redirect('/anunciante/perfil/planes');
            }
            $messages = [];
            if (!in_array($post['type'], array('free', 'pro', 'team', 'enterprise'))) {
                $messages[] = 'Invalid plan specified';
            }
            if ($post['type'] == $currentPlan) {
                $messages[] = 'Please choose a plan different to your current one.';
            }
            if (!$messages) {
                $plan = $service->setPlanByFactorId(
                    $this->get('id'),
                    $post['type']
                );
                $this->refreshSession();
                $this->flash->success('New plan chosen successfully');
            } else {
                $this->view->setVar('messages', $messages);
            }
        }
        $plan = $service->getPlanByFactorId($this->get('id'));
        $this->view->setVar('plan', $plan);
    }


    /**
     * Either updates images or information but not both in one post request
     *
     * @throws Exception
     */
    public function informationAction()
    {
        /** @var \Entities\FactorMeta $meta */
        $meta = $this->get('account.meta');
        if ($this->request->isPost()) {
            if (!$this->security->checkToken()) {
                $this->redirect('/anunciante/perfil/informacion');
            }

            $post = $this->getPost();
            //Check for images, and update them
            $validationMessages = [];
            foreach ($this->request->getUploadedFiles() as $file) {
                if ($file->getError() == UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($file->getKey() == 'image_avatar') {
                    $uploaded = Image::upload($file, [150, 150, 128, 128], '/content/profile/avatar');
                    if (is_array($uploaded)) {
                        $validationMessages = array_merge($validationMessages, $uploaded);
                    } else {
                        $meta->setAvatar($uploaded);
                    }
                }
            }
            //..or expect these information fields, and process them separately to the images
            $validation = new Validation();
            $validation->add(
                'first_name',
                new StringLengthValidator(
                    array(
                        'messageMinimum' => (int)$meta->getIsBrand() ? 'A valid brand name should be at least 2 characters long'
                            : 'A valid name should be at least 2 letters long',
                        'min'            => 2
                    )
                )
            );
            if (!(int)$meta->getIsBrand()) {
                $validation->add(
                    'last_name',
                    new StringLengthValidator(
                        array(
                            'messageMinimum' => 'A valid surname should be at least 2 characters long',
                            'min'            => 2
                        )
                    )
                );
                $post['date_of_birth'] = array($post['date_of_birth_year'], $post['date_of_birth_month'], $post['date_of_birth_day']);
                $validation->add(
                    'date_of_birth',
                    new BirthDateValidator(
                        array(
                            'message' => 'The date of birth is invalid'
                        )
                    )
                );
            }
            $validation->add(
                'bio',
                new StringLengthValidator(
                    array(
                        'min'            => 0,
                        'messageMaximum' => 'A bio cannot be longer that 500 characters',
                        'max'            => '500'
                    )
                )
            );
            $validationMessages = $validation->validate($post);
            if (!$validationMessages->count()) {
                $this->view->setVar('messages', $validationMessages);
                $meta->setBio($post['bio']);
                $meta->setFirstName($post['first_name']);
                if (!(int)$meta->getIsBrand()) {
                    $meta->setGender($post['gender']);
                    $meta->setLastName($post['last_name']);
                    $meta->setBirthdate($post['date_of_birth_year'].'-'.$post['date_of_birth_month'].'-'.$post['date_of_birth_day']);
                }
            }
            if (count($validationMessages)) {
                $this->flash->error(\Frontend\Widgets\Translate::_('Please complete your information carefully'));
                $this->view->setVar('messages', $validationMessages);
            } else {
                $meta->update();
                $this->refreshSession();
                //work around to make images refresh immediately
                $this->flash->success(\Frontend\Widgets\Translate::_('Account updated successfully'));
                $this->refreshSession('/anunciante/perfil/informacion');
            }

        }
        //load variables into form

        $birthday = new \DateTime($this->get('account.meta')->getBirthdate());
        $this->view->setVars(
            array(
                'birthday' => new \DateTime($meta->getBirthdate()),
                'days'     => array_combine(range(1, 31), range(1, 31)),
                'months'   => [
                    1 => 'Enero',
                    'Febrero',
                    'Marzo',
                    'Abril',
                    'Mayo',
                    'Junio',
                    'Julio',
                    'Agosto',
                    'Septiembre',
                    'Octubre',
                    'Noviembre',
                    'Diciembre'
                ],
                'years'    => array_combine(array_reverse(range(1940, date('Y') - 21)), array_reverse(range(1940, date('Y') - 21)))
            )
        );
    }

    /**
     * Activity controller - just a simple grid
     */
    public function activityAction()
    {
        $activities = FactorActivity::find(
            [
                'id_factor = '.$this->get('id').' AND created_at >= "'.date('Y-m-d', strtotime('NOW -90 DAY')).'"',
                'order' => 'created_at DESC'
            ]
        );
        $currentPage = !isset($_GET["p"]) || (int)$_GET["p"] < 0 ? 1 : (int)$_GET["p"];
        $paginator = new PaginatorModel(
            array(
                "data"  => $activities,
                "limit" => 25,
                "page"  => $currentPage
            )
        );
        $results = $paginator->getPaginate();
        $resultSet = [];
        $timezone = $this->get('account.meta')->getTimezone();
        $timeModifier = Module::getService('Time');
        foreach ($results->items as $item) {
            $createdAt = $timeModifier->utcToTimezone($item->getCreatedAt(), $timezone, 'd/m/Y h:i A');
            $resultSet[] = ['created' => $createdAt, 'msg' => $item->getMessage()];
        }
        $this->view->setVars(
            ['page' => $resultSet, 'totalPages' => $results->total_pages, 'currentPage' => $results->current]
        );
    }

}

