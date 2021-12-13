<?php

namespace App\Controller;

use App\Service\TwitterApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class IndexController extends AbstractController
{
    /**
     * @throws ExceptionInterface
     */
    #[Route('/', name: 'home')]
    public function index(Request $request, TwitterApiService $twitterApiService): Response
    {
        $form = $this->createFormBuilder()
            ->add('username', TextType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $username = (string) $form->get('username')->getData();

            $user = $twitterApiService->findUser($username);

            return $this->redirectToRoute('tweets', ['username' => $user['username']]);
        }

        return $this->renderForm('index/index.html.twig', [
            'form' => $form,
        ]);
    }
}
