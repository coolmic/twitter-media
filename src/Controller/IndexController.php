<?php

namespace App\Controller;

use App\Exception\TwitterApiException;
use App\Service\TwitterApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
        $formFactory = $this->container->get('form.factory');

        $userForm = $formFactory->createNamedBuilder('user')
            ->add('username', TextType::class)
            ->getForm();
        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            $username = (string) $userForm->get('username')->getData();

            try {
                $user = $twitterApiService->findUser($username);

                return $this->redirectToRoute('tweets/user', ['username' => $user['username']]);
            } catch (TwitterApiException $e) {
                $userForm->get('username')->addError(new FormError($e->getMessage()));
            }
        }

        $idsForm = $formFactory->createNamedBuilder('ids')
            ->add('ids', TextareaType::class)
            ->getForm();
        $idsForm->handleRequest($request);
        if ($idsForm->isSubmitted() && $idsForm->isValid()) {
            $ids = (string) $idsForm->get('ids')->getData();

            $array = [];
            foreach (explode(',', $ids) as $id) {
                $id = trim($id);
                if (preg_match('/^\d+$/', $id)) {
                    $array[] = $id;
                }
            }

            if (count($array) > 0) {
                return $this->redirectToRoute('tweets/ids', ['ids' => implode(',', $array)]);
            }

            $idsForm->get('ids')->addError(new FormError('Invalid id list'));
        }

        return $this->renderForm('index/index.html.twig', [
            'userForm' => $userForm,
            'idsForm' => $idsForm,
        ]);
    }
}
