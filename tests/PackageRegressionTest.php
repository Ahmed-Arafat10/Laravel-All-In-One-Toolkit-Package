<?php

use AhmedArafat\AllInOne\Console\GitCommand;
use AhmedArafat\AllInOne\Services\Smtp\SmartMailSender;
use AhmedArafat\AllInOne\Traits\Eloquent\FilterableTrait;
use AhmedArafat\AllInOne\Traits\Eloquent\SearchableTrait;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;

final class PackageRegressionTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->instance('config', new Repository());
        Facade::setFacadeApplication($this->app);
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        $this->app->flush();
        Application::setInstance(null);
        Mockery::close();
    }

    public function test_scopes_filter_and_search_database_rows(): void
    {
        $db = new Capsule($this->app);
        $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $db->bootEloquent();
        $db->getConnection()->statement('CREATE TABLE products (name TEXT, category TEXT)');
        $db->getConnection()->table('products')->insert([
            ['name' => 'Red apple', 'category' => 'fruit'],
            ['name' => 'Green pear', 'category' => 'fruit'],
            ['name' => 'Red pepper', 'category' => 'vegetable'],
        ]);
        $this->app->instance('request', Request::create('/', 'GET', [
            'filter_col' => 'type', 'filter_val' => 'fruit', 'search' => 'Red',
        ]));

        self::assertSame(['Red apple'], RegressionProduct::query()->filter()->search()->pluck('name')->all());
    }

    public function test_failed_smtp_account_is_skipped_before_sending_with_the_second(): void
    {
        $this->configureMail();
        $mailer = Mockery::mock();
        Mail::shouldReceive('purge')->twice()->with('smtp');
        Mail::shouldReceive('mailer')->twice()->with('smtp')->andReturn($mailer);
        $mailer->shouldReceive('to')->twice()->with('user@example.org')->andReturnSelf();
        $hosts = [];
        $mailer->shouldReceive('send')->twice()->andReturnUsing(function () use (&$hosts) {
            $hosts[] = config('mail.mailers.smtp.host');
            if (count($hosts) === 1) {
                throw new RuntimeException('First account unavailable');
            }
        });

        SmartMailSender::send(new Mailable(), 'user@example.org');
        self::assertSame(['first.example', 'second.example'], $hosts);
    }

    public function test_mailpit_failure_exhausts_pool_after_one_attempt(): void
    {
        $this->configureMail();
        config(['all-in-one.smtpData.isTesting' => true]);
        $mailer = Mockery::mock();
        Mail::shouldReceive('purge')->once()->with('smtp');
        Mail::shouldReceive('mailer')->once()->with('smtp')->andReturn($mailer);
        $mailer->shouldReceive('to')->once()->andReturnSelf();
        $mailer->shouldReceive('send')->once()->andThrow(new RuntimeException('Offline'));

        $this->expectExceptionMessage('All SMTP accounts exhausted');
        SmartMailSender::send(new Mailable(), 'user@example.org');
    }

    private function configureMail(): void
    {
        config([
            'cache.default' => 'array',
            'cache.stores.array' => ['driver' => 'array'],
            'all-in-one.smtpData.isTesting' => false,
            'all-in-one.smtpData.dailyLimit' => 2,
            'all-in-one.smtpData.accounts' => array_map(fn ($name) => [
                'name' => $name, 'host' => $name.'.example', 'port' => 587,
                'encryption' => 'tls', 'username' => $name, 'password' => 'test',
            ], ['first', 'second']),
        ]);
        $this->app->instance('cache', new CacheManager($this->app));
        $handler = $this->createMock(ExceptionHandler::class);
        $handler->expects($this->once())->method('report');
        $this->app->instance(ExceptionHandler::class, $handler);
        Log::swap(Mockery::mock()->shouldReceive('channel')->andReturn(new NullLogger())->getMock());
        Mail::swap(Mockery::mock());
    }

    public function test_git_arguments_are_literal(): void
    {
        Process::fake();
        $message = 'fix "quotes" & echo example';
        $command = new GitCommand();
        $command->setLaravel($this->app);
        $result = (new CommandTester($command))->execute(['msg' => $message, 'branch' => 'master']);
        self::assertSame(0, $result);
        Process::assertRan(fn ($p) => is_array($p->command) && array_slice($p->command, 0, 3) === ['git', 'commit', '-m']
            && count($p->command) === 4 && str_starts_with($p->command[3], $message.' --- '));
        Process::assertRan(fn ($p) => $p->command === ['git', 'push', '--', 'origin', 'master']);
    }

    public function test_git_stops_on_failure(): void
    {
        Process::fake(fn () => Process::result(exitCode: 17));
        $command = new GitCommand();
        $command->setLaravel($this->app);
        $result = (new CommandTester($command))->execute(['msg' => 'example', 'branch' => 'master']);
        self::assertSame(17, $result);
        Process::assertDidntRun(fn ($p) => $p->command !== ['git', 'status']);
    }
}

final class RegressionProduct extends Model
{
    use FilterableTrait, SearchableTrait;

    protected $table = 'products';
    protected $filterableColumns = ['products' => ['columns' => ['type' => 'category']]];
    protected $searchableColumns = ['products' => ['columns' => ['name']]];
}
