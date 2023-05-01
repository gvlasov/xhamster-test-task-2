<?php

namespace Gvlasov\XhamsterTestTask2;

class DefaultUserValidator implements UserValidator
{

    public function __construct(
        protected ProhibitedWords $prohibitedWords,
        protected TrustedDomains  $trustedDomains
    )
    {
    }

    public function validate(User $user): void
    {
        if (!$user->name) {
            throw new ValidationException('Name is required');
        }
        if (!$user->email) {
            throw new ValidationException('Email is required');
        }
        if (strlen($user->name) < 8) {
            throw new ValidationException('Name must be at least 8 characters long');
        }
        if (!preg_match('/^[a-z0-9]+$/', $user->name)) {
            throw new ValidationException('Name can only contain lowercase alphanumeric characters');
        }
        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Email must be a valid email');
        }
        if ($this->prohibitedWords->hasProhibitedWords($user->name)) {
            throw new ValidationException('Name contains prohibited words');
        }
        if (!$this->trustedDomains->isDomainTrusted($user->getEmailDomain())) {
            throw new ValidationException('Email must be on trusted domain');
        }
        if (
            $user->deleted !== null
            && $user->deleted < $user->created
        ) {
            throw new ValidationException('Deleted time must be >= created time');
        }
    }

}
