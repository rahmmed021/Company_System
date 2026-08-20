<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function require(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = __('validation.required');
            }
        }
        return $errors;
    }

    public static function positive(mixed $value): bool
    {
        return is_numeric($value) && (float) $value >= 0;
    }

    public static function date(mixed $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value);
    }
}
