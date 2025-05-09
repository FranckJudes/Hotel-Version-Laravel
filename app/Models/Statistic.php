<?php

namespace App\Models;

use App\Enums\StatisticType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'date',
        'value',
        'value_string',
        'value_integer',
        'percentage_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => StatisticType::class,
        'date' => 'date',
        'value' => 'decimal:2',
        'value_integer' => 'integer',
        'percentage_value' => 'decimal:2',
    ];

    /**
     * Create a revenue statistic.
     *
     * @param \DateTime $date
     * @param float $amount
     * @return Statistic
     */
    public static function createRevenueStat($date, $amount)
    {
        return self::create([
            'type' => StatisticType::REVENUE,
            'date' => $date,
            'value' => $amount,
        ]);
    }

    /**
     * Create an occupancy rate statistic.
     *
     * @param \DateTime $date
     * @param float $rate
     * @return Statistic
     */
    public static function createOccupancyStat($date, $rate)
    {
        return self::create([
            'type' => StatisticType::OCCUPANCY_RATE,
            'date' => $date,
            'percentage_value' => $rate,
        ]);
    }
}
