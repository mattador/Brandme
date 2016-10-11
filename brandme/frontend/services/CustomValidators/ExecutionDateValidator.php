<?php

namespace Frontend\Services\CustomValidators;

use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

class ExecutionDateValidator extends Validator implements ValidatorInterface
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
        $date = $validator->getValue($attribute);
        //check date is valid
        $date = strtotime($date);
        if (!checkdate(date('m', $date), date('d', $date), date('Y', $date))) {
            $message = $this->getOption('message');
            $validator->appendMessage(new Message($message, $attribute, 'date'));

            return false;
        }

        /*$executionDate = $date[0] . '-' . str_pad($date[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($date[2], 2, '0', STR_PAD_LEFT) . ' ' . str_pad($date[3], 2, '0', STR_PAD_LEFT) . ':' . str_pad($date[4], 2, '0', STR_PAD_LEFT) . ':00';
        $utcExecutionDate = Module::getService('Time')->timezoneTimeToUtc($executionDate, $date[5]);
        //var_dump($executionDate, date('Y-m-d H:i:s'), $utcExecutionDate);
        //actual time is greater than execution date means invalid, since it won't occur.
        if (time() > $utcExecutionDate) {
            $message = $this->getOption('message');
            $validator->appendMessage(new Message($message, $attribute, 'date'));
            return false;
        }*/

        return true;
    }

}