<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\CloudInstanceProvider\CloudInstanceProvider;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopKind;
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
                'kind',
                ChoiceType::class,
                [
                    'choices' => [
                        'Playing games' => RemoteDesktopKind::GAMING,
                        'Working with CAD programs' => RemoteDesktopKind::CAD
                    ],
                    'label' => 'What do you want to use this remote desktop for?'
                ]
            );
    }
}
