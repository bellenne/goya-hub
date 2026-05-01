<?php

namespace App\Services\Dice;

class DiceRandomResult
{
    public function __construct(
        public readonly array $values,
        public readonly string $source,
        public readonly ?string $error = null,
    ) {}
}
