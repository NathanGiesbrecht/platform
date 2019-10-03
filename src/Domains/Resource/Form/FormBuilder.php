<?php

namespace SuperV\Platform\Domains\Resource\Form;

use Illuminate\Http\Request;
use SuperV\Platform\Contracts\Dispatcher;
use SuperV\Platform\Domains\Database\Model\Contracts\EntryContract;
use SuperV\Platform\Domains\Resource\Field\Contracts\FieldInterface;
use SuperV\Platform\Domains\Resource\Field\FieldFactory;
use SuperV\Platform\Domains\Resource\Field\FieldModel;
use SuperV\Platform\Domains\Resource\Field\GhostField;
use SuperV\Platform\Domains\Resource\Form\Contracts\FormBuilderInterface;
use SuperV\Platform\Domains\Resource\Form\Contracts\FormInterface;
use SuperV\Platform\Domains\Resource\Resource;

class FormBuilder implements FormBuilderInterface
{
    /** @var \SuperV\Platform\Domains\Resource\Form\FormModel */
    protected $formEntry;

    /** @var \Illuminate\Http\Request */
    protected $request;

    /** @var \SuperV\Platform\Domains\Resource\Resource */
    protected $resource;

    /** @var \SuperV\Platform\Domains\Database\Model\Contracts\EntryContract */
    protected $entry;

    /** @var \SuperV\Platform\Contracts\Dispatcher */
    protected $dispatcher;

    /** @var int */
    protected $entryId;

    /**
     * @var \SuperV\Platform\Domains\Resource\Form\Contracts\FormInterface
     */
    protected $form;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function makeForm(): FormInterface
    {
        $this->form = Form::resolve($this->formEntry->getIdentifier());

        $this->makeFormFields();

        return $this->form;
    }

    public function getForm(): FormInterface
    {
        if (! $this->form) {
            $this->makeForm();
        }

        if ($this->entry) {
            $this->form->setEntry($this->entry);
            $this->form->getData()->resolveEntry($this->entry);
        }

        if ($this->request) {
            $this->form->resolveRequest($this->request);
            $this->form->getData()->resolveRequest($this->request, $this->entry);
        }

        $this->form->setUrl(sv_url()->path());

        $this->dispatcher->dispatch($this->formEntry->getIdentifier().'.events:resolved', $this->form);

        return $this->form;
    }

    public function getEntry(): ?EntryContract
    {
        return $this->entry;
    }

    public function setEntry(?EntryContract $entry = null): FormBuilder
    {
        $this->entry = $entry;

        return $this;
    }


    public function setFormEntry(FormModel $formEntry): FormBuilder
    {
        $this->formEntry = $formEntry;

        return $this;
    }

    public function setRequest($request): FormBuilder
    {
        if (is_array($request)) {
            $request = new Request($request);
        }
        $this->request = $request;

        return $this;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function getResource(): ?Resource
    {
        return $this->formEntry->getOwnerResource();
    }

    public function getFormEntry(): FormModel
    {
        return $this->formEntry;
    }

    protected function makeFormFields()
    {
        $formFields = $this->formEntry->getFormFields()
                                      ->map(function (FieldModel $field) {
                                          $field = FieldFactory::createFromEntry($field, FormField::class);

                                          if ($this->resource) {
                                              $field->setResource($this->resource);
                                          }

                                          return $field;
                                      })
                                      ->filter(function (FieldInterface $field) {
                                          return ! $field instanceof GhostField;
                                      })
                                      ->map(function (FormField $field) {
                                          $field->setForm($this->form);

                                          /**
                                           *  ????????????
                                           */
                                          if ($this->getEntry()) {
                                              $field->fillFromEntry($this->getEntry());
                                          }

                                          return $field;
                                      });

        $this->form->fields()->mergeFields($formFields);
    }

    /** @return static */
    public static function resolve()
    {
        return app(FormBuilderInterface::class);
    }
}
