<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Tests\TestCase;

class ScheduleRegistrationTest extends TestCase
{
    private function scheduledEvents(): array
    {
        app(ConsoleKernel::class)->bootstrap();

        return app(Schedule::class)->events();
    }

    private function eventFor(string $command): object
    {
        foreach ($this->scheduledEvents() as $event) {
            if (str_contains($event->command, $command)) {
                return $event;
            }
        }

        $this->fail("Scheduled command '{$command}' is not registered.");
    }

    public function test_all_automation_commands_are_scheduled(): void
    {
        $commands = collect($this->scheduledEvents())->pluck('command')->all();

        foreach ([
            'assignments:check-deadlines',
            'procurement:escalate-overdue',
            'bookings:expire-pending',
            'bookings:send-reminders',
            'notifications:prune',
            'subscriptions:notify-lifecycle',
            'subscriptions:expire',
        ] as $command) {
            $this->assertTrue(
                collect($commands)->contains(fn ($c) => str_contains($c, $command)),
                "{$command} is scheduled"
            );
        }
    }

    public function test_scheduled_commands_have_overlap_guard(): void
    {
        foreach ($this->scheduledEvents() as $event) {
            $this->assertTrue($event->withoutOverlapping, "{$event->command} must not overlap");
        }
    }

    public function test_scheduled_commands_only_run_in_production(): void
    {
        foreach ($this->scheduledEvents() as $event) {
            $this->assertSame(['production'], $event->environments, "{$event->command} must be production-only");
        }
    }

    public function test_schedule_frequencies_are_as_documented(): void
    {
        $this->assertSame('0 * * * *', $this->eventFor('assignments:check-deadlines')->expression);
        $this->assertSame('0 * * * *', $this->eventFor('procurement:escalate-overdue')->expression);
        $this->assertSame('0 * * * *', $this->eventFor('bookings:expire-pending')->expression);
        $this->assertSame('0 * * * *', $this->eventFor('subscriptions:expire')->expression);
        $this->assertSame('0 0 * * *', $this->eventFor('subscriptions:notify-lifecycle')->expression);
        $this->assertSame('0 0 * * *', $this->eventFor('notifications:prune')->expression);
        $this->assertSame('0 8 * * *', $this->eventFor('bookings:send-reminders')->expression);
    }
}
