<?php


namespace Gvlasov\XhamsterTestTask2;


interface UserValidator
{

    public function validate(User $user): void;
}