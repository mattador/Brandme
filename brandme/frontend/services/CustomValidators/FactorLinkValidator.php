<?php

namespace Frontend\Services\CustomValidators;

use Entities\FactorMeta;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

class FactorLinkValidator extends Validator implements ValidatorInterface
{

    /**
     * Executes the validation
     *
     * @param \Phalcon\Validation $validator
     * @param string              $attribute
     * @return boolean
     * @deprecated
     */
    public function validate(\Phalcon\Validation $validator, $attribute)
    {
        $link = str_replace('http://brandme.la/perfil/', '', $validator->getValue($attribute));
        if (strlen(trim($link)) == 0) {
            return true;
        }
        if (!preg_match('/^[a-zA-Z\-]+$/', $link) || substr_count($link, '-') > 1) {
            $validator->appendMessage(
                new Message('Your personalized link must be letters only (with one "-" allowed).', $attribute, 'link')
            );

            return false;
        }
        if (strlen($link) < 10 || strlen($link) > 20) {
            $validator->appendMessage(new Message('Your personalized link must between 10 and 20 characters long.', $attribute, 'link'));

            return false;
        }
        if ((int)FactorMeta::count('profile_link = "'.$link.'"') > 0) {
            $validator->appendMessage(new Message('Your chosen personalized link is already in use.', $attribute, 'link'));

            return false;
        }

        return true;
    }

}