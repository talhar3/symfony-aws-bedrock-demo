<?php

namespace App\Controller;

use App\Service\AwsBedrock;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatBotController extends AbstractController
{
    #[Route('/chatbot/ask', methods: ['GET'])]
    public function askAction(Request $request, AwsBedrock $awsBedrock): JsonResponse
    {
        $question = $request->query->get('question');
        if (!$question) {
            return $this->json(['error' => 'Missing question param'], 400);
        }

        $answer = $awsBedrock->askQuestion($question);

        return $this->json([
            'question' => $question,
            'answer' => $answer,
        ]);
    }
}
