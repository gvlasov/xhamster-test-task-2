<?php

namespace Gvlasov\XhamsterTestTask2;


interface TrustedDomains
{

    public function isDomainTrusted(string $email): bool;

}