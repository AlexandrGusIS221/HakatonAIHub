<?php
declare(strict_types=1);

/** @var array $task */
?>
<div class="card task-card mb-3 <?= $task['completed'] ? 'completed' : '' ?> <?= $task['is_overdue'] ?? false ? 'overdue-task' : '' ?>">
    <div class="card-body">
        <div class="d-flex align-items-start">
            <div class="priority-indicator bg-<?= 
                match($task['priority']) {
                    'low' => 'success',
                    'medium' => 'warning', 
                    'high' => 'danger'
                }
            ?> me-3 rounded h-100"></div>
            
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h6 class="card-title mb-0"><?= htmlspecialchars($task['title']) ?></h6>
                    <?php if ($task['is_overdue'] ?? false): ?>
                        <span class="badge bg-danger ms-2">Просрочено</span>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex align-items-center mb-2 flex-wrap">
                    <span class="badge bg-secondary me-2 mb-1">
                        <?= match($task['category']) {
                            'work' => '💼 Работа',
                            'personal' => '🏠 Личное', 
                            'health' => '💪 Здоровье',
                            'learning' => '🎓 Обучение'
                        } ?>
                    </span>
                    <span class="badge bg-success me-2 mb-1">
                        +<?= $task['points'] ?> очков
                    </span>
                    <?php if ($task['due_date']): ?>
                        <small class="text-muted mb-1">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= date('d.m.Y', strtotime($task['due_date'])) ?>
                        </small>
                    <?php endif; ?>
                    <?php if ($task['completed'] && $task['completed_at']): ?>
                        <small class="text-muted mb-1 ms-2 completed-date">
                            <i class="bi bi-check-circle me-1"></i>
                            <?= date('d.m.Y H:i', strtotime($task['completed_at'])) ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="btn-group">
                <button class="btn btn-sm <?= $task['completed'] ? 'btn-outline-warning' : 'btn-outline-success' ?> toggle-task"
                        data-id="<?= $task['id'] ?>"
                        title="<?= $task['completed'] ? 'Отменить выполнение' : 'Отметить выполненной' ?>">
                    <i class="bi <?= $task['completed'] ? 'bi-arrow-counterclockwise' : 'bi-check-lg' ?>"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-task" 
                        data-id="<?= $task['id'] ?>"
                        title="Удалить задачу">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>