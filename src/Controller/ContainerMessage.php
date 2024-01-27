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
use Symfony\Contracts\HttpClient\HttpClientInterface;


class ContainerMessage extends AbstractController {

    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

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

        try {

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

            // Enregistrer le message de l'utilisateur
            $userMessage = $this->createMessage($messageContent, $user, $container, $entityManager);

            // Appeler l'API Mistral pour obtenir une réponse
            $responseIA = $this->appelerIA($messageContent, $entityManager, $container);

            return $this->json([
                'status' => 'success',
                'userMessageContent' => $userMessage->getContent(),
                'iaMessageContent' => $responseIA['content'],
                'timestamp' => $userMessage->getTimestamp()->format('Y-m-d H:i'),
            ]);
        }
    } catch (\Exception $e) {
        error_log('Erreur lors de l\'envoi du message : ' . $e->getMessage());
        return $this->json(['status' => 'error', 'message' => 'Erreur interne du serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

        return $this->json(['status' => 'error', 'message' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);
    }

    private function createMessage($content, $user, $container, $entityManager) {
        $message = new Messages();
        $message->setContent($content);
        $message->setTimestamp(new \DateTime());
        $message->setContainer($container);
        $message->setUser($user);
        $entityManager->persist($message);
        $entityManager->flush();
        return $message;
    }

    private function appelerIA($messageContent, $entityManager, $container) {
        $response = $this->client->request('POST', 'https://api.mistral.ai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'model' => 'mistral-tiny',
                'messages' => [
                    ["role" => "user", "content" => $messageContent]
                ],
            ],
        ]);

        $data = $response->toArray();

        // Créer un message pour la réponse de l'IA
        $messageIA = $this->createMessage($data['responses'][0]['content'], null, $container, $entityManager);

        return [
            'content' => $data['responses'][0]['content']
        ];
    }
}

?>
