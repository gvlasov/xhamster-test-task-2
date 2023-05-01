<?php

namespace Gvlasov\XhamsterTestTask2;

use Carbon\Carbon;

class User
{

    public int $id;

    public string $created;

    public ?string $deleted = null;

    public function __construct(
        public string  $name,
        public string  $email,
        public ?string $notes = null
    )
    {
        $this->created = Carbon::now('UTC')->format('Y-m-d H:i:s');
    }

    public function getEmailDomain(): string
    {
        return explode('@', $this->email)[1];
    }
}