<?php

namespace Gvlasov\XhamsterTestTask2;


interface UserModificationLog
{

    public function logChange(mixed $change): void;

}