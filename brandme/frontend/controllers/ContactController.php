<?php

namespace Frontend\Controllers;

use Frontend\Services\Email;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;
use Phalcon\Validation\Validator\StringLength as StringLengthValidator;


class ContactController extends ControllerBase
{

    /**
     * Simple contact controller
     *
     * @todo implement re-captcha
     * @param null $reference
     * @throws Exception
     */
    public function indexAction()
    {
        if ($this->request->isPost()) {
            if (!$this->security->checkToken()) {
                $this->destroy('/contacto');
            }
            $validation = new Validation();
            $validation->add(
                'email',
                new EmailValidator(
                    array(
                        'message' => 'The email is not valid'
                    )
                )
            );
            $validation->add(
                'name',
                new StringLengthValidator(
                    array(
                        'messageMinimum' => 'A valid last should be at least be at least 2 letters long',
                        'min'            => 3
                    )
                )
            );
            $validation->add(
                'message',
                new PresenceOfValidator(
                    array(
                        'message' => 'Please leave us a message',
                    )
                )
            );
            $post = $this->getPost();
            $messages = $validation->validate($post);
            if ($messages->count()) {
                $this->view->setVar('messages', $messages);

                return;
            }
            $mail = $this->getMail();
            $mail->send(
                ['matt@brandme.la', 'gerardo@brandme.la'],
                Email::MAIL_CONFIRMATION_TEMPLATE,
                Email::MAIL_CONTACT_SUBJECT,
                array('message' => $post['message'], 'name' => $post['name'], 'email' => $post['email'])
            );
            $this->view->setVar('messages', array('Thanks for contacting us, we\'ll be in touch'));
        }

    }


}

