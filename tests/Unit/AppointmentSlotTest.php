<?php

namespace Tests\Unit;

use App\Support\AppointmentSlot;
use PHPUnit\Framework\TestCase;

class AppointmentSlotTest extends TestCase
{
    public function test_afternoon_window_runs_from_one_pm_until_nine_pm(): void
    {
        $times = AppointmentSlot::times(AppointmentSlot::AFTERNOON);

        $this->assertSame('13:00:00', $times->first());
        $this->assertSame('20:55:00', $times->last());
        $this->assertCount(96, $times);
    }

    public function test_afternoon_label_and_time_mapping_use_nine_pm(): void
    {
        $this->assertSame(
            'Afternoon (1:00 PM - 9:00 PM)',
            AppointmentSlot::all()[AppointmentSlot::AFTERNOON]['label'],
        );
        $this->assertSame(AppointmentSlot::AFTERNOON, AppointmentSlot::fromTime('20:00:00'));
        $this->assertSame(AppointmentSlot::AFTERNOON, AppointmentSlot::fromTime('21:00:00'));
        $this->assertNull(AppointmentSlot::fromTime('21:05:00'));
    }
}
