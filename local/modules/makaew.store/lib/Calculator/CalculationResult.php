<?php

namespace Makaew\Store\Calculator;

/**
 * Результат расчёта калькулятора расхода.
 *
 * Иммутабельный value object с результатами вычисления.
 */
class CalculationResult
{
    private bool $success;
    private ?string $errorMessage;

    public function __construct(
        public readonly int $materialId = 0,
        public readonly string $materialName = '',
        public readonly float $area = 0,
        public readonly float $amount = 0,
        public readonly float $amountWithReserve = 0,
        public readonly string $unit = '',
        public readonly string $formula = '',
        public readonly array $params = [],
        ?string $error = null,
    ) {
        $this->success = ($error === null);
        $this->errorMessage = $error;
    }

    public static function error(string $message): self
    {
        return new self(error: $message);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Преобразовать в массив для API-ответа.
     */
    public function toArray(): array
    {
        if (!$this->success) {
            return [
                'success' => false,
                'error' => $this->errorMessage,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'material_id' => $this->materialId,
                'material_name' => $this->materialName,
                'area' => $this->area,
                'amount' => $this->amount,
                'amount_with_reserve' => $this->amountWithReserve,
                'unit' => $this->unit,
                'formula' => $this->formula,
                'params' => $this->params,
            ],
        ];
    }
}
