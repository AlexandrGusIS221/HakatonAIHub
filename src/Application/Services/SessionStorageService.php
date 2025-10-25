<?php
// src/Application/Services/SessionStorageService.php

declare(strict_types=1);

namespace App\Application\Services;

class SessionStorageService
{
    private const SESSION_KEYS = [
        'tasks' => 'app_tasks_v5', // Новая версия
        'progress' => 'app_progress_v5', 
        'mood' => 'app_mood_v5',
        'achievements' => 'app_achievements_v5'
    ];

    public function __construct()
    {
        if (session_status() === \PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->initializeSession();
    }

    private function initializeSession(): void
    {
        // Инициализируем прогресс с правильными значениями
        if (empty($_SESSION[self::SESSION_KEYS['progress']])) {
            $_SESSION[self::SESSION_KEYS['progress']] = [
                'points' => 0,
                'level' => 1,
                'achievements' => []
            ];
        } else {
            // Исправляем существующий прогресс если нужно
            $progress = $_SESSION[self::SESSION_KEYS['progress']];
            if (!isset($progress['points']) || $progress['points'] > 10000) {
                $progress['points'] = 0;
            }
            if (!isset($progress['level']) || $progress['level'] > 1000) {
                $progress['level'] = 1;
            }
            $_SESSION[self::SESSION_KEYS['progress']] = $progress;
        }
        
        // Миграция задач: добавляем поле points_awarded если его нет
        if (isset($_SESSION[self::SESSION_KEYS['tasks']])) {
            foreach ($_SESSION[self::SESSION_KEYS['tasks']] as &$task) {
                if (!isset($task['points_awarded'])) {
                    // Если задача выполнена, помечаем что очки начислены
                    $task['points_awarded'] = ($task['completed'] ?? false);
                }
            }
        }
        
        foreach (self::SESSION_KEYS as $key) {
            if (!isset($_SESSION[$key])) {
                $_SESSION[$key] = [];
            }
        }
    }

    // Task methods
    public function getTasks(): array
    {
        return $_SESSION[self::SESSION_KEYS['tasks']] ?? [];
    }

    public function saveTask(array $task): void
    {
        $_SESSION[self::SESSION_KEYS['tasks']][] = $task;
    }

    public function updateTask(string $id, array $updatedTask): void
    {
        foreach ($_SESSION[self::SESSION_KEYS['tasks']] as &$task) {
            if ($task['id'] === $id) {
                $task = $updatedTask;
                break;
            }
        }
    }

    public function deleteTask(string $id): void
    {
        $_SESSION[self::SESSION_KEYS['tasks']] = array_filter(
            $this->getTasks(),
            fn($task) => $task['id'] !== $id
        );
    }

    public function getTasksByDate(string $date): array
    {
        $tasks = $this->getTasks();
        return array_filter($tasks, function($task) use ($date) {
            if ($task['completed'] && isset($task['completed_at'])) {
                $taskDate = date('Y-m-d', strtotime($task['completed_at']));
            } else {
                $taskDate = date('Y-m-d', strtotime($task['created_at']));
            }
            return $taskDate === $date;
        });
    }

    public function getRecentDatesWithTasks(int $limit = 7): array
    {
        $tasks = $this->getTasks();
        $dates = [];
        
        foreach ($tasks as $task) {
            if ($task['completed'] && isset($task['completed_at'])) {
                $date = date('Y-m-d', strtotime($task['completed_at']));
            } else {
                $date = date('Y-m-d', strtotime($task['created_at']));
            }
            
            if (!in_array($date, $dates)) {
                $dates[] = $date;
            }
        }
        
        rsort($dates);
        return array_slice($dates, 0, $limit);
    }

    // Progress methods
    public function getProgress(): array
    {
        return $_SESSION[self::SESSION_KEYS['progress']] ?? [
            'points' => 0,
            'level' => 1,
            'achievements' => []
        ];
    }

    public function saveProgress(array $progress): void
    {
        $_SESSION[self::SESSION_KEYS['progress']] = $progress;
    }

    // Mood methods
    public function saveMoodEntry(string $date, int $value): void
    {
        $_SESSION[self::SESSION_KEYS['mood']][$date] = $value;
    }

    public function getMoodData(): array
    {
        return $_SESSION[self::SESSION_KEYS['mood']] ?? [];
    }

    // Achievements methods
    public function addAchievement(string $achievement): void
    {
        $achievements = $_SESSION[self::SESSION_KEYS['achievements']] ?? [];
        if (!in_array($achievement, $achievements)) {
            $achievements[] = $achievement;
            $_SESSION[self::SESSION_KEYS['achievements']] = $achievements;
        }
    }

    public function getAchievements(): array
    {
        return $_SESSION[self::SESSION_KEYS['achievements']] ?? [];
    }
}