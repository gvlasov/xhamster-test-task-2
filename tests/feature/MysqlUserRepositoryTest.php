<?php

use Gvlasov\XhamsterTestTask2\DefaultUserValidator;
use Gvlasov\XhamsterTestTask2\MysqlUserRepository;
use Gvlasov\XhamsterTestTask2\ProhibitedWords;
use Gvlasov\XhamsterTestTask2\TrustedDomains;
use Gvlasov\XhamsterTestTask2\User;
use Gvlasov\XhamsterTestTask2\UserModificationLog;
use Gvlasov\XhamsterTestTask2\UserValidator;
use Gvlasov\XhamsterTestTask2\ValidationException;
use PHPUnit\Framework\TestCase;

class MysqlUserRepositoryTest extends TestCase
{

    protected MysqlUserRepository $repo;

    protected PDO $pdo;

    public function setUp(): void
    {

        $env = parse_ini_file(__DIR__ . '/../../.env');
        $this->pdo = new PDO(
            "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};port={$env['DB_PORT']}",
            $env['DB_USER'],
            $env['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        /** @var TrustedDomains $trustedDomains */
        $trustedDomains = Mockery::mock(TrustedDomains::class);
        $trustedDomains->shouldReceive('isDomainTrusted')->andReturn(true);
        /** @var ProhibitedWords $prohibitedWords */
        $prohibitedWords = Mockery::mock(ProhibitedWords::class);
        $prohibitedWords->shouldReceive('hasProhibitedWords')->andReturn(false);
        $this->repo = new MysqlUserRepository(
            $this->pdo,
            new DefaultUserValidator(
                $prohibitedWords,
                $trustedDomains
            ),
            new class implements UserModificationLog {
                public function logChange($change): void
                {
                }
            }
        );
        $this->pdo->exec('TRUNCATE TABLE users');
    }

    public function test_can_create_new_users()
    {
        $user = new User('frosty123', 'frosty@chilly.com');
        $this->repo->add($user);
        $this->assertCount(1, iterator_to_array($this->repo->getAll()));
    }

    public function test_can_update_existing_user()
    {
        {
            $user = new User('frosty123', 'frosty@chilly.com');
            $this->repo->add($user);
            $user = $this->repo->getByEmail($user->email);
        }
        $newEmail = 'asdf@asdf.com';
        $user->email = $newEmail;
        $this->repo->update($user);
        $this->assertCount(1, iterator_to_array($this->repo->getAll()));
        $this->assertEquals(
            $newEmail,
            $this->repo->getById($user->id)->email
        );
    }

    public function test_can_soft_delete_user()
    {
        {
            $user = new User('frosty123', 'frosty@chilly.com');
            $this->repo->add($user);
            $user = $this->repo->getByEmail($user->email);
            $newEmail = 'asdf@asdf.com';
            $user->email = $newEmail;
        }
        $this->repo->softDeleteById($user->id);
        $this->assertCount(1, iterator_to_array($this->repo->getAll()));
        $user = $this->repo->getById($user->id);
        $this->assertNotNull($user->deleted);
        $this->assertGreaterThanOrEqual($user->created, $user->deleted);
    }

    public function test_applies_validation_on_add()
    {
        $this->expectException(ValidationException::class);
        $user = new User(
            'short',
            'frosty@chilly.com'
        );
        $this->repo->add($user);
    }

    public function test_applies_validation_on_update()
    {
        $this->expectException(ValidationException::class);
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('frosty123', 'frosty@chilly.com', NOW(), null, null)"
        );
        $user = $this->repo->getByName('frosty123');
        $user->email = 'incorrect_email';
        $this->repo->update($user);
    }

    public function test_name_must_be_unique_on_add()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('frosty123', 'frosty@chilly.com', NOW(), null, null)"
        );
        $user = new User('frosty123', 'frosty@cold.com');
        $this->repo->add($user);
    }

    public function test_name_must_be_unique_on_update()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('frosty123', 'frosty@chilly.com', NOW(), null, null)"
        );
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('letter999', 'a@b.com', NOW(), null, null)"
        );
        $user = $this->repo->getByName('frosty123');
        $user->name = 'letter999';
        $this->repo->update($user);
    }

    public function test_email_must_be_unique_on_add()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('frosty123', 'frosty@chilly.com', NOW(), null, null)"
        );
        $user = new User('frosty000', 'frosty@chilly.com');
        $this->repo->add($user);
    }

    public function test_email_must_be_unique_on_update()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('frosty123', 'frosty@chilly.com', NOW(), null, null)"
        );
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('letter999', 'a@b.com', NOW(), null, null)"
        );
        $user = $this->repo->getByName('frosty123');
        $user->email = 'a@b.com';
        $this->repo->update($user);
    }

    public function test_deleted_is_null_on_active_user(): void
    {
        $user = new User('frosty123', 'frosty@chilly.com');
        $this->repo->add($user);
        $this->assertNull($this->repo->getByEmail($user->email)->deleted);
    }

    public function test_adding_is_journaled()
    {
        $log = $this->createMock(UserModificationLog::class);
        $log->expects($this->once())->method('logChange');
        $this->repo = new MysqlUserRepository(
            $this->pdo,
            new class implements UserValidator {
                public function validate(User $user): void
                {
                }
            },
            $log
        );
        $user = new User('frosty123', 'frosty@chilly.com');
        $this->repo->add($user);
    }

    public function test_updating_is_journaled()
    {
        $log = $this->createMock(UserModificationLog::class);
        $log->expects($this->once())->method('logChange');
        $this->repo = new MysqlUserRepository(
            $this->pdo,
            new class implements UserValidator {
                public function validate(User $user): void
                {
                }
            },
            $log
        );
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('frosty123', 'frosty@chilly.com', NOW(), null, null)"
        );
        $user = $this->repo->getByName('frosty123');
        $user->email = 'frosty@newdomain.com';
        $this->repo->update($user);
    }

    public function test_soft_deletion_is_journaled()
    {
        $log = $this->createMock(UserModificationLog::class);
        $log->expects($this->once())->method('logChange');
        $this->repo = new MysqlUserRepository(
            $this->pdo,
            new class implements UserValidator {
                public function validate(User $user): void
                {
                }
            },
            $log
        );
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('frosty123', 'frosty@chilly.com', NOW(), null, null)"
        );
        $user = $this->repo->getByName('frosty123');
        $this->repo->softDeleteById($user->id);
    }

    public function test_hard_deletion_is_journaled()
    {
        $log = $this->createMock(UserModificationLog::class);
        $log->expects($this->once())->method('logChange');
        $this->repo = new MysqlUserRepository(
            $this->pdo,
            new class implements UserValidator {
                public function validate(User $user): void
                {
                }
            },
            $log
        );
        $this->pdo->exec(
            "INSERT INTO users (name, email, created, deleted, notes) VALUES ('frosty123', 'frosty@chilly.com', NOW(), null, null)"
        );
        $user = $this->repo->getByName('frosty123');
        $this->repo->hardDeleteById($user->id);
    }

}