<?php

namespace App\Controller;

use App\Exception\TwitterApiException;
use App\Service\TwitterApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, TwitterApiService $twitterApiService): Response
    {
        $form = $this->createFormBuilder()
            ->add('username', TextType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $username = (string) $form->get('username')->getData();

            try {
                $user = $twitterApiService->findUser($username);

                return $this->redirectToRoute('tweets', ['username' => $user['username']]);
            } catch (TwitterApiException $e) {
                $form->get('username')->addError(new FormError($e->getMessage()));
            }
        }

        return $this->renderForm('index/index.html.twig', [
            'form' => $form,
        ]);
    }
}
