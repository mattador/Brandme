<?php

namespace Frontend\Services\CustomValidators;

use DateTime;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

class BirthDateValidator extends Validator implements ValidatorInterface
{

    /**
     * Executes the validation
     *
     * @param |Phalcon\Validation $validator
     * @param string $attribute
     * @return boolean
     */
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $date = $validator->getValue($attribute);
        //check date is valid
        if (!checkdate($date[1], $date[2], $date[0])) {
            $message = $this->getOption('message');
            $validator->appendMessage(new Message($message, $attribute, 'Date'));

            return false;
        }
        //check is 18 and above
        $birthday = new DateTime(implode('-', $date));
        $today = new DateTime();
        $years = $today->diff($birthday)->y;
        if ($years < 21) {
            $validator->appendMessage(new Message('You must be at least 21 years old to participate in Brandme', $attribute, 'Email'));

            return false;
        };

        return true;
    }

}