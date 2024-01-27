<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Messages;
use App\Entity\containerMess;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;


class ContainerMessage extends AbstractController {

//   return vers lindex
    #[Route('/container', name: 'container')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response {
       $container = new ContainerMess();

       $user = $this->getUser();

         if (!$user) {
              return $this->redirectToRoute('app_login');
            }

            $containers = $entityManager->getRepository(ContainerMess::class)->findBy(['user' => $user]);


        $form = $this->createFormBuilder($container)
        ->add('name', TextType::class, ['label' => 'Nom du container'])
        ->add('send', SubmitType::class, ['label' => 'Envoyer'])
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $container = $form->getData();
            $container->setDateCreated(new \DateTime());
            $container->setUser($user);
            $entityManager->persist($container);
            $entityManager->flush();
            return $this->redirectToRoute('container');
        }

        return $this->render('container_message/index.html.twig', [
            'controller_name' => 'ContainerMessageController',
            'containers' => $containers,
        ]);
    }

    #[Route('/container/delete/{id}', name: 'delete_container')]
    public function deleteContainer(EntityManagerInterface $entityManager, $id): Response {
        $container = $entityManager->getRepository(ContainerMess::class)->find($id);
        if ($container) {
            $entityManager->remove($container);
            $entityManager->flush();
        }
        return $this->redirectToRoute('container');
    }

    #[Route('/container/edit/{id}', name: 'edit_container', methods: ['POST'])]
    public function editContainer(Request $request, EntityManagerInterface $entityManager, $id): Response {
        $container = $entityManager->getRepository(ContainerMess::class)->find($id);
        if ($container) {
            $newName = $request->request->get('newName');
            if ($newName) {
                $container->setName($newName);
                $entityManager->flush();
            }
        }
        return $this->redirectToRoute('container');
    }


    #[Route('/container/view/{id}', name: 'view_container')]
    public function viewContainer(EntityManagerInterface $entityManager, $id): Response {
        $container = $entityManager->getRepository(ContainerMess::class)->find($id);
        $messages = $container ? $container->getMessages() : [];

        return $this->render('container_message/container_view.html.twig', [
            'container' => $container,
            'messages' => $messages,
        ]);
    }

    // add_container
    #[Route('/add-container', name: 'add_container', methods: ['POST'])]
    public function addContainer(Request $request, EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $containerName = $request->request->get('name');
        if ($containerName) {
            $container = new ContainerMess();
            $container->setName($containerName);
            $container->setDateCreated(new \DateTime());
            $container->setUser($user);

            $entityManager->persist($container);
            $entityManager->flush();

            return $this->redirectToRoute('container');
        }


        return $this->redirectToRoute('container', ['error' => 'Nom du container requis']);
    }

    #[Route('/envoyer-message', name: 'envoyer_message')]
    public function envoyerMessage(Request $request, EntityManagerInterface $entityManager): Response {
        if ($request->isMethod('POST') && $request->isXmlHttpRequest()) {
            $messageContent = $request->request->get('message');
            $containerId = $request->request->get('container_id');

            $container = $entityManager->getRepository(ContainerMess::class)->find($containerId);
            if (!$container) {
                return $this->json(['status' => 'error', 'message' => 'Container non trouvé'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->getUser();
            if (!$user) {
                return $this->json(['status' => 'error', 'message' => 'Utilisateur non connecté'], Response::HTTP_BAD_REQUEST);
            }

            if (!$messageContent) {
                return $this->json(['status' => 'error', 'message' => 'Message vide'], Response::HTTP_BAD_REQUEST);
            }

            $message = new Messages();
            $message->setContent($messageContent);
            $message->setTimestamp(new \DateTime());
            $message->setContainer($container); // Associer le message au container spécifié
            $message->setUser($user);

            $entityManager->persist($message);
            $entityManager->flush();

            return $this->json([
                'status' => 'success',
                'messageContent' => $message->getContent(),
                'timestamp' => $message->getTimestamp()->format('Y-m-d H:i'),
            ]);
        }

        return $this->json(['status' => 'error', 'message' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);
    }




}

?>
