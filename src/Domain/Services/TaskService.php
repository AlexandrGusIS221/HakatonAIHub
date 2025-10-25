<?php
// app/src/Domain/Services/TaskService.php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Entities\Task;
use App\Domain\Entities\UserProgress;
use App\Domain\ValueObjects\TaskPriority;

class TaskService
{
    public function createTask(
        string $title,
        string $category,
        TaskPriority $priority,
        int $basePoints,
        ?string $dueDate = null
    ): Task {
        $dueDateTime = $dueDate ? new \DateTimeImmutable($dueDate) : null;
        
        return new Task(
            uniqid('task_', true),
            $title,
            $category,
            $priority,
            $basePoints,
            false,
            null,
            new \DateTimeImmutable(),
            $dueDateTime
        );
    }

    public function completeTask(Task $task, UserProgress $progress): void
    {
        if (!$task->isCompleted()) {
            $task->complete();
            $progress->addPoints($task->getCalculatedPoints());
            
            // Check for achievements
            if ($progress->getPoints() >= 100 && !in_array('first_100_points', $progress->getAchievements())) {
                $progress->addAchievement('first_100_points');
            }
        }
    }

    public function reopenTask(Task $task, UserProgress $progress): void
    {
        if ($task->isCompleted()) {
            $task->reopen();
            // Optionally deduct points when reopening
            // $progress->deductPoints($task->getCalculatedPoints());
        }
    }

    public function calculateDailyProgress(array $tasks): float
    {
        $total = count($tasks);
        if ($total === 0) return 0;
        
        $completed = count(array_filter($tasks, fn($task) => $task->isCompleted()));
        return ($completed / $total) * 100;
    }

    public function getTasksForDate(array $tasks, string $date): array
    {
        return array_filter($tasks, function($task) use ($date) {
            $taskDate = $task->isCompleted() && $task->getCompletedAt() 
                ? $task->getCompletedAt()->format('Y-m-d')
                : $task->getCreatedAt()->format('Y-m-d');
            return $taskDate === $date;
        });
    }
}