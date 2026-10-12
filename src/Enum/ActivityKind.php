<?php

namespace App\Enum;

enum ActivityKind: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case LinkedIn = 'linkedin';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Appel',
            self::Email => 'E-mail',
            self::Meeting => 'Réunion',
            self::LinkedIn => 'Message LinkedIn',
            self::Note => 'Note',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => 'call',
            self::Email => 'mail',
            self::Meeting => 'groups',
            self::LinkedIn => 'forum',
            self::Note => 'note_alt',
        };
    }
}