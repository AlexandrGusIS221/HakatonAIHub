<?php
// src/Domain/Services/AnalyticsService.php

declare(strict_types=1);

namespace App\Domain\Services;

class AnalyticsService
{
    public function getDailyCompletionStats(array $tasks): array
    {
        $stats = [];
        
        foreach ($tasks as $task) {
            if ($task['completed'] && isset($task['completed_at'])) {
                $completionDate = date('Y-m-d', strtotime($task['completed_at']));
                
                if (!isset($stats[$completionDate])) {
                    $stats[$completionDate] = [
                        'date' => $completionDate,
                        'completed_tasks' => 0,
                        'total_points' => 0,
                        'tasks' => []
                    ];
                }
                
                $stats[$completionDate]['completed_tasks']++;
                $stats[$completionDate]['total_points'] += $task['points'] ?? 0;
                $stats[$completionDate]['tasks'][] = $task;
            }
        }
        
        // Сортируем по дате (новые сверху)
        krsort($stats);
        
        return array_values($stats);
    }

    public function getProductivityByWeekday(array $tasks): array
    {
        $weekdays = [
            'Monday' => 0, 
            'Tuesday' => 0, 
            'Wednesday' => 0, 
            'Thursday' => 0, 
            'Friday' => 0, 
            'Saturday' => 0, 
            'Sunday' => 0
        ];
        
        foreach ($tasks as $task) {
            if ($task['completed'] && isset($task['completed_at'])) {
                $weekday = date('l', strtotime($task['completed_at']));
                $weekdays[$weekday]++;
            }
        }
        
        return $weekdays;
    }

    public function getCategoryStats(array $tasks): array
    {
        $stats = [];
        
        foreach ($tasks as $task) {
            $category = $task['category'];
            
            if (!isset($stats[$category])) {
                $stats[$category] = [
                    'total' => 0,
                    'completed' => 0,
                    'points' => 0
                ];
            }
            
            $stats[$category]['total']++;
            $stats[$category]['points'] += $task['points'];
            
            if ($task['completed']) {
                $stats[$category]['completed']++;
            }
        }
        
        return $stats;
    }

    public function getWeeklyProgress(array $tasks): array
    {
        $weekData = [];
        $currentWeek = date('W');
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $weekDay = date('D', strtotime($date));
            
            $dayTasks = array_filter($tasks, function($task) use ($date) {
                $taskDate = $task['completed'] && $task['completed_at'] 
                    ? date('Y-m-d', strtotime($task['completed_at']))
                    : date('Y-m-d', strtotime($task['created_at']));
                return $taskDate === $date;
            });
            
            $completed = count(array_filter($dayTasks, fn($t) => $t['completed']));
            $total = count($dayTasks);
            $progress = $total > 0 ? ($completed / $total) * 100 : 0;
            
            $weekData[] = [
                'date' => $date,
                'day' => $weekDay,
                'progress' => $progress,
                'completed' => $completed,
                'total' => $total
            ];
        }
        
        return $weekData;
    }
}