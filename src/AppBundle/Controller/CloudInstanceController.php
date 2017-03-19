<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;

class CloudInstanceController extends Controller
{
    /**
     * @ParamConverter("remoteDesktop", class="AppBundle:RemoteDesktop\RemoteDesktop")
     */
    public function newAction(RemoteDesktop $remoteDesktop, Request $request)
    {
        $user = $this->getUser();

        if ($remoteDesktop->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('remotedesktops.index', [], Response::HTTP_FORBIDDEN);
        }

        $cloudInstanceProvider = $remoteDesktop->getCloudInstanceProvider();

        $regions = $cloudInstanceProvider->getRegions();
        $regionChoices = [];

        foreach ($regions as $region) {
            $regionChoices[$region->getHumanName()] = $region->getInternalName();
        }

        $form = $this->createFormBuilder()
            ->add(
                'region',
                ChoiceType::class,
                [
                    'choices' => $regionChoices,
                    'expanded' => true,
                    'multiple' => false,
                    'label' => 'cloudInstance.new.form.region_label'
                ]
            )
            ->add('send', SubmitType::class, ['label' => 'cloudInstance.new.form.submit_label', 'attr' => ['class' => 'btn-primary']])
            ->getForm();

        return $this->render('AppBundle:cloudInstance:new.html.twig', ['form' => $form->createView()]);
    }
}
