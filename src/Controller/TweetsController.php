<?php

namespace App\Controller;

use App\Exception\DownloadException;
use App\Service\DownloaderService;
use App\Service\TwitterApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\Stream\TurboStreamResponse;

class TweetsController extends AbstractController
{
    #[Route('/tweets/user/{username}', name: 'tweets')]
    public function index(string $username, TwitterApiService $twitterApiService, Request $request): Response
    {
        $paginationToken = null;
        if ($request->query->has('pagination_token')) {
            $paginationToken = $request->query->get('pagination_token');
        }

        $user = $twitterApiService->findUser($username);
        $tweets = $twitterApiService->getTweets($user['id'], [
            'pagination_token' => $paginationToken,
        ]);

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

        return $this->render('tweets/index.html.twig', [
            'user' => $user,
            'list' => $list,
            'meta' => $tweets['meta'],
            'paginationToken' => $paginationToken,
        ]);
    }

    #[Route('/tweets/download', name: 'tweets/download')]
    public function download(Request $request, DownloaderService $downloaderService): Response
    {
        $key = $request->query->get('key');
        $username = $request->query->get('username');
        $paginationToken = $request->query->get('paginationToken');
        $url = $request->query->get('url');

        $downloadUrl = null;
        $error = null;

        $path = $username.DIRECTORY_SEPARATOR.pathinfo($url, PATHINFO_BASENAME);
        try {
            $downloaderService->download($url, $path);
        } catch (DownloadException $e) {
            $downloadUrl = $request->getRequestUri();
            $error = $e->getMessage();
        }

        if ($request->headers->has('Turbo-Frame')) {
            return $this->render('tweets/_image.html.twig', [
                'key' => $key,
                'url' => $url,
                'downloadUrl' => $downloadUrl,
                'error' => $error,
            ], new TurboStreamResponse());
        }

        if ($error) {
            $this->addFlash('danger', 'Download fail : '.$error);
        } else {
            $this->addFlash('success', 'Download successful');
        }

        return $this->redirectToRoute('tweets', [
            'username' => $username,
            'pagination_token' => $paginationToken,
        ], Response::HTTP_SEE_OTHER);
    }
}
