<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Workflow\Command;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * Class LogStateMachineTransitionCommand
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Workflow\Command
 */
class LogStateMachineTransitionCommand implements CommandInterface
{
    /**
     * @param Event $event
     * @param ContainerInterface $container
     *
     * @return bool
     */
    public function execute(Event $event, ContainerInterface $container): bool
    {
        /** @var ObjectManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $subject = $event->getSubject();
        $transition = $event->getTransition();

//        if ($container->has('security.token_storage')) {
//            $token = $container->get('security.token_storage')->getToken();
//
//            if ($token !== null) {
//                $admin = $token->getUser();
//            }
//        }
//
//        if (!isset($admin) || !is_object($admin)) {
//            $admin = $entityManager->getRepository(Admin::class)->find(Admin::SYSTEM_USER_ID);
//        }

        $stateMachineHistory = (new StateMachineHistory())
            ->setSchemaName($entityManager->getClassMetadata(get_class($subject))->getTableName())
            ->setSchemaIdentifier($subject->getWorkflowIdentifier())
            ->setTransition($transition->getName())
            ->setInitialState($transition->getFroms()[0])
            ->setFinalState($transition->getTos()[0])
//            ->setCreatedBy($admin)
//            ->setUpdatedBy($admin)
        ;

        $entityManager->persist($stateMachineHistory);
        $entityManager->flush();

        return true;
    }
}
