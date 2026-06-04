<?php

namespace App\Enums;

enum ApprovalLevel: int
{
    case Manager = 1;
    case HR = 2;
    case Finance = 3;

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'Manager',
            self::HR      => 'HR',
            self::Finance => 'Finance',
        };
    }

    /**
     * Returns the next level in the chain, or null if this is the last.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Manager => self::HR,
            self::HR      => self::Finance,
            self::Finance => null,
        };
    }

    /**
     * First level in the approval chain.
     */
    public static function first(): self
    {
        return self::Manager;
    }
}
