<?php

namespace App\Forms\Admin;

use App\Models\Role;
use BalajiDharma\LaravelFormBuilder\Form;

class ActivityForm extends Form
{
    protected $showFieldErrors = false;

    public function buildForm()
    {

        $this->add('title', 'text', [
            'label' => __('Title'),
        ]);

        $this->add('description', 'textarea', [
            'label' => __('Description'),
        ]);

        $this->add('image_url', 'file', [
            'label' => __('Image'),
        ]);

        $this->add('date', 'date', [
            'label' => __('Date'),
        ]);


        $submitLabel = __('Create');

        if ($this->model) {
            $submitLabel = __('Update');
        }

        $this->add('submit', 'submit', [
            'label' => $submitLabel,
        ]);
    }
}
