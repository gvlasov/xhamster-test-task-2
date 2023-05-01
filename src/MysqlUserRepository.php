<?php

namespace Gvlasov\XhamsterTestTask2;

use PDO;

class MysqlUserRepository implements UserRepository
{

    public function __construct(
        protected PDO                 $pdo,
        protected UserValidator       $userValidator,
        protected UserModificationLog $log
    )
    {
    }

    /**
     * @param User $user
     * @return void
     * @throws ValidationException
     */
    public function add(User $user): void
    {
        $this->userValidator->validate($user);
        $query = 'INSERT INTO users (name, email, created, deleted, notes) 
                      VALUES (:name, :email, :created, null, :notes)';
        $params = [
            'name' => $user->name,
            'email' => $user->email,
            'created' => $user->created,
            'notes' => $user->notes,
        ];
        $stmt = $this->pdo->prepare($query);
        $stmt
            ->execute($params);
        $this->log->logChange([$query, $params]);
    }

    public function hardDeleteById(int $id): void
    {
        $query = 'DELETE FROM users WHERE id = :id';
        $params = ['id' => $id];
        $this->pdo
            ->prepare($query)
            ->execute($params);
        $this->log->logChange([$query, $params]);
    }

    public function getById(int $id): User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_FUNC, [$this, 'materialize'])[0];
    }

    public function getAll(): iterable
    {
        return $this->pdo->query('SELECT * FROM users')
            ->fetchAll(
                PDO::FETCH_FUNC,
                [$this, 'materialize']
            );
    }

    public function update(User $user): void
    {
        if (!isset($user->id)) {
            throw new \InvalidArgumentException('User id is not set');
        }
        $this->userValidator->validate($user);
        $query = 'UPDATE users SET name = :name, email = :email, notes = :notes WHERE id = :id';
        $params = [
            'name' => $user->name,
            'email' => $user->email,
            'notes' => $user->notes,
            'id' => $user->id
        ];
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $this->log->logChange([$query, $params]);
    }

    public function getByEmail(string $email): User
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users where email = :email'
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetchAll(PDO::FETCH_FUNC, [$this, 'materialize'])[0];
    }

    public function getByName(string $name): User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users where name = :name');
        $stmt->execute(['name' => $name]);
        return $stmt->fetchAll(PDO::FETCH_FUNC, [$this, 'materialize'])[0];
    }

    public function softDeleteById(int $id): void
    {
        $query = 'UPDATE users SET deleted = NOW() WHERE id = :id';
        $params = [
            'id' => $id
        ];
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $this->log->logChange([$query, $params]);
    }

    protected function materialize($id, $name, $email, $created, $deleted, $notes): User
    {
        $user = new User($name, $email);
        $user->id = $id;
        $user->created = $created;
        $user->deleted = $deleted;
        $user->notes = $notes;
        return $user;
    }

}