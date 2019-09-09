<?php

namespace Tests\Platform\Domains\TaskManager;

use SuperV\Platform\Domains\TaskManager\Contracts\Task;
use SuperV\Platform\Domains\TaskManager\Contracts\TaskHandler;
use Tests\Platform\Domains\TaskManager\Fixtures\TestHandler;

class TaskTest extends TestCase
{
    function test__create()
    {
        $taskData = $this->makeTaskData();
        $task = $this->makeTaskModel($taskData);

        $this->assertNotNull($task);
        $this->assertArrayContains($taskData, $task->toArray());
        $this->assertInstanceOf(Task::class, $task);

        $this->assertEquals(TestHandler::class, $task->getHandlerClass());
        $this->assertInstanceOf(TaskHandler::class, $task->getHandler());
        $this->assertEquals($taskData['payload'], $task->getPayload());
    }
}