<?php
declare(strict_types=1);

/** @var array $tasks */
/** @var array $progress */
/** @var array $moodData */
/** @var float $dailyProgress */
/** @var array $dailyStats */
/** @var array $recentDates */
/** @var array $weekdayStats */
/** @var array $categoryStats */
/** @var array $weeklyProgress */

// Защита от неинициализированных переменных
$progress = $progress ?? ['points' => 0, 'level' => 1, 'achievements' => []];
$tasks = $tasks ?? [];
$moodData = $moodData ?? [];
$dailyProgress = $dailyProgress ?? 0;
$dailyStats = $dailyStats ?? [];
$recentDates = $recentDates ?? [];
$weekdayStats = $weekdayStats ?? [];
$categoryStats = $categoryStats ?? [];
$weeklyProgress = $weeklyProgress ?? [];

// Убедимся, что progress - массив
if (!is_array($progress)) {
    $progress = ['points' => 0, 'level' => 1, 'achievements' => []];
}
?>
<!DOCTYPE html>
<html lang="ru" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .priority-indicator { width: 4px; }
        .task-card { transition: all 0.3s ease; }
        .task-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .completed { opacity: 0.7; background-color: #f8f9fa; }
        .mood-option { cursor: pointer; transition: transform 0.2s; }
        .mood-option:hover { transform: scale(1.1); }
        .level-progress { height: 8px; }
        .achievement-badge { font-size: 0.8rem; }
        .date-badge { cursor: pointer; }
        .overdue-task { border-left: 4px solid #dc3545 !important; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .nav-tabs .nav-link.active { font-weight: 600; }
        .productivity-bar { height: 20px; }
        .calendar-day { cursor: pointer; transition: all 0.2s; }
        .calendar-day:hover { background-color: #e9ecef; }
        .calendar-day.has-tasks { background-color: #d1ecf1; }
        .calendar-day.completed { background-color: #d4edda; }
        .empty-state { color: #6c757d; text-align: center; padding: 2rem; }
    .days-scroll-container {
        display: flex;
        overflow-x: auto;
        gap: 10px;
        padding-bottom: 10px;
    }

    .day-selector {
        transition: all 0.3s ease;
        min-width: 150px;
    }

    .day-selector:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .day-selector.active {
        border-color: #0d6efd !important;
        background-color: #f8f9fa !important;
    }

    /* Стили для горизонтального скролла */
    .d-flex.overflow-auto::-webkit-scrollbar {
        height: 8px;
    }

    .d-flex.overflow-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .d-flex.overflow-auto::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .d-flex.overflow-auto::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">
                <i class="bi bi-check-circle-fill me-2"></i>
                TaskMaster Pro
            </span>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Stats Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title mb-1">Сегодняшний прогресс</h4>
                                <p class="text-muted mb-0"><?= date('d M Y') ?></p>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-0"><?= round($dailyProgress) ?>%</h3>
                                <small class="text-muted">завершено</small>
                            </div>
                        </div>
                        <div class="progress level-progress mt-3">
                            <div class="progress-bar bg-primary" 
                                 style="width: <?= $dailyProgress ?>%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <h5>Общая статистика</h5>
                        <div class="row mt-3">
                            <div class="col-6">
                                <h4 class="mb-0"><?= count($tasks) ?></h4>
                                <small>Всего задач</small>
                            </div>
                            <div class="col-6">
                                <h4 class="mb-0"><?= count(array_filter($tasks, fn($t) => $t['completed'] ?? false)) ?></h4>
                                <small>Выполнено</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Навигация по вкладкам -->
        <ul class="nav nav-tabs mb-4" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tasks-tab" data-bs-toggle="tab" 
                        data-bs-target="#tasks" type="button" role="tab">
                    <i class="bi bi-list-check me-1"></i>Текущие задачи
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" 
                        data-bs-target="#history" type="button" role="tab">
                    <i class="bi bi-calendar-range me-1"></i>История
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" 
                        data-bs-target="#analytics" type="button" role="tab">
                    <i class="bi bi-graph-up me-1"></i>Аналитика
                </button>
            </li>
        </ul>

        <div class="tab-content" id="mainTabsContent">
            <!-- Вкладка текущих задач -->
            <div class="tab-pane fade show active" id="tasks" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Add Task Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-plus-circle me-2"></i>Новая задача
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="taskForm" class="row g-3">
                                    <div class="col-md-12">
                                        <input type="text" name="title" class="form-control" 
                                               placeholder="Описание задачи" required>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="category" class="form-select" required>
                                            <option value="work">💼 Работа</option>
                                            <option value="personal">🏠 Личное</option>
                                            <option value="health">💪 Здоровье</option>
                                            <option value="learning">🎓 Обучение</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="priority" class="form-select" required>
                                            <option value="low">🟢 Низкий</option>
                                            <option value="medium">🟡 Средний</option>
                                            <option value="high">🔴 Высокий</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="points" class="form-control" 
                                               placeholder="Очки" min="5" max="50" value="10" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" name="due_date" class="form-control" 
                                               min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-primary w-100" title="Добавить задачу">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Tasks List -->
                        <div id="tasksContainer">
                            <?php if (empty($tasks)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox display-4"></i>
                                    <h5>Нет задач</h5>
                                    <p class="text-muted">Добавьте первую задачу используя форму выше</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tasks as $task): ?>
                                    <?php include __DIR__ . '/components/task_card.php'; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Mood Tracker -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-emoji-smile me-2"></i>Мое настроение
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <?php foreach ([1 => '😢', 2 => '😕', 3 => '😐', 4 => '😊', 5 => '😄'] as $value => $emoji): ?>
                                        <span class="mood-option fs-3" data-value="<?= $value ?>">
                                            <?= $emoji ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (!empty($moodData)): ?>
                                    <canvas id="moodChart" height="150"></canvas>
                                <?php else: ?>
                                    <p class="text-muted text-center">Отслеживайте настроение каждый день</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-clock-history me-2"></i>Недавние дни
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentDates)): ?>
                                    <p class="text-muted text-center">Нет активности</p>
                                <?php else: ?>
                                    <?php foreach ($recentDates as $date): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="date-badge" data-date="<?= $date ?>">
                                                <?= date('d M', strtotime($date)) ?>
                                            </span>
                                            <span class="badge bg-primary">
                                                <?= count(array_filter($tasks, function($task) use ($date) {
                                                    $taskDate = ($task['completed'] ?? false) && isset($task['completed_at']) 
                                                        ? date('Y-m-d', strtotime($task['completed_at']))
                                                        : date('Y-m-d', strtotime($task['created_at'] ?? 'now'));
                                                    return $taskDate === $date && ($task['completed'] ?? false);
                                                })) ?> выполнено
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Weekly Progress -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-calendar-week me-2"></i>Прогресс за неделю
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($weeklyProgress as $day): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small><?= $day['day'] ?? '???' ?></small>
                                        <div class="d-flex align-items-center">
                                            <small class="me-2"><?= round($day['progress'] ?? 0) ?>%</small>
                                            <div class="progress" style="width: 60px; height: 6px;">
                                                <div class="progress-bar bg-<?= ($day['progress'] ?? 0) > 70 ? 'success' : (($day['progress'] ?? 0) > 30 ? 'warning' : 'danger') ?>" 
                                                     style="width: <?= $day['progress'] ?? 0 ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Вкладка истории -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="row">
                    <div class="col-12">
                        <!-- Горизонтальный скролл дней -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Выберите день для просмотра</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex overflow-auto pb-2" style="gap: 10px;" id="daysScrollContainer">
                                    <?php if (empty($dailyStats)): ?>
                                        <p class="text-muted text-center w-100">Нет данных по истории</p>
                                    <?php else: ?>
                                        <?php foreach ($dailyStats as $index => $day): ?>
                                            <div class="card day-selector <?= $index === 0 ? 'border-primary' : '' ?>" 
                                                style="min-width: 150px; cursor: pointer;" 
                                                data-date="<?= $day['date'] ?>">
                                                <div class="card-body text-center p-3">
                                                    <h6 class="card-title mb-1"><?= date('d M', strtotime($day['date'])) ?></h6>
                                                    <p class="card-text mb-1 small"><?= date('Y', strtotime($day['date'])) ?></p>
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <span class="badge bg-success"><?= $day['completed_tasks'] ?? 0 ?> ✔</span>
                                                        <span class="badge bg-primary"><?= $day['total_points'] ?? 0 ?> ⭐</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Задачи выбранного дня -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0" id="dayTasksTitle">
                                    <?= !empty($dailyStats) ? 'Задачи на ' . date('d M Y', strtotime($dailyStats[0]['date'] ?? 'now')) : 'Задачи' ?>
                                </h5>
                                <div id="dayStats" class="text-muted small">
                                    <?php if (!empty($dailyStats)): ?>
                                        <?= $dailyStats[0]['completed_tasks'] ?? 0 ?> задач • <?= $dailyStats[0]['total_points'] ?? 0 ?> очков
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body" id="dayTasksContainer">
                                <?php if (!empty($dailyStats)): ?>
                                    <?php 
                                    $firstDayTasks = array_filter($tasks, function($task) use ($dailyStats) {
                                        if ($task['completed'] && isset($task['completed_at'])) {
                                            $taskDate = date('Y-m-d', strtotime($task['completed_at']));
                                            return $taskDate === ($dailyStats[0]['date'] ?? '');
                                        }
                                        return false;
                                    });
                                    ?>
                                    <?php if (!empty($firstDayTasks)): ?>
                                        <?php foreach ($firstDayTasks as $task): ?>
                                            <?php include __DIR__ . '/components/task_card.php'; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted text-center">Нет выполненных задач на этот день</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center">Выберите день для просмотра задач</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Вкладка аналитики -->
            <div class="tab-pane fade" id="analytics" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Продуктивность по дням недели</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="weekdayChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Статистика по категориям</h5>
                            </div>
                            <div class="card-body">
                                <div id="categoryStats">
                                    <?php if (empty($categoryStats)): ?>
                                        <p class="text-muted text-center">Нет данных по категориям</p>
                                    <?php else: ?>
                                        <?php foreach ($categoryStats as $category => $stats): ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><?= match($category) {
                                                        'work' => '💼 Работа',
                                                        'personal' => '🏠 Личное', 
                                                        'health' => '💪 Здоровье',
                                                        'learning' => '🎓 Обучение',
                                                        default => '📌 ' . ucfirst($category)
                                                    } ?></span>
                                                    <span><?= $stats['completed'] ?? 0 ?>/<?= $stats['total'] ?? 0 ?></span>
                                                </div>
                                                <div class="progress productivity-bar">
                                                    <div class="progress-bar" 
                                                         style="width: <?= (($stats['completed'] ?? 0) / max(1, ($stats['total'] ?? 1))) * 100 ?>%">
                                                    </div>
                                                </div>
                                                <small class="text-muted"><?= $stats['points'] ?? 0 ?> очков</small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для просмотра задач дня -->
    <div class="modal fade" id="dayTasksModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dayTasksModalLabel">Задачи дня</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="dayTasksModalBody">
                    <!-- Содержимое будет загружено через AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // API integration
        const API = {
            async request(action, data = {}) {
                const formData = new FormData();
                formData.append('action', action);
                Object.entries(data).forEach(([key, value]) => formData.append(key, value));
                
                const response = await fetch('', { method: 'POST', body: formData });
                return await response.json();
            }
        };

        // Task management
        document.getElementById('taskForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const result = await API.request('add_task', Object.fromEntries(formData));
            
            if (result.success) {
                updateProgress(result.progress);
                location.reload(); // Перезагружаем для обновления списка задач
            } else {
                alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
            }
        });

        // Mood tracking
        document.querySelectorAll('.mood-option').forEach(option => {
            option.addEventListener('click', async () => {
                const value = option.dataset.value;
                await API.request('add_mood', { value });
                location.reload();
            });
        });

        // Task actions delegation
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.toggle-task')) {
                const button = e.target.closest('.toggle-task');
                const id = button.dataset.id;
                const result = await API.request('toggle_task', { id });
                
                if (result.success) {
                    updateProgress(result.progress);
                    // Обновляем интерфейс без перезагрузки
                    const taskCard = button.closest('.task-card');
                    const isCompleted = taskCard.classList.contains('completed');
                    
                    if (!isCompleted) {
                        // Задача выполнена - обновляем интерфейс
                        taskCard.classList.add('completed');
                        const icon = button.querySelector('i');
                        icon.className = 'bi bi-arrow-counterclockwise';
                        button.classList.remove('btn-outline-success');
                        button.classList.add('btn-outline-warning');
                    } else {
                        // Задача отменена
                        taskCard.classList.remove('completed');
                        const icon = button.querySelector('i');
                        icon.className = 'bi bi-check-lg';
                        button.classList.remove('btn-outline-warning');
                        button.classList.add('btn-outline-success');
                    }
                } else {
                    alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
                }
            }

            if (e.target.closest('.delete-task')) {
                const button = e.target.closest('.delete-task');
                const id = button.dataset.id;
                if (confirm('Удалить задачу?')) {
                    const result = await API.request('delete_task', { id });
                    if (result.success) {
                        updateProgress(result.progress);
                        button.closest('.task-card').remove();
                        // Обновляем прогресс бар
                        updateDailyProgress();
                    } else {
                        alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
                    }
                }
            }
        });

        // Функция обновления прогресса в заголовке
        function updateProgress(progress) {
            if (progress && progress.points !== undefined && progress.level !== undefined) {
                // Защита от некорректных значений
                const points = Math.max(0, parseInt(progress.points) || 0);
                const level = Math.max(1, parseInt(progress.level) || 1);
                
                // Обновляем навигационную панель
                const navProgress = document.querySelector('.navbar-text');
                if (navProgress) {
                    navProgress.innerHTML = `<i class="bi bi-star-fill text-warning me-1"></i>
                                            Уровень ${level} • ${points} очков`;
                }
                
                // Обновляем карточку прогресса
                const progressCard = document.querySelector('.card.bg-primary.text-white .display-4');
                if (progressCard) {
                    progressCard.textContent = points;
                }
                
                const levelCard = document.querySelector('.card.bg-primary.text-white h5');
                if (levelCard) {
                    levelCard.textContent = `Уровень ${level}`;
                }
                
                console.log('Прогресс обновлен:', { points, level });
            }
        }

        // Task actions delegation
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.toggle-task')) {
                const button = e.target.closest('.toggle-task');
                const id = button.dataset.id;
                const result = await API.request('toggle_task', { id });
                
                if (result.success) {
                    if (result.progress) {
                        updateProgress(result.progress);
                    }
                    
                    // Обновляем интерфейс задачи на основе данных с сервера
                    const taskCard = button.closest('.task-card');
                    const isCompleted = result.task?.completed || false;
                    
                    if (isCompleted) {
                        // Задача выполнена
                        taskCard.classList.add('completed');
                        const icon = button.querySelector('i');
                        icon.className = 'bi bi-arrow-counterclockwise';
                        button.classList.remove('btn-outline-success');
                        button.classList.add('btn-outline-warning');
                        button.title = 'Отменить выполнение';
                        
                        // Обновляем дату выполнения если есть
                        if (result.task?.completed_at) {
                            const completedDateElement = taskCard.querySelector('.completed-date');
                            if (!completedDateElement) {
                                // Создаем элемент для отображения даты выполнения
                                const datesContainer = taskCard.querySelector('.d-flex.align-items-center.mb-2');
                                if (datesContainer) {
                                    const dateElement = document.createElement('small');
                                    dateElement.className = 'text-muted mb-1 ms-2 completed-date';
                                    dateElement.innerHTML = `<i class="bi bi-check-circle me-1"></i>${new Date(result.task.completed_at).toLocaleDateString('ru-RU')}`;
                                    datesContainer.appendChild(dateElement);
                                }
                            }
                        }
                    } else {
                        // Задача не выполнена
                        taskCard.classList.remove('completed');
                        const icon = button.querySelector('i');
                        icon.className = 'bi bi-check-lg';
                        button.classList.remove('btn-outline-warning');
                        button.classList.add('btn-outline-success');
                        button.title = 'Отметить выполненной';
                        
                        // Удаляем дату выполнения
                        const completedDateElement = taskCard.querySelector('.completed-date');
                        if (completedDateElement) {
                            completedDateElement.remove();
                        }
                    }
                    
                    // Обновляем дневной прогресс
                    updateDailyProgress();
                } else {
                    alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
                }
            }

            if (e.target.closest('.delete-task')) {
                const button = e.target.closest('.delete-task');
                const id = button.dataset.id;
                if (confirm('Удалить задачу?')) {
                    const result = await API.request('delete_task', { id });
                    if (result.success) {
                        if (result.progress) {
                            updateProgress(result.progress);
                        }
                        button.closest('.task-card').remove();
                        updateDailyProgress();
                    } else {
                        alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
                    }
                }
            }
        });

        // Функция обновления прогресса в заголовке
        function updateProgress(progress) {
            if (progress && progress.points !== undefined && progress.level !== undefined) {
                // Защита от некорректных значений
                const points = Math.max(0, parseInt(progress.points) || 0);
                const level = Math.max(1, parseInt(progress.level) || 1);
                
                // Обновляем навигационную панель
                const navProgress = document.querySelector('.navbar-text');
                if (navProgress) {
                    navProgress.innerHTML = `<i class="bi bi-star-fill text-warning me-1"></i>
                                            Уровень ${level} • ${points} очков`;
                }
                
                // Обновляем карточку прогресса
                const progressCard = document.querySelector('.stats-card.text-white');
                if (progressCard) {
                    const pointsElement = progressCard.querySelector('.display-4');
                    const levelElement = progressCard.querySelector('h5');
                    if (pointsElement) pointsElement.textContent = points;
                    if (levelElement) levelElement.textContent = `Уровень ${level}`;
                }
                
                console.log('Прогресс обновлен:', { points, level });
            }
        }

        // Функция обновления дневного прогресса
        function updateDailyProgress() {
            const totalTasks = document.querySelectorAll('#tasksContainer .task-card').length;
            const completedTasks = document.querySelectorAll('#tasksContainer .task-card.completed').length;
            const progress = totalTasks > 0 ? (completedTasks / totalTasks) * 100 : 0;
            
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }
            
            const progressText = document.querySelector('.text-end h3');
            if (progressText) {
                progressText.textContent = `${Math.round(progress)}%`;
            }
        }

        // История: загрузка задач выбранного дня
        document.querySelectorAll('.day-selector').forEach(selector => {
            selector.addEventListener('click', async function() {
                // Убираем выделение у всех дней
                document.querySelectorAll('.day-selector').forEach(s => {
                    s.classList.remove('border-primary', 'bg-light');
                });
                
                // Выделяем выбранный день
                this.classList.add('border-primary', 'bg-light');
                
                const date = this.dataset.date;
                await loadDayTasks(date);
            });
        });

        // Загрузка задач дня для истории
        async function loadDayTasks(date) {
            try {
                const response = await fetch(`?action=get_day_tasks&date=${date}`);
                if (!response.ok) {
                    throw new Error('Ошибка загрузки данных');
                }
                const html = await response.text();
                document.getElementById('dayTasksContainer').innerHTML = html;
                document.getElementById('dayTasksTitle').textContent = `Задачи на ${date}`;
                
                // Обновляем статистику дня
                const selectedDay = document.querySelector(`.day-selector[data-date="${date}"]`);
                if (selectedDay) {
                    const completedTasks = selectedDay.querySelector('.badge.bg-success').textContent;
                    const totalPoints = selectedDay.querySelector('.badge.bg-primary').textContent;
                    document.getElementById('dayStats').textContent = 
                        `${completedTasks} задач • ${totalPoints} очков`;
                }
                
            } catch (error) {
                console.error('Ошибка загрузки задач:', error);
                document.getElementById('dayTasksContainer').innerHTML = 
                    '<p class="text-danger text-center">Ошибка загрузки задач</p>';
            }
        }

        // Mood chart
        <?php if (!empty($moodData)): ?>
        new Chart(document.getElementById('moodChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($moodData)) ?>,
                datasets: [{
                    label: 'Настроение',
                    data: <?= json_encode(array_values($moodData)) ?>,
                    borderColor: '#0d6efd',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                scales: {
                    y: {
                        min: 1,
                        max: 5,
                        ticks: {
                            callback: value => ['', '😢', '😕', '😐', '😊', '😄'][value]
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Weekday productivity chart
        <?php if (!empty($weekdayStats)): ?>
        new Chart(document.getElementById('weekdayChart'), {
            type: 'bar',
            data: {
                labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                datasets: [{
                    label: 'Выполнено задач',
                    data: <?= json_encode(array_values($weekdayStats)) ?>,
                    backgroundColor: '#0d6efd'
                }]
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>