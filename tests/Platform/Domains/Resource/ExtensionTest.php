<?php

namespace Tests\Platform\Domains\Resource;

use SuperV\Platform\Domains\Resource\Extension\Contracts\ObservesSaved;
use SuperV\Platform\Domains\Resource\Extension\Contracts\ObservesSaving;
use SuperV\Platform\Domains\Resource\Extension\Contracts\ResourceExtension;
use SuperV\Platform\Domains\Resource\Extension\Extension;
use SuperV\Platform\Domains\Resource\Extension\RegisterExtensionsInPath;
use SuperV\Platform\Domains\Resource\Field\FieldConfig;
use SuperV\Platform\Domains\Resource\Field\Types\Number;
use SuperV\Platform\Domains\Resource\Field\Types\Text;
use SuperV\Platform\Domains\Resource\Field\Types\Textarea;
use SuperV\Platform\Domains\Resource\Model\ResourceEntryModel;
use SuperV\Platform\Domains\Resource\Resource;

class ExtensionTest extends ResourceTestCase
{
    /** @test */
    function overrides_fields()
    {
        $res = $this->makeResource('t_users', ['name', 'age:integer']);
        $res->build();

        Extension::register(TestUserResourceExtension::class);
        $ext = Resource::of('t_users');

        $this->assertNotEquals($res, $ext);

        $this->assertEquals(3, $ext->getFields()->count());
        $this->assertInstanceOf(Text::class, $ext->getField('name'));

        $age = $ext->getField('age');
        $this->assertInstanceOf(Number::class, $age);
        $this->assertEquals($res->getField('age')->getEntry(), $age->getEntry());
        $this->assertEquals(['integer', 'required', 'min:18', 'max:150'], $age->makeRules());

        $bio = $ext->getField('bio');
        $this->assertInstanceOf(Textarea::class, $bio);
        $this->assertEquals('textarea', $bio->getType());
    }

    /** @test */
    function gets_before_saving()
    {
        $res = $this->makeResource('t_users', ['name', 'age:integer']);
        $res->build();

        Extension::register(TestUserResourceExtension::class);
        $ext = Resource::of('t_users');
        $user = $ext->createFake(['age => 40']); // rules set in extension

        TestUserResourceExtension::$callbacks['saving'] = function (ResourceEntryModel $entry) {
            $this->assertEquals(100, $entry->age);

            return $entry->age = $entry->age + 1;
        };
        TestUserResourceExtension::$callbacks['saved'] = function (ResourceEntryModel $entry) {
            return $entry->age = $entry->age + 1;
        };
        $user->age = 100;
        $user->save();

        // object at current pointer is incremented twice
        $this->assertEquals(102, $user->age);

        // since the last one was after saving, it is not persisted
        $this->assertEquals(101, $user->fresh()->age);
    }

    /** @test */
    function registers_extensions_from_path()
    {
        RegisterExtensionsInPath::dispatch(
            __DIR__.'/Fixtures/Extensions',
            'Tests\Platform\Domains\Resource\Fixtures\Extensions'
        );

        $this->assertNotNull(Extension::get('test_a'));
//        $this->assertNotNull(Extension::get('test_b'));
    }

    protected function tearDown()
    {
        parent::tearDown();

        Extension::unregister(TestUserResourceExtension::class);
    }
}

class TestUserResourceExtension implements ResourceExtension, ObservesSaving, ObservesSaved
{
    /** @var array */
    public static $callbacks = [];

    /** @var array */
    protected $called = [];

    public function isCalled($event)
    {
        return array_has($this->called, $event);
    }

    public function saving(ResourceEntryModel $entry)
    {
        if ($saving = array_get(static::$callbacks, 'saving')) {
            $saving($entry);
            $this->called[] = 'saving';
        }
    }

    public function saved(ResourceEntryModel $entry)
    {
        if ($saved = array_get(static::$callbacks, 'saved')) {
            $saved($entry);
            $this->called[] = 'saved';
        }
    }

    public function fields()
    {
        return [
            'name',
            FieldConfig::field('age')->mergeRules(['min:18', 'max:150']),
            Textarea::make('bio'),
        ];
    }

    public function extends()
    {
        return 't_users';
    }
}