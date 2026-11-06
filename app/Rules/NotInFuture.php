<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * A hearing (or stage) date can't be in the future — compared with "today" in Kosovo time.
 */
class NotInFuture implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        try {
            $date = Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return;
        }

        $today = Carbon::now((string) config('courtwatch.display_timezone'))->startOfDay();

        if ($date->toDateString() > $today->toDateString()) {
            $fail(__('validation.not_in_future'));
        }
    }
}
