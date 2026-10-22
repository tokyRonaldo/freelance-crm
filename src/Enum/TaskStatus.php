<?php

namespace App\Enum;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'À faire',
            self::InProgress => 'En cours',
            self::Done => 'Terminée',
        };
    }
}