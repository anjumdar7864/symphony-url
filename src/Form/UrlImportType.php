<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UrlImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'CSV File',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null, // Set your form data class if applicable
            'csrf_protection' => true, // Enable CSRF protection
            'csrf_field_name' => '_token', // Customize the CSRF token field name
            'csrf_token_id' => 'url_import', // Customize the CSRF token ID
            'validation_groups' => ['Default'], // Specify the validation groups if needed
        ]);
    }
}
