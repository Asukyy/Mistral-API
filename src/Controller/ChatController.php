<?php
namespace App\Controller;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChatController extends AbstractController
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    #[Route('/envoyer-message', name: 'envoyer_message')]
    public function envoyerMessage(Request $request, EntityManagerInterface $entityManager): Response
    {

        // Envoi du message de l'utilisateur à l'API Mistral
        $response = $this->httpClient->request('POST', 'https://api.mistral.ai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'model' => 'mistral-tiny',
                'messages' => [
                    ['role' => 'user', 'content' => $messageContent]
                ],
            ],
        ]);

        $responseData = $response->toArray();

        // Créer un nouveau message avec la réponse de l'IA et l'ajouter à la base de données

        // Votre logique pour retourner une réponse au client
    }
}

?>
