<?php

namespace Frontend\Services\CustomValidators;

use Entities\Region;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

/**
 * This entity currently only applies to Mexico
 * Class RegionPostcodeValidator
 *
 * @package Frontend\Services\CustomValidators
 */
class RegionPostcodeValidator extends Validator implements ValidatorInterface
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
        $postcode = $validator->getValue($attribute);
        if ((int)Region::count('postcode = "'.$postcode.'"') == 0) {
            $message = $this->getOption('message');
            $validator->appendMessage(new Message($message, $attribute, 'Postcode'));

            return false;
        }

        return true;
    }

}