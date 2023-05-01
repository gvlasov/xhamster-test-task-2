<?php

namespace unit;


use Carbon\Carbon;
use Gvlasov\XhamsterTestTask2\DefaultUserValidator;
use Gvlasov\XhamsterTestTask2\ProhibitedWords;
use Gvlasov\XhamsterTestTask2\TrustedDomains;
use Gvlasov\XhamsterTestTask2\User;
use Gvlasov\XhamsterTestTask2\UserValidator;
use Gvlasov\XhamsterTestTask2\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\DomainGoogleComIsProhibited;
use Tests\Helpers\WordBollocksIsProhibited;

class UserTest extends TestCase
{

    public function test_gets_email_domain()
    {
        $this->assertEquals(
            'bbb.com',
            (new User('asdfadsf', 'aaa@bbb.com'))->getEmailDomain()
        );
    }
}