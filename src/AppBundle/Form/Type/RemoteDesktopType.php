<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RemoteDesktopType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Title for this remote desktop'])
            ->add(
                'remoteDesktopType',
                ChoiceType::class,
                [
                    'choices' => [
                        'Amazon AWS' => CloudInstanceProvider::CLOUD_INSTANCE_PROVIDER_AWS_ID
                    ],
                    'label' => 'Cloud provider'
                ]
            );
    }
}
