<?php
/** $Id$
 * Email.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Form\Validators;

use \Core\Form\abstractValidator;

class Email extends abstractValidator {

    CONST ERROR_INVALID_FORMAT = 'validator_email_invalid_format';

    CONST ERROR_INVALID_DOMAIN = 'validator_email_invalid_domain';

    protected $checkDomain = true;

    public function __construct($checkDomain = true)
    {
        $this->checkDomain = (bool)$checkDomain;
    }

    /**
     * @param $value
     * @return bool|void
     */
    public function isValid($value)
    {
        # General email validation
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)){
            $this->addError(self::ERROR_INVALID_FORMAT);
            return false;
        }

        # Check domain DNS
        if($this->checkDomain && !$this->isValidDomain($value)){
            $this->addError(self::ERROR_INVALID_DOMAIN);
            return false;
        }

        return (bool)!$this->isErrors();
    }

    protected function isValidDomain($email)
    {
        if(!strpos($email, '@')) return false;
        list($user, $domain) = explode('@', $email);
        if (checkdnsrr($domain, 'MX')) return true;
        return false;
    }
}