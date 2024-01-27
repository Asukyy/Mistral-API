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


class HomeController extends AbstractController {

    /**
     * @Route("/", name="home")
     */
    #[Route('/', name: 'home')]
    #[Route('/container/{containerId}', name: 'home_with_container')]
    public function index(Request $request, EntityManagerInterface $entityManager, $containerId = null): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $container = new ContainerMess();
        $containerForm = $this->createFormBuilder($container)
            ->add('name', TextType::class, ['label' => 'Nom du container'])
            ->add('create', SubmitType::class, ['label' => 'Créer'])
            ->getForm();

        $containerForm->handleRequest($request);
        if ($containerForm->isSubmitted() && $containerForm->isValid()) {
            $container->setDateCreated(new \DateTime());
            $container->setUser($user);
            $entityManager->persist($container);
            $entityManager->flush();
            return $this->redirectToRoute('home');
        }

        $containers = $entityManager->getRepository(ContainerMess::class)->findBy(['user' => $user]);

        $selectedMessages = [];
        if ($containerId) {
            $selectedContainer = $entityManager->getRepository(ContainerMess::class)->find($containerId);
            if ($selectedContainer) {
                $selectedMessages = $selectedContainer->getMessages();
            }
        }

        return $this->render('home/index.html.twig', [
            'containerForm' => $containerForm->createView(),
            'containers' => $containers,
            'selectedMessages' => $selectedMessages,
        ]);
    }
 /**
     * @Route("/envoyer-message", name="route_pour_envoyer_message")
     */
    // #[Route('/envoyer-message', name: 'envoyer_message')]
    // public function envoyerMessage(Request $request, EntityManagerInterface $entityManager): Response {
    //     if ($request->isMethod('POST') && $request->isXmlHttpRequest()) {
    //         $messageContent = $request->request->get('message');

    //         $containers = $entityManager->getRepository(ContainerMess::class)->findAll();
    //         if (!$containers) {
    //             $container = new ContainerMess();
    //             $container->setName("Nom du container");
    //             $container->setDateCreated(new \DateTime());
    //             $entityManager->persist($container);
    //         } else {
    //             $container = $containers[0];
    //         }

    //         $user = $this->getUser();

    //         if(!$messageContent) throw new \Exception("Message vide");

    //         $message = new Messages();
    //         $message->setContent($messageContent);
    //         $message->setTimestamp(new \DateTime());
    //         $message->setContainer($container);
    //         $message->setUser($user);

    //         $entityManager->persist($message);
    //         $entityManager->flush();

    //         return $this->json([
    //             'status' => 'success',
    //             'messageContent' => $message->getContent(),
    //             'timestamp' => $message->getTimestamp()->format('Y-m-d H:i'),
    //         ]);
    //     }

    //     return $this->json(['status' => 'error', 'message' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);

    // }
}
?>
