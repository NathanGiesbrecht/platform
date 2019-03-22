<?php

namespace SuperV\Platform\Console\Jobs;

use Artisan;
use DB;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as SchemaBuilder;
use Log;
use Schema;
use SuperV\Platform\Events\PlatformInstalledEvent;
use SuperV\Platform\Exceptions\PlatformException;
use SuperV\Platform\Platform;
use SuperV\Platform\PlatformServiceProvider;

class InstallSuperV
{
    /**
     * @var \SuperV\Platform\Platform
     */
    protected $platform;

    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }
    
    public function __invoke()
    {
        $platformServiceProvider = new PlatformServiceProvider(app());

        DB::beginTransaction();

        try {
            $this->install($platformServiceProvider);
            $this->commit();

        } catch (Exception $e) {
            $this->rollback($e);
        }
    }

    public function setEnv($line)
    {
        list($variable, $value) = explode('=', $line, 2);

        $data = $this->readEnvironmentFile();

        array_set($data, $variable, $value);

        $this->writeEnvironmentFile($data);
    }

    protected function readEnvironmentFile()
    {
        $data = [];

        $file = base_path('.env');

        if (! file_exists($file)) {
            return $data;
        }

        foreach (file($file) as $line) {
            // Check for # comments.
            if (starts_with($line, '#')) {
                $data[] = $line;
            } elseif ($operator = strpos($line, '=')) {
                $key = substr($line, 0, $operator);
                $value = substr($line, $operator + 1);

                $data[$key] = $value;
            }
        }

        return $data;
    }

    protected function writeEnvironmentFile($data)
    {
        $contents = '';

        foreach ($data as $key => $value) {
            if ($key) {
                $contents .= PHP_EOL.strtoupper($key).'='.$value;
            } else {
                $contents .= PHP_EOL.$value;
            }
        }

        $file = base_path('.env');

        file_put_contents($file, $contents);
    }

    /**
     * @param \Exception $e
     * @throws \SuperV\Platform\Exceptions\PlatformException
     */
    protected function rollback(Exception $e): void
    {
        DB::rollBack();

        $this->setEnv('SV_INSTALLED=false');

        config(['superv.installed' => false]);

        Log::error($e->getMessage());

        PlatformException::fail($e->getMessage());
    }

    protected function commit(): void
    {
        DB::commit();

        Artisan::call('vendor:publish', ['--tag' => 'superv.config']);
//        Artisan::call('vendor:publish', ['--tag' => 'superv.views']);
        Artisan::call('vendor:publish', ['--tag' => 'superv.assets']);

        PlatformInstalledEvent::dispatch();

        $this->platform->fire('install');
    }

    /**
     * @param \SuperV\Platform\PlatformServiceProvider $platformServiceProvider
     */
    protected function install(PlatformServiceProvider $platformServiceProvider): void
    {
        if (! SchemaBuilder::hasTable('migrations')) {
            Artisan::call('migrate', ['--force' => true]);
        }
        if (! SchemaBuilder::hasColumn('migrations', 'scope')) {
            Schema::table('migrations', function (Blueprint $table) {
                $table->string('scope')->nullable();
            });
        }
        $this->setEnv('SV_INSTALLED=true');
        config(['superv.installed' => true]);

        $platformServiceProvider->register();

        Artisan::call('migrate', ['--scope' => 'platform', '--force' => true]);
    }
}