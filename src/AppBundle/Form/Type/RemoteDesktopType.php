<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\RemoteDesktop\RemoteDesktopKind;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RemoteDesktopType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'remoteDesktop.new.form.title_label'])
            ->add(
                'kind',
                ChoiceType::class,
                [
                    'choices' => [
                        'remoteDesktop.kind.gamingpro' => RemoteDesktopKind::GAMING_PRO,
                        'remoteDesktop.kind.cadpro' => RemoteDesktopKind::CAD_PRO,
                        'remoteDesktop.kind.cadultra' => RemoteDesktopKind::CAD_ULTRA,
                        'remoteDesktop.kind.3dmediapro' => RemoteDesktopKind::THREED_MEDIA_PRO,
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'label' => 'remoteDesktop.new.form.kind_label'
                ]
            )
            ->add('send', SubmitType::class, ['label' => 'remoteDesktop.new.form.submit_label', 'attr' => ['class' => 'btn-primary']]);
    }
}
