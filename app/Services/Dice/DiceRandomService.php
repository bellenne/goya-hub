<?php

namespace App\Services\Dice;

use App\Enums\DiceType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DiceRandomService
{
    public function roll(DiceType $diceType, int $diceCount): DiceRandomResult
    {
        $apiKey = config('services.random_org.api_key');

        if (! $apiKey) {
            return $this->fallback($diceType, $diceCount, 'RANDOM.ORG API key is not configured.');
        }

        try {
            $response = Http::timeout((int) config('services.random_org.timeout', 3))
                ->acceptJson()
                ->asJson()
                ->post(config('services.random_org.endpoint'), [
                    'jsonrpc' => '2.0',
                    'method' => 'generateIntegers',
                    'params' => [
                        'apiKey' => $apiKey,
                        'n' => $diceCount,
                        'min' => 1,
                        'max' => $diceType->sides(),
                        'replacement' => true,
                    ],
                    'id' => sprintf('dice-%s-%s', now()->timestamp, bin2hex(random_bytes(4))),
                ]);

            if (! $response->successful()) {
                return $this->fallback($diceType, $diceCount, "RANDOM.ORG HTTP {$response->status()}.");
            }

            $payload = $response->json();

            if (isset($payload['error'])) {
                $message = $payload['error']['message'] ?? 'RANDOM.ORG API error.';

                return $this->fallback($diceType, $diceCount, $message);
            }

            $values = $payload['result']['random']['data'] ?? null;

            if (! is_array($values) || count($values) !== $diceCount) {
                return $this->fallback($diceType, $diceCount, 'RANDOM.ORG returned an invalid dice payload.');
            }

            $values = array_map(fn ($value) => (int) $value, $values);

            if (! $this->valuesFitDice($values, $diceType)) {
                return $this->fallback($diceType, $diceCount, 'RANDOM.ORG returned values outside dice range.');
            }

            return new DiceRandomResult($values, 'random_org');
        } catch (Throwable $exception) {
            return $this->fallback($diceType, $diceCount, $exception->getMessage());
        }
    }

    protected function fallback(DiceType $diceType, int $diceCount, string $reason): DiceRandomResult
    {
        $mode = config('services.random_org.fallback', 'local');

        if ($mode !== 'local') {
            throw new RuntimeException("RANDOM.ORG dice roll failed: {$reason}");
        }

        Log::warning('RANDOM.ORG dice roll failed; using server random_int fallback.', [
            'reason' => $reason,
            'dice_type' => $diceType->value,
            'dice_count' => $diceCount,
        ]);

        $values = [];

        for ($index = 0; $index < $diceCount; $index++) {
            $values[] = random_int(1, $diceType->sides());
        }

        return new DiceRandomResult($values, 'server_random_int_fallback', $reason);
    }

    protected function valuesFitDice(array $values, DiceType $diceType): bool
    {
        foreach ($values as $value) {
            if ($value < 1 || $value > $diceType->sides()) {
                return false;
            }
        }

        return true;
    }
}
