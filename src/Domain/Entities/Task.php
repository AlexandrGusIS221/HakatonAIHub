<?php
// app/src/Domain/Entities/Task.php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\TaskPriority;
use DateTimeImmutable;

class Task 
{
    public function __construct(
        private string $id,
        private string $title,
        private string $category,
        private TaskPriority $priority,
        private int $basePoints,
        private bool $completed = false,
        private ?DateTimeImmutable $completedAt = null,
        private DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $dueDate = null
    ) {}

    // Getters
    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getCategory(): string { return $this->category; }
    public function getPriority(): TaskPriority { return $this->priority; }
    public function isCompleted(): bool { return $this->completed; }
    public function getBasePoints(): int { return $this->basePoints; }
    public function getCompletedAt(): ?DateTimeImmutable { return $this->completedAt; }
    public function getDueDate(): ?DateTimeImmutable { return $this->dueDate; }
    
    public function getCalculatedPoints(): int
    {
        return (int) ($this->basePoints * $this->priority->getPointsMultiplier());
    }

    public function complete(): void
    {
        $this->completed = true;
        $this->completedAt = new DateTimeImmutable();
    }

    public function reopen(): void
    {
        $this->completed = false;
        $this->completedAt = null;
    }

    public function getCompletionDate(): ?string
    {
        return $this->completedAt ? $this->completedAt->format('Y-m-d') : null;
    }

    public function isOverdue(): bool
    {
        return !$this->completed && $this->dueDate && $this->dueDate < new DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'priority' => $this->priority->value,
            'completed' => $this->completed,
            'points' => $this->getCalculatedPoints(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt ? $this->completedAt->format('Y-m-d H:i:s') : null,
            'due_date' => $this->dueDate ? $this->dueDate->format('Y-m-d') : null,
            'is_overdue' => $this->isOverdue()
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['title'],
            $data['category'],
            TaskPriority::from($data['priority']),
            $data['points'] ?? 10,
            $data['completed'] ?? false,
            isset($data['completed_at']) ? new DateTimeImmutable($data['completed_at']) : null,
            new DateTimeImmutable($data['created_at'] ?? 'now'),
            isset($data['due_date']) ? new DateTimeImmutable($data['due_date']) : null
        );
    }
}