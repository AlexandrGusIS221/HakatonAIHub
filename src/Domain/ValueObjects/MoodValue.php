<?php
// app/src/Domain/ValueObjects/MoodValue.php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

enum MoodValue: int 
{
    case TERRIBLE = 1;
    case BAD = 2;
    case NEUTRAL = 3;
    case GOOD = 4;
    case EXCELLENT = 5;

    public function getEmoji(): string
    {
        return match($this) {
            self::TERRIBLE => '😢',
            self::BAD => '😕',
            self::NEUTRAL => '😐',
            self::GOOD => '😊',
            self::EXCELLENT => '😄'
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::TERRIBLE => 'danger',
            self::BAD => 'warning',
            self::NEUTRAL => 'secondary',
            self::GOOD => 'info',
            self::EXCELLENT => 'success'
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::TERRIBLE => 'Ужасно',
            self::BAD => 'Плохо',
            self::NEUTRAL => 'Нормально',
            self::GOOD => 'Хорошо',
            self::EXCELLENT => 'Отлично'
        };
    }
}