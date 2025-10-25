<?php
// public/index.php

declare(strict_types=1);

error_reporting(E_ALL);

ini_set('display_errors', '1');
// В начале файла, после error_reporting
if (isset($_GET['reset_all']) && $_GET['reset_all'] === 'true') {
    session_destroy();
    session_start();
    header('Location: /');
    exit;
}

// Manual class loading
$baseDir = __DIR__ . '/../src/';

require_once $baseDir . 'Domain/ValueObjects/TaskPriority.php';
require_once $baseDir . 'Domain/ValueObjects/MoodValue.php';
require_once $baseDir . 'Domain/Entities/Task.php';
require_once $baseDir . 'Domain/Entities/UserProgress.php';
require_once $baseDir . 'Domain/Services/TaskService.php';
require_once $baseDir . 'Domain/Services/AnalyticsService.php';
require_once $baseDir . 'Application/Services/SessionStorageService.php';
require_once $baseDir . 'Application/Controllers/TaskController.php';

use App\Application\Controllers\TaskController;
use App\Domain\Services\TaskService;
use App\Domain\Services\AnalyticsService;
use App\Application\Services\SessionStorageService;

// EMERGENCY FIX: Сброс прогресса при необходимости
if (isset($_GET['reset_progress']) && $_GET['reset_progress'] === 'true') {
    $storage = new SessionStorageService();
    $storage->saveProgress(['points' => 0, 'level' => 1, 'achievements' => []]);
    header('Location: /');
    exit;
}

// Simple DI container
try {
    $storage = new SessionStorageService();
    $taskService = new TaskService();
    $analyticsService = new AnalyticsService();
    
    $container = [
        'storage' => $storage,
        'taskService' => $taskService,
        'analyticsService' => $analyticsService,
        'taskController' => new TaskController($taskService, $storage)
    ];
    
} catch (Exception $e) {
    die("❌ Ошибка инициализации: " . $e->getMessage());
}

// Handle GET requests for day tasks view
if (isset($_GET['action']) && $_GET['action'] === 'get_day_tasks') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $tasks = $container['storage']->getTasks();
    
    $filteredTasks = array_filter($tasks, function($task) use ($date) {
        if ($task['completed'] && isset($task['completed_at'])) {
            $taskDate = date('Y-m-d', strtotime($task['completed_at']));
            return $taskDate === $date;
        }
        return false;
    });
    
    echo '<div class="tasks-list">';
    if (empty($filteredTasks)) {
        echo '<p class="text-muted text-center">Нет выполненных задач на этот день</p>';
    } else {
        foreach ($filteredTasks as $task) {
            include __DIR__ . '/../templates/components/task_card.php';
        }
    }
    echo '</div>';
    exit;
}

// Handle POST API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? null)) {
    header('Content-Type: application/json');
    echo handleApiRequest($container, $_POST);
    exit;
}

// Render main dashboard
renderDashboard($container);

function handleApiRequest(array $container, array $data): string 
{
    try {
        $controller = $container['taskController'];
        
        switch ($data['action']) {
            case 'add_task':
                $result = $controller->handleAddTask($data);
                break;
                
            case 'toggle_task':
                $result = $controller->handleToggleTask($data['id']);
                break;
                
            case 'delete_task':
                $result = $controller->handleDeleteTask($data['id']);
                break;
                
            case 'add_mood':
                $container['storage']->saveMoodEntry(date('Y-m-d'), (int)$data['value']);
                $result = ['success' => true];
                break;
                
            default:
                $result = ['success' => false, 'error' => 'Unknown action'];
        }
        
        return json_encode($result, JSON_THROW_ON_ERROR);
        
    } catch (Throwable $e) {
        error_log('API Error: ' . $e->getMessage());
        return json_encode([
            'success' => false,
            'error' => 'Server error occurred'
        ], JSON_THROW_ON_ERROR);
    }
}

function renderDashboard(array $container): void 
{
    $storage = $container['storage'];
    $analyticsService = $container['analyticsService'];
    
    $tasks = $storage->getTasks();
    $progress = $storage->getProgress();
    $moodData = $storage->getMoodData();
    
    // EMERGENCY FIX: Проверяем и исправляем некорректный прогресс
    if (isset($progress['points']) && $progress['points'] > 10000) {
        $progress = ['points' => 0, 'level' => 1, 'achievements' => []];
        $storage->saveProgress($progress);
    }
    
    // Calculate daily progress
    $completedCount = count(array_filter($tasks, fn($t) => $t['completed'] ?? false));
    $dailyProgress = count($tasks) > 0 ? ($completedCount / count($tasks)) * 100 : 0;
    
    // Analytics data
    $dailyStats = $analyticsService->getDailyCompletionStats($tasks);
    $weekdayStats = $analyticsService->getProductivityByWeekday($tasks);
    $categoryStats = $analyticsService->getCategoryStats($tasks);
    $recentDates = $storage->getRecentDatesWithTasks(10);
    
    // Weekly progress
    $weeklyProgress = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayTasks = array_filter($tasks, function($task) use ($date) {
            if ($task['completed'] && isset($task['completed_at'])) {
                $taskDate = date('Y-m-d', strtotime($task['completed_at']));
                return $taskDate === $date;
            }
            return false;
        });
        $completed = count($dayTasks);
        $total = count($tasks);
        $progress = $total > 0 ? ($completed / $total) * 100 : 0;
        
        $weeklyProgress[] = [
            'date' => $date,
            'day' => date('D', strtotime($date)),
            'progress' => $progress,
            'completed' => $completed,
            'total' => $total
        ];
    }
    
    // Extract variables for template
    extract([
        'tasks' => $tasks,
        'progress' => $progress,
        'moodData' => $moodData,
        'dailyProgress' => $dailyProgress,
        'dailyStats' => $dailyStats,
        'weekdayStats' => $weekdayStats,
        'categoryStats' => $categoryStats,
        'recentDates' => $recentDates,
        'weeklyProgress' => $weeklyProgress
    ], EXTR_SKIP);
    
    include __DIR__ . '/../templates/dashboard.php';
}