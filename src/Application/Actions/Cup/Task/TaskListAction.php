<?php

namespace App\Application\Actions\Cup\Task;

use App\Application\Actions\Action;
use Psr\Container\ContainerInterface;

class TaskListAction extends Action
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    protected $taskRepository;

    /**
     * @inheritDoc
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->taskRepository = $this->entityManager->getRepository(\App\Domain\Entities\Task::class);
    }

    protected function action(): \Slim\Http\Response
    {
        $tasks = collect(
            $this->taskRepository->findBy(['status' => [\App\Domain\Types\TaskStatusType::STATUS_QUEUE, \App\Domain\Types\TaskStatusType::STATUS_WORK]], ['status' => 'desc'])
        );
        $tasks->map(function ($obj) {
            $obj->action = str_replace('App\Domain\Tasks\\', '', $obj->action);
        });

        return $this->respondWithData($tasks);
    }
}