<?php

namespace Frontend\Services\CustomValidators;

use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

class PasswordValidator extends Validator implements ValidatorInterface
{

    /**
     * Executes the validation
     *
     * @param \Phalcon\Validation $validator
     * @param string              $attribute
     * @return boolean
     */
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $password = $validator->getValue($attribute);
        //very important that we don't change the password regex requirement later on
        if (!preg_match('/^(?=.*[a-zA-Z].*)(?=.*[0-9])[^\s]{8,15}$/ ', $password)) {
            $message = $this->getOption('message');
            if (!$message) {
                $message
                    = 'Please select a password between 8 and 15 characters, excluding white space. The password must also contain at least a letter and one digit.';
            }
            $validator->appendMessage(new Message($message, $attribute, 'Password'));
        }

        return true;
    }

}