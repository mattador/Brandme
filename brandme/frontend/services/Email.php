<?php

namespace Frontend\Services;
require_once APPLICATION_LIB_DIR . '/Sendgrid/sendgrid-php.php';

use Phalcon\Mvc\View;
use SendGrid;
use SendGrid\Email as SendGridEmail;

/**
 * Class Email
 * @package Frontend\Services
 */
class Email extends AbstractService
{

    const MAIL_CONTACT_TEMPLATE = 'contact';
    const MAIL_CONTACT_SUBJECT = 'Brandme - Contacto';

    protected $username;
    protected $password;
    protected $sender;

    public function __construct($params)
    {
        $this->setUsername($params->username);
        $this->setPassword($params->password);
        $this->setSender($params->sender);
    }

    public function send($toEmail, $template, $subject, $params)
    {
        /** @var \Phalcon\Mvc\View $view */
        $view = $this->getDI()->get('view');
        $content = $view->getRender('Mail', $template, $params, function ($v) {
            $v->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        });
        $sendGrid = new SendGrid($this->getUsername(), $this->getPassword());
        $email = new SendGridEmail();
        $email
            ->addTo($toEmail)
            ->setFrom($this->getSender())
            ->setSubject($subject)
            ->setHtml($content);
        $result = $sendGrid->send($email);
        if ($result->message == 'error') {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param mixed $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }
}