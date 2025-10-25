<?php
// src/Application/Controllers/TaskController.php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Services\TaskService;
use App\Application\Services\SessionStorageService;
use App\Domain\ValueObjects\TaskPriority;

class TaskController
{
    public function __construct(
        private TaskService $taskService,
        private SessionStorageService $storage
    ) {}

    public function handleAddTask(array $data): array
    {
        try {
            $task = $this->taskService->createTask(
                htmlspecialchars(trim($data['title'])),
                $data['category'] ?? 'personal',
                TaskPriority::from($data['priority'] ?? 'medium'),
                (int) ($data['points'] ?? 10),
                $data['due_date'] ?? null
            );

            $taskData = $task->toArray();
            // Добавляем поле для отслеживания начисления очков
            $taskData['points_awarded'] = false;
            $this->storage->saveTask($taskData);
            
            return [
                'success' => true,
                'task' => $taskData,
                'progress' => $this->storage->getProgress()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function handleToggleTask(string $id): array
    {
        try {
            $tasks = $this->storage->getTasks();
            $progressData = $this->storage->getProgress();
            $taskFound = false;
            
            // Инициализируем прогресс если его нет
            if (!isset($progressData['points']) || !isset($progressData['level'])) {
                $progressData = ['points' => 0, 'level' => 1, 'achievements' => []];
            }
            
            foreach ($tasks as &$task) {
                if ($task['id'] === $id) {
                    $taskFound = true;
                    $previousState = $task['completed'] ?? false;
                    $newState = !$previousState;
                    
                    // Обновляем статус задачи
                    $task['completed'] = $newState;
                    $task['completed_at'] = $newState ? date('Y-m-d H:i:s') : null;
                    
                    // ЛОГИКА ОЧКОВ:
                    // - Начисляем очки только при выполнении (переход из невыполненной в выполненную)
                    // - Снимаем очки только при отмене выполнения (переход из выполненной в невыполненную)
                    // - Проверяем, были ли уже начислены очки для этой задачи
                    
                    $points = $task['points'] ?? 0;
                    
                    if ($newState && !$previousState) {
                        // ВЫПОЛНЕНИЕ: начисляем очки, если еще не начисляли
                        if (!($task['points_awarded'] ?? false)) {
                            $progressData['points'] = (int)($progressData['points'] ?? 0) + (int)$points;
                            $task['points_awarded'] = true;
                        }
                    } else if (!$newState && $previousState) {
                        // ОТМЕНА ВЫПОЛНЕНИЯ: снимаем очки, если они были начислены
                        if ($task['points_awarded'] ?? false) {
                            $progressData['points'] = max(0, (int)($progressData['points'] ?? 0) - (int)$points);
                            $task['points_awarded'] = false;
                        }
                    }
                    
                    // Всегда пересчитываем уровень после изменения очков
                    $progressData['level'] = max(1, floor($progressData['points'] / 100) + 1);
                    
                    $this->storage->updateTask($id, $task);
                    $this->storage->saveProgress($progressData);
                    
                    break;
                }
            }

            if (!$taskFound) {
                return ['success' => false, 'error' => 'Task not found'];
            }
            
            return [
                'success' => true,
                'progress' => $progressData,
                'task' => $task // Возвращаем обновленную задачу для UI
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function handleDeleteTask(string $id): array
    {
        try {
            // При удалении задачи снимаем очки, если они были начислены
            $tasks = $this->storage->getTasks();
            $progressData = $this->storage->getProgress();
            
            foreach ($tasks as $task) {
                if ($task['id'] === $id && ($task['completed'] ?? false) && ($task['points_awarded'] ?? false)) {
                    $points = $task['points'] ?? 0;
                    $progressData['points'] = max(0, (int)($progressData['points'] ?? 0) - (int)$points);
                    $progressData['level'] = max(1, floor($progressData['points'] / 100) + 1);
                    $this->storage->saveProgress($progressData);
                    break;
                }
            }
            
            $this->storage->deleteTask($id);
            return [
                'success' => true,
                'progress' => $this->storage->getProgress()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}