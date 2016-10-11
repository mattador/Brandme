<?php

namespace Frontend\Services\CustomValidators;

use Entities\Factor;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

class FactorEmailValidator extends Validator implements ValidatorInterface
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
        $email = $validator->getValue($attribute);
        if ((int)Factor::count('email = "'.addslashes($email).'"') > 0) {
            $message = $this->getOption('message');
            $validator->appendMessage(new Message($message, $attribute, 'Email'));

            return false;
        }

        return true;
    }

}