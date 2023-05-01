<?php

namespace Gvlasov\XhamsterTestTask2;

interface UserRepository
{
    public function add(User $user): void;

    public function softDeleteById(int $id): void;

    public function getById(int $id): User;

    public function getAll(): iterable;

    public function update(User $user): void;

    public function getByEmail(string $email): User;

    public function getByName(string $name): User;

}