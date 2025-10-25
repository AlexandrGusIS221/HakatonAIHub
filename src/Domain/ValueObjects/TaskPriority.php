<?php
// app/src/Domain/ValueObjects/TaskPriority.php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

enum TaskPriority: string 
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function getColor(): string
    {
        return match($this) {
            self::LOW => 'success',
            self::MEDIUM => 'warning', 
            self::HIGH => 'danger'
        };
    }

    public function getPointsMultiplier(): float
    {
        return match($this) {
            self::LOW => 1.0,
            self::MEDIUM => 1.5,
            self::HIGH => 2.0
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::LOW => 'Низкий',
            self::MEDIUM => 'Средний',
            self::HIGH => 'Высокий'
        };
    }
}