<?php

namespace App\Enum;

enum OpportunityStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Contacted => 'Contacté',
            self::Negotiation => 'Négociation',
            self::Won => 'Gagné',
            self::Lost => 'Perdu',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Won, self::Lost], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'bg-surface-container-high text-on-surface-variant',
            self::Contacted => 'bg-secondary-container text-on-secondary-container',
            self::Negotiation => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
            self::Won => 'bg-primary-fixed text-on-primary-fixed',
            self::Lost => 'bg-error-container text-on-error-container',
        };
    }
}