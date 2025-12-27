<?php

namespace AaronFrancis\Eventable\Tests;

use AaronFrancis\Eventable\PruneConfig;
use Illuminate\Support\Carbon;

class PruneConfigTest extends TestCase
{
    public function test_default_values(): void
    {
        $config = new PruneConfig;

        $this->assertNull($config->before);
        $this->assertEquals(0, $config->keep);
        $this->assertTrue($config->varyOnData);
    }

    public function test_custom_before(): void
    {
        $before = Carbon::now()->subDays(30);
        $config = new PruneConfig(before: $before);

        $this->assertEquals($before, $config->before);
    }

    public function test_custom_keep(): void
    {
        $config = new PruneConfig(keep: 5);

        $this->assertEquals(5, $config->keep);
    }

    public function test_custom_vary_on_data(): void
    {
        $config = new PruneConfig(varyOnData: false);

        $this->assertFalse($config->varyOnData);
    }

    public function test_all_custom_values(): void
    {
        $before = Carbon::now()->subDays(7);
        $config = new PruneConfig(
            before: $before,
            keep: 10,
            varyOnData: false
        );

        $this->assertEquals($before, $config->before);
        $this->assertEquals(10, $config->keep);
        $this->assertFalse($config->varyOnData);
    }

    public function test_is_readonly(): void
    {
        $config = new PruneConfig(keep: 5);

        $reflection = new \ReflectionClass($config);

        $this->assertTrue($reflection->isReadOnly());
    }
}
