<?php

namespace App\Controller;

use App\Exception\DownloadException;
use App\Exception\TwitterApiException;
use App\Helper\IterableHelper;
use App\Service\DownloaderService;
use App\Service\TwitterApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\Stream\TurboStreamResponse;

class TweetsController extends AbstractController
{
    #[Route('/tweets/user/{username}', name: 'tweets/user')]
    public function user(string $username, TwitterApiService $twitterApiService, Request $request): Response
    {
        $paginationToken = null;
        if ($request->query->has('pagination_token')) {
            $paginationToken = $request->query->get('pagination_token');
        }

        $user = $twitterApiService->findUser($username);
        $tweets = $twitterApiService->getUserTweets($user['id'], $paginationToken);

        $list = [];
        if ($tweets['meta']['result_count'] > 0) {
            $medias = [];
            if (isset($tweets['includes'])) {
                foreach ($tweets['includes']['media'] as $media) {
                    $medias[$media['media_key']] = $media;
                }
            }
            foreach ($tweets['data'] as $datum) {
                if (!isset($datum['attachments'])) {
                    continue;
                }
                $tweet = [
                    'id' => $datum['id'],
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
        }

        return $this->render('tweets/user.html.twig', [
            'user' => $user,
            'list' => $list,
            'meta' => $tweets['meta'],
            'paginationToken' => $paginationToken,
        ]);
    }

    #[Route('/tweets/ids', name: 'tweets/ids')]
    public function ids(TwitterApiService $twitterApiService, Request $request): Response
    {
        $ids = $request->query->get('ids');

        $tweets = $twitterApiService->getTweetsByIds(explode(',', $ids));

        $list = [];

        $medias = [];
        if (isset($tweets['includes'])) {
            foreach ($tweets['includes']['media'] as $media) {
                $medias[$media['media_key']] = $media;
            }
        }
        foreach ($tweets['data'] as $datum) {
            if (!isset($datum['attachments'])) {
                continue;
            }
            $tweet = [
                'id' => $datum['id'],
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

        return $this->render('tweets/ids.html.twig', [
            'list' => $list,
        ]);
    }

    #[Route('/tweets/download', name: 'tweets/download')]
    public function download(Request $request, TwitterApiService $twitterApiService, DownloaderService $downloaderService): Response
    {
        $id = $request->query->get('id');
        $mediaKey = $request->query->get('mediaKey');
        $paginationToken = $request->query->get('paginationToken');

        try {
            $tweet = $twitterApiService->getTweetDetails($id);
        } catch (TwitterApiException $e) {
            if ($request->headers->has('Turbo-Frame')) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('home');
        }

        $data = $tweet['data'];
        $authorId = $data['author_id'];
        if (isset($data['referenced_tweets'])) {
            $referencedTweetId = $data['referenced_tweets'][0]['id'];

            $referencedTweet = IterableHelper::findFn($tweet['includes']['tweets'], function ($tweet) use ($referencedTweetId) {
                return $tweet['id'] === $referencedTweetId;
            });
            $authorId = $referencedTweet['author_id'];
        }

        $user = IterableHelper::findFn($tweet['includes']['users'], function ($user) use ($authorId) {
            return $user['id'] === $authorId;
        });
        $username = $user['username'];

        $media = IterableHelper::findFn($tweet['includes']['media'], function ($media) use ($mediaKey) {
            return $media['media_key'] === $mediaKey;
        });
        $url = $media['url'];

        try {
            $path = $username.DIRECTORY_SEPARATOR.pathinfo($url, PATHINFO_BASENAME);
            $downloaderService->download($url, $path);
            $downloadUrl = null;
            $error = null;
        } catch (DownloadException $e) {
            $downloadUrl = $request->getRequestUri();
            $error = $e->getMessage();
        }

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('tweets/_image.html.twig', [
                'media' => $media,
                'downloadUrl' => $downloadUrl,
                'error' => $error,
            ], new TurboStreamResponse());
        }

        if ($error) {
            $this->addFlash('danger', 'Download fail : '.$error);
        } else {
            $this->addFlash('success', 'Download successful');
        }

        return $this->redirectToRoute('tweets/user', [
            'username' => $username,
            'pagination_token' => $paginationToken,
        ], Response::HTTP_SEE_OTHER);
    }
}
