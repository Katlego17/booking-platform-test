<?php

namespace App\Actions;

use App\Models\Booking;

class CheckBookingOverlap
{
    public static function handle(array $times, ?int $excludeId = null, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        $query = Booking::where('user_id', $userId)
            ->where(function ($q) use ($times) {
                $q->whereBetween('start_time', [$times['start_time'], $times['end_time']])
                  ->orWhereBetween('end_time', [$times['start_time'], $times['end_time']])
                  ->orWhere(function ($q2) use ($times) {
                      $q2->where('start_time', '<', $times['start_time'])
                         ->where('end_time', '>', $times['end_time']);
                  });
            });

        if ($excludeId)
        {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
