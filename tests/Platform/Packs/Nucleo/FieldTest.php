<?php

namespace Tests\SuperV\Platform\Packs\Nucleo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuperV\Platform\Domains\Nucleo\Prototype;
use Tests\SuperV\Platform\BaseTestCase;

class FieldTest extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * @return mixed
     */
    protected function setUpField()
    {
        $prototype = Prototype::create(['table' => 'tasks']);
        $field = $prototype->fields()->create(
            [
                'slug' => 'title',
                'type' => 'string',
            ]
        );

        return $field;
    }

    /** @test */
    function can_add_field_rules()
    {
        $field = $this->setUpField();

        $field->addRule('required')
              ->addRule('unique')
              ->save();

        $this->assertContains('required', $field->rules);
        $this->assertContains('unique', $field->rules);
    }

    /** @test */
    function can_set_field_rules()
    {
        $field = $this->setUpField();

        $field->setRules(['required', 'unique'])->save();

        $this->assertEquals(['required', 'unique'], $field->rules);
    }

    /** @test */
    function can_set_field_rules_in_string_format()
    {
        $field = $this->setUpField();

        $field->setRules('required|unique')->save();

        $this->assertEquals(['required', 'unique'], $field->rules);
    }
}