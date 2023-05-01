<?php

namespace Gvlasov\XhamsterTestTask2;


interface ProhibitedWords
{

    public function hasProhibitedWords(string $text): bool;

}