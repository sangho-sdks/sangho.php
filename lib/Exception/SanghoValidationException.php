<?php

declare(strict_types=1);

namespace Sangho\Exception;

class SanghoValidationException extends SanghoException
{
    public function __construct(array $raw = [])
    {
        $detail = $raw['detail'] ?? $raw['errors'] ?? [];
        $fields = is_array($detail) ? $detail : [];

        $parts = [];
        foreach ($fields as $field => $errors) {
            $errors = is_array($errors) ? $errors : [$errors];
            $parts[] = "{$field}: " . implode(', ', $errors);
        }
        $summary = implode(' | ', $parts);
        $fallback = is_string($raw['message'] ?? null) ? $raw['message'] : 'Validation error';
        $message = $summary !== '' ? $summary : $fallback;

        parent::__construct($message, 'validation_error', 422, $raw, type: 'VALIDATION_ERROR');
    }

    public function getFieldErrors(): array
    {
        $detail = $this->raw['detail'] ?? $this->raw['errors'] ?? [];
        return is_array($detail) ? $detail : [];
    }

    public function getParam(): ?string
    {
        $param = $this->raw['param'] ?? null;
        return is_string($param) ? $param : null;
    }
}
