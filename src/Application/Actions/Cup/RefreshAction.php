<?php declare(strict_types=1);

namespace App\Application\Actions\Cup;

use App\Domain\AbstractAction;
use App\Domain\Models\Task;
use App\Domain\Service\Task\TaskService;

class RefreshAction extends AbstractAction
{
    protected function action(): \Slim\Psr7\Response
    {
        $taskService = $this->container->get(TaskService::class);
        $exists = (array) $this->getParam('tasks');

        $tasks = collect()
            ->merge($taskService->read(['uuid' => array_keys($exists)]))
            ->merge($taskService->read(['order' => ['date' => 'desc'], 'limit' => 50]))
            ->keyBy('uuid')
            ->sortBy('date');

        $output = ['new' => [], 'update' => [], 'delete' => []];

        /** @var Task $task */
        foreach ($tasks as $task) {
            if (!in_array($task->uuid, array_keys($exists), true)) {
                $output['new'][] = array_except($task->toArray(), ['params', 'output']);
            } else {
                if (
                    in_array($task->uuid, array_keys($exists), true)
                    && (
                        $task->status !== $exists[$task->uuid]['status'] || $task->progress !== (float) ($exists[$task->uuid]['progress'] ?? .0)
                    )
                ) {
                    $output['update'][] = array_except($task->toArray(), ['params', 'output']);
                }
            }
        }
        foreach ($exists as $uuid => $props) {
            if (!isset($tasks[$uuid])) {
                $output['delete'][] = $uuid;
            }
        }

        return $this->respondWithJson([
            'task' => $output,
        ]);
    }
}
