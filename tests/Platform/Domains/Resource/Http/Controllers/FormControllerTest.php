<?php

namespace Tests\Platform\Platform\Domains\Resource\Http\Controllers;

use SuperV\Platform\Domains\Database\Schema\Blueprint;
use SuperV\Platform\Domains\Resource\Field\FieldComposer;
use SuperV\Platform\Domains\Resource\Field\FieldFactory;
use SuperV\Platform\Domains\Resource\Field\FieldModel;
use SuperV\Platform\Domains\Resource\Form\FormFactory;
use SuperV\Platform\Domains\Resource\Form\FormModel;
use Tests\Platform\Domains\Resource\ResourceTestCase;

class FormControllerTest extends ResourceTestCase
{
    /**
     * @var \SuperV\Platform\Domains\Resource\Resource
     */
    protected $posts;

    /**
     * @var \SuperV\Platform\Domains\Resource\Form\FormModel
     */
    protected $formEntry;

    function test__form_page_with_uuid()
    {
        $form = $this->getUserPage('sv/forms/'.$this->formEntry->uuid);
//        $form = HelperComponent::from($page->getProp('blocks.0'));

        $this->assertEquals('sv-form', $form->getName());
        $this->assertEquals($this->posts->getFields()->count(), $form->countProp('fields'));

        foreach ($form->getProp('fields') as $fieldArray) {
            $field = $this->posts->getField($fieldArray['name']);
            $this->assertEquals(
                (new FieldComposer($field))->forForm()->get(),
                $fieldArray
            );
        }
    }

    function test__public_form()
    {
        $this->withoutExceptionHandling();
        $formEntry = $this->makePublicForm();
        $this->assertTrue($formEntry->isPublic());
        $this->getPublicPage('sv/forms/'.$formEntry->uuid);
    }

    function test__user_form()
    {
        $formEntry = $this->makeForm(['public' => false]);
        $this->assertFalse($formEntry->isPublic());
        $this->getUserPage('sv/forms/'.$formEntry->uuid);
    }

    function test__user_forms_are_not_accessible_by_public()
    {
        $formEntry = $this->makeForm(['public' => false]);
        $this->assertFalse($formEntry->isPublic());
        $response = $this->getJson('sv/forms/'.$formEntry->uuid);
        $response->assertStatus(401);

        $response = $this->postJson('sv/forms/'.$formEntry->uuid);
        $response->assertStatus(401);
    }

    function test__fields()
    {
        $this->withoutExceptionHandling();

        $form = $this->makePublicForm();

        $response = $this->getJson($form->getUrl(). '/fields/name');
        $response->assertOk();

        $fieldArray = $response->decodeResponseJson('data');

        $field = FieldFactory::createFromArray($fieldArray);
        $this->assertEquals(
            (new FieldComposer($field))->forForm()->get(),
            $fieldArray
        );
    }

    protected function makeForm(array $overrides = []): FormModel
    {
        $factory = app(FormFactory::class);
        $formEntry = $factory->create(array_merge([
            'title'  => 'Public Form',
            'public' => false,
        ], $overrides));

        $formEntry->fields()->attach(FieldModel::create(['type' => 'text', 'name' => 'name']));
        $formEntry->fields()->attach(FieldModel::create(['type' => 'text', 'name' => 'email']));
        $formEntry->fields()->attach(FieldModel::create(['type' => 'belongs_to', 'name' => 'user', 'config' =>[
            'related_resource' => 'users',
        ] ]));

        return $formEntry;
    }

    protected function setUp()
    {
        parent::setUp();

        $this->posts = $this->create('test_posts',
            function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->boolean('is_published');
            });

        /** @var FormModel $postsForm */
        $this->formEntry = FormModel::query()->where('resource_id', $this->posts->id())->first();
    }

    /**
     * @return \SuperV\Platform\Domains\Resource\Form\FormModel
     */
    protected function makePublicForm(): \SuperV\Platform\Domains\Resource\Form\FormModel
    {
        return $this->makeForm(['public' => true]);
    }
}