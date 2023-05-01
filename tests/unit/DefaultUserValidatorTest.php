<?php

namespace unit;


use Carbon\Carbon;
use Gvlasov\XhamsterTestTask2\DefaultUserValidator;
use Gvlasov\XhamsterTestTask2\ProhibitedWords;
use Gvlasov\XhamsterTestTask2\TrustedDomains;
use Gvlasov\XhamsterTestTask2\User;
use Gvlasov\XhamsterTestTask2\UserValidator;
use Gvlasov\XhamsterTestTask2\ValidationException;
use Tests\Helpers\DomainGoogleComIsProhibited;
use Tests\Helpers\WordBollocksIsProhibited;

class DefaultUserValidatorTest extends \PHPUnit\Framework\TestCase
{

    protected function makeValidator(): UserValidator
    {
        return new DefaultUserValidator(
            new class implements ProhibitedWords {
                public function hasProhibitedWords(string $string): bool
                {
                    return false;
                }
            },
            new class implements TrustedDomains {
                public function isDomainTrusted(string $domain): bool
                {
                    return true;
                }
            }
        );
    }

    public function test_name_must_be_8_characters_or_longer()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Name must be at least 8 characters long');
        $this->makeValidator()->validate(
            new User('asdf', 'asdf@asdf.com')
        );
    }

    public function test_name_must_be_lowercase_alphanumeric(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Name can only contain lowercase alphanumeric characters');
        $user = new User('Mc&Donalds', 'frosty@chilly.com');
        $this->makeValidator()->validate($user);
    }

    public function test_name_must_not_contain_prohibited_words(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Name contains prohibited words');
        (new DefaultUserValidator(
            new class implements ProhibitedWords {
                public function hasProhibitedWords(string $string): bool
                {
                    return true;
                }
            },
            new class implements TrustedDomains {
                public function isDomainTrusted(string $domain): bool
                {
                    return true;
                }
            }
        ))
            ->validate(new User('bollocks69', 'frosty@chilly.com'));
    }

    public function test_email_must_be_valid(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email must be a valid email');
        $user = new User('asdfadsf', 'email');
        $this->makeValidator()->validate($user);
    }

    public function test_email_must_be_on_trusted_domain(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email must be on trusted domain');
        $user = new User('asdfadsf', 'email@google.com');
        (
            new DefaultUserValidator(
                new class implements ProhibitedWords {
                    public function hasProhibitedWords(string $string): bool
                    {
                        return false;
                    }
                },
                new class implements TrustedDomains {
                    public function isDomainTrusted(string $domain): bool
                    {
                        return false;
                    }
                }
            )
        )->validate($user);
    }

    public function test_deleted_cant_be_less_than_created(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Deleted time must be >= created time');
        $user = new User('frosty123', 'frosty@chilly.com');
        $user->created = Carbon::now('UTC')->format('Y-m-d H:i:s');
        $user->deleted = Carbon::now('UTC')->subSeconds(10)->format('Y-m-d H:i:s');;
        $this->makeValidator()->validate($user);
    }

}