# xhamster-test-task-2

### Running

```sh
composer install
docker-compose up -d --wait
vendor/bin/phpunit
```

### Usage

```php 
$repo = new MysqlUserRepository(
    $pdo,
    $userVaidator,
    $userModificationLog
);

$user = new User('william123', 'will@google.com');
$repo->add($user);

$user->email = 'will@bing.com';
$repo->update($user);

$users = $repo->findAll();

$user = $repo->findById($user->id);

$repo->softDeleteById($user->id);

$repo->hardDeleteByid($user->id);

(new DefaultUservalidator())->validate(new User('asdf', 'asdf@asdf.com'));
```