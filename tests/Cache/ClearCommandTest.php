<?php

namespace Illuminate\Tests\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Contracts\Cache\Repository;

class ClearCommandTest extends TestCase
{
    /**
     * @var ClearCommandTestStub
     */
    private $command;

    /**
     * @var CacheManager|m\Mock
     */
    private $cacheManager;

    /**
     * @var Filesystem|m\Mock
     */
    private $files;

    /**
     * @var Repository|m\Mock
     */
    private $cacheRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cacheManager = m::mock('Illuminate\Cache\CacheManager');
        $this->files = m::mock('Illuminate\Filesystem\Filesystem');
        $this->cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');
        $this->command = new ClearCommandTestStub($this->cacheManager, $this->files);

        $app = new Application;
        $app['path.storage'] = __DIR__;
        $this->command->setLaravel($app);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testClearWithNoStoreArgument()
    {
        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($this->command);
    }

    public function testClearWithStoreArgument()
    {
        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with('foo')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($this->command, ['store' => 'foo']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearWithInvalidStoreArgument()
    {
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with('bar')->andThrow('InvalidArgumentException');
        $this->cacheRepository->shouldReceive('flush')->never();

        $this->runCommand($this->command, ['store' => 'bar']);
    }

    public function testClearWithTagsOption()
    {
        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('tags')->once()->with(['foo', 'bar'])->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($this->command, ['--tags' => 'foo,bar']);
    }

    public function testClearWithStoreArgumentAndTagsOption()
    {
        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn([]);

        $this->cacheManager->shouldReceive('store')->once()->with('redis')->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('tags')->once()->with(['foo'])->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->runCommand($this->command, ['store' => 'redis', '--tags' => 'foo']);
    }

    public function testClearWillClearRealTimeFacades()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        $this->files->shouldReceive('exists')->andReturn(true);
        $this->files->shouldReceive('files')->andReturn(['/facade-XXXX.php']);
        $this->files->shouldReceive('delete')->with('/facade-XXXX.php')->once();

        $this->runCommand($this->command);
    }

    public function testClearWillNotClearRealTimeFacadesIfCacheDirectoryDoesntExist()
    {
        $this->cacheManager->shouldReceive('store')->once()->with(null)->andReturn($this->cacheRepository);
        $this->cacheRepository->shouldReceive('flush')->once();

        // No files should be looped over and nothing should be deleted if the cache directory doesn't exist
        $this->files->shouldReceive('exists')->andReturn(false);
        $this->files->shouldNotReceive('files');
        $this->files->shouldNotReceive('delete');

        $this->runCommand($this->command);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new \Symfony\Component\Console\Input\ArrayInput($input), new \Symfony\Component\Console\Output\NullOutput);
    }
}

class ClearCommandTestStub extends ClearCommand
{
    public function call($command, array $arguments = [])
    {
        //
    }
}
