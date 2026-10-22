<?php

namespace App\Enum;

enum TaskPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Normal => 'Normale',
            self::High => 'Haute',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'bg-surface-container-high text-on-surface-variant',
            self::Normal => 'bg-secondary-container text-on-secondary-container',
            self::High => 'bg-error-container text-on-error-container',
        };
    }
}