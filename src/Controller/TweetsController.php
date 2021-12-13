<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TweetsController extends AbstractController
{
    #[Route('/tweets/{username}', name: 'tweets')]
    public function index(string $username): Response
    {
        return $this->render('tweets/index.html.twig', [
            'username' => $username,
        ]);
    }
}
