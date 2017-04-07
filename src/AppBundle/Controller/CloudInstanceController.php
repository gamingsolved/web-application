<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
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

        /** @var Region $region */
        foreach ($regions as $region) {
            if ($region->getIsCurrentlyChoosable()) {
                $regionChoices[$region->getHumanName()] = $region->getInternalName();
            }
        }

        /** @var Form $form */
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

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $cloudInstance = $cloudInstanceProvider->createInstanceForRemoteDesktopAndRegion(
                $remoteDesktop,
                $cloudInstanceProvider->getRegionByInternalName($form->get('region')->getData())
            );

            $hourlyCosts = $cloudInstance
                ->getCloudInstanceProvider()
                ->getHourlyCostsForFlavorImageRegionCombination(
                    $cloudInstance->getFlavor(),
                    $cloudInstance->getImage(),
                    $cloudInstance->getRegion()
                );

            $em = $this->getDoctrine()->getManager();
            /** @var AccountMovementRepository $accountMovementRepository */
            $accountMovementRepository = $em->getRepository(AccountMovement::class);

            if ($hourlyCosts > $accountMovementRepository->getAccountBalanceForUser($user)) {
                return $this->render(
                    'AppBundle:cloudInstance:new.html.twig',
                    [
                        'insufficientAccountBalance' => true,
                        'currentAccountBalance' => $accountMovementRepository->getAccountBalanceForUser($user),
                        'hourlyCosts' => $hourlyCosts,
                        'form' => $form->createView()
                    ]
                );
            }

            $cloudInstance->setStatus(CloudInstance::STATUS_IN_USE);
            $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH);

            $remoteDesktop->addCloudInstance($cloudInstance);
            $em->persist($remoteDesktop);
            $em->flush();

            return $this->redirectToRoute('remotedesktops.index');
        } else {
            return $this->render(
                'AppBundle:cloudInstance:new.html.twig',
                [
                    'insufficientAccountBalance' => false,
                    'currentAccountBalance' => null,
                    'hourlyCosts' => null,
                    'form' => $form->createView()
                ]
            );
        }
    }

    public function remainingTtlApiAction(Request $request)
    {
        $cloudInstanceProvider = (string)$request->query->get('cloudInstanceProvider');
        $providerInstanceId = (string)$request->query->get('providerInstanceId');

        if ($cloudInstanceProvider === 'aws') {

            $em = $this->getDoctrine()->getManager();
            /** @var EntityRepository $awsCloudInstanceRepository */
            $awsCloudInstanceRepository = $em->getRepository(AwsCloudInstance::class);
            /** @var AwsCloudInstance $awsCloudInstance */
            $awsCloudInstance = $awsCloudInstanceRepository->findOneBy(['ec2InstanceId' => $providerInstanceId]);

            if (is_null($awsCloudInstance)) {
                return $this->json(
                    'No instance with id ' . $providerInstanceId . ' for provider ' . $cloudInstanceProvider . ' found',
                    Response::HTTP_NOT_FOUND
                );
            } else {
                /** @var \DateTime $scheduleForStopAt */
                $scheduleForStopAt = $awsCloudInstance->getScheduleForStopAt();
                if (is_null($scheduleForStopAt)) {
                    return $this->json(
                        'No remaining ttl available for instance ' . $providerInstanceId,
                        Response::HTTP_NOT_FOUND
                    );
                }

                $now = DateTimeUtility::createDateTime();

                return $this->json((int)($scheduleForStopAt->getTimestamp() - $now->getTimestamp()));
            }

        } else {
            return $this->json('Unknown provider ' . $cloudInstanceProvider, Response::HTTP_BAD_REQUEST);
        }
    }
}
