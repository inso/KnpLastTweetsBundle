<?php

namespace Knp\Bundle\LastTweetsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Knp\Bundle\LastTweetsBundle\Twitter\LastTweetsFetcher\FetcherInterface;
use Knp\Bundle\LastTweetsBundle\Twitter\Exception\TwitterException;

class TwitterController extends Controller
{
    public function lastTweetsAction($username, $limit = 10, $age = null, $view = 'KnpLastTweetsBundle:Tweet:lastTweets.html.twig')
    {
        /* @var $twitter FetcherInterface */
        $twitter = $this->get('knp_last_tweets.last_tweets_fetcher');

        try {
            $tweets = $twitter->fetch($username, $limit);
        } catch (TwitterException $e) {
            $tweets = array();
        }

        $response = $this->render($view, array(
            'username' => $username,
            'tweets'   => $tweets,
        ));

        if ($age) {
            $response->setSharedMaxAge($age);
        }

        return $response;
    }
}
