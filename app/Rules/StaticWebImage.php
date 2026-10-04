<?php

namespace App\Rules;

use App\Support\WebImageOptimizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class StaticWebImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        try {
            app(WebImageOptimizer::class)->inspect($value, $attribute);
        } catch (ValidationException $exception) {
            $fail($exception->errors()[$attribute][0]);
        }
    }
}
