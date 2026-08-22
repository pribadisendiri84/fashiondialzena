<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

trait ResolvesDateRange
{
    /**
     * @return array{from: string, to: string, periodLabel: string, isDefaultRange: bool}
     */
    protected function dateRange(Request $request): array
    {
        $today = now()->startOfDay();
        $defaultFrom = $today->copy()->startOfMonth();
        $from = $this->parseDate($request->input('from')) ?? $defaultFrom->copy();
        $to = $this->parseDate($request->input('to')) ?? $today->copy();

        if ($from->gt($today)) {
            $from = $today->copy();
        }
        if ($to->gt($today)) {
            $to = $today->copy();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'periodLabel' => $from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y'),
            'isDefaultRange' => $from->equalTo($defaultFrom) && $to->equalTo($today),
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
