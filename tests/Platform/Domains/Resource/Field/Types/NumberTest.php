<?php

namespace Tests\Platform\Domains\Resource\Field\Types;

use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Resource\Field\Types\Number;
use Tests\Platform\Domains\Resource\ResourceTestCase;

class NumberTest extends ResourceTestCase
{
    /** @test */
    function type_number_integer()
    {
        $res = $this->create(function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('age');
        });
        $this->assertColumnExists($res->handle(), 'age');

        $age = $res->freshWithFake(['age' => '10'])->build()->getField('age');

        $this->assertInstanceOf(Number::class, $age);
        $this->assertEquals('number', $age->getType());
        $this->assertEquals(['integer', 'min:0', 'required'], $age->makeRules());
        $this->assertEquals('integer', $age->getConfigValue('type'));
        $this->assertTrue($age->getConfigValue('unsigned'));

        $this->assertSame(10, $age->getValue());
    }

    /** @test */
    function type_number_decimal()
    {
        $res = $this->create(function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('height', 3, 2);
        });
        $this->assertColumnExists($res->handle(), 'height');

        $height = $res->freshWithFake(['height' => '1.754234'])->build()->getField('height');

        $this->assertInstanceOf(Number::class, $height);
        $this->assertEquals('number', $height->getType());
        $this->assertEquals('decimal', $height->getConfigValue('type'));
        $this->assertEquals(['numeric', 'required'], $height->makeRules());

        $this->assertEquals(3, $height->getConfigValue('total'));
        $this->assertEquals(2, $height->getConfigValue('places'));

        $this->assertSame(1.75, $height->getValue());
    }
}