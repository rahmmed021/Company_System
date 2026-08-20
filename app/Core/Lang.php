<?php
declare(strict_types=1);

namespace App\Core;

final class Lang
{
    private static array $lines = [];

    public static function setLocale(string $locale): void
    {
        $locale = in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
        Session::put('lang', $locale);
        self::$lines = require base_path('languages/' . $locale . '.php');
    }

    public static function locale(): string
    {
        return (string) Session::get('lang', env('DEFAULT_LANGUAGE', 'en'));
    }

    public static function load(): void
    {
        self::setLocale(self::locale());
    }

    public static function get(string $key, array $replace = []): string
    {
        if (!self::$lines) {
            self::load();
        }

        $value = self::$lines;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return env('APP_DEBUG', false) ? '[' . $key . ']' : '';
            }
            $value = $value[$segment];
        }

        $line = (string) $value;
        foreach ($replace as $name => $replacement) {
            $line = str_replace(':' . $name, (string) $replacement, $line);
        }
        return $line;
    }
}
