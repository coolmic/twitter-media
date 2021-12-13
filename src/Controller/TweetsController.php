<?php

namespace App\Controller;

use App\Service\TwitterApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TweetsController extends AbstractController
{
    #[Route('/tweets/{username}', name: 'tweets')]
    public function index(string $username, TwitterApiService $twitterApiService, Request $request): Response
    {
        $options = [];
        if ($request->query->has('next_token')) {
            $options['pagination_token'] = $request->query->get('next_token');
        }

        $user = $twitterApiService->findUser($username);
        $tweets = $twitterApiService->getTweets($user['id'], $options);

        $medias = [];
        foreach ($tweets['includes']['media'] as $media) {
            $medias[$media['media_key']] = $media;
        }

        $list = [];
        foreach ($tweets['data'] as $datum) {
            if (!isset($datum['attachments'])) {
                continue;
            }

            $tweet = [
                'text' => $datum['text'],
                'medias' => [],
            ];
            foreach ($datum['attachments']['media_keys'] as $mediaKey) {
                if (isset($medias[$mediaKey]) && 'photo' === $medias[$mediaKey]['type']) {
                    $tweet['medias'][] = $medias[$mediaKey];
                }
            }

            if (count($tweet['medias']) > 0) {
                $list[] = $tweet;
            }
        }

        return $this->render('tweets/index.html.twig', [
            'user' => $user,
            'list' => $list,
            'meta' => $tweets['meta'],
        ]);
    }
}
