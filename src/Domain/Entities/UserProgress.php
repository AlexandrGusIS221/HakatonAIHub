<?php
// app/src/Domain/Entities/UserProgress.php

declare(strict_types=1);

namespace App\Domain\Entities;

class UserProgress 
{
    public function __construct(
        private int $points = 0,
        private int $level = 1,
        private array $achievements = []
    ) {}

    public function addPoints(int $points): void
    {
        $this->points += $points;
        $this->checkLevelUp();
    }

    public function deductPoints(int $points): void
    {
        $this->points = max(0, $this->points - $points);
    }

    private function checkLevelUp(): void
    {
        $requiredPoints = $this->level * 100;
        while ($this->points >= $requiredPoints) {
            $this->level++;
            $requiredPoints = $this->level * 100;
        }
    }

    public function addAchievement(string $achievement): void
    {
        if (!in_array($achievement, $this->achievements)) {
            $this->achievements[] = $achievement;
        }
    }

    // Getters
    public function getPoints(): int { return $this->points; }
    public function getLevel(): int { return $this->level; }
    public function getAchievements(): array { return $this->achievements; }
    
    public function getProgressToNextLevel(): float
    {
        $currentLevelPoints = ($this->level - 1) * 100;
        $nextLevelPoints = $this->level * 100;
        $pointsInCurrentLevel = $this->points - $currentLevelPoints;
        
        if ($nextLevelPoints - $currentLevelPoints === 0) {
            return 100;
        }
        
        return ($pointsInCurrentLevel / ($nextLevelPoints - $currentLevelPoints)) * 100;
    }

    public function toArray(): array
    {
        return [
            'points' => $this->points,
            'level' => $this->level,
            'achievements' => $this->achievements
        ];
    }
}