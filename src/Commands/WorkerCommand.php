<?php

declare(strict_types=1);

namespace Grpcavel\Commands;

use Grpcavel\Runtime\Worker;
use Illuminate\Console\Command;

final class WorkerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grpc:worker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the gRPC worker (called by RoadRunner)';

    /**
     * Execute the console command.
     */
    public function handle(Worker $worker): void
    {
        $worker->serve();
    }
}
