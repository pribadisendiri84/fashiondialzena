<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\Ability;
use Illuminate\Http\Request;

trait ResolvesFinancialInput
{
    protected function costFromRequest(Request $request, int $fallback, string $field = 'cost_price'): int
    {
        if ($request->user()->cannot(Ability::ViewFinancials->value)) {
            return $fallback;
        }

        $raw = $request->input($field);
        if ($raw === null || $raw === '') {
            return $fallback;
        }

        return (int) preg_replace('/\D+/', '', (string) $raw);
    }
}
