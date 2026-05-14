<?php
/**
 * ValidationHelper — Validação de dados de formulários.
 */

declare(strict_types=1);

class ValidationHelper
{
    private array $errors = [];
    private array $data   = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /** Valida campo obrigatório. */
    public function required(string $campo, string $label): static
    {
        if (empty(trim((string) ($this->data[$campo] ?? '')))) {
            $this->errors[$campo] = "{$label} é obrigatório.";
        }
        return $this;
    }

    /** Valida e-mail. */
    public function email(string $campo, string $label): static
    {
        $val = $this->data[$campo] ?? '';
        if (!empty($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$campo] = "{$label} deve ser um e-mail válido.";
        }
        return $this;
    }

    /** Valida tamanho mínimo. */
    public function min(string $campo, string $label, int $min): static
    {
        $val = $this->data[$campo] ?? '';
        if (!empty($val) && mb_strlen((string) $val) < $min) {
            $this->errors[$campo] = "{$label} deve ter pelo menos {$min} caracteres.";
        }
        return $this;
    }

    /** Valida tamanho máximo. */
    public function max(string $campo, string $label, int $max): static
    {
        $val = $this->data[$campo] ?? '';
        if (!empty($val) && mb_strlen((string) $val) > $max) {
            $this->errors[$campo] = "{$label} deve ter no máximo {$max} caracteres.";
        }
        return $this;
    }

    /** Valida valor numérico positivo. */
    public function positivo(string $campo, string $label): static
    {
        $val = $this->data[$campo] ?? 0;
        // Aceita formato brasileiro (1.234,56) e internacional (1234.56)
        $numerico = str_replace(['.', ','], ['', '.'], (string) $val);
        if (!is_numeric($numerico) || (float) $numerico <= 0) {
            $this->errors[$campo] = "{$label} deve ser um valor positivo.";
        }
        return $this;
    }

    /** Valida data no formato Y-m-d. */
    public function date(string $campo, string $label): static
    {
        $val = $this->data[$campo] ?? '';
        if (!empty($val)) {
            $d = DateTimeImmutable::createFromFormat('Y-m-d', $val);
            if (!$d || $d->format('Y-m-d') !== $val) {
                $this->errors[$campo] = "{$label} deve ser uma data válida.";
            }
        }
        return $this;
    }

    /** Verifica se passou em todas as validações. */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /** Retorna todos os erros. */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Retorna o primeiro erro de um campo. */
    public function error(string $campo): string
    {
        return $this->errors[$campo] ?? '';
    }

    /**
     * Converte valor monetário em formato brasileiro para float.
     * Ex: "1.234,56" → 1234.56
     */
    public static function parseMoeda(string $valor): float
    {
        $valor = str_replace(['R$', ' '], '', $valor);
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
        return round((float) $valor, 2);
    }
}
