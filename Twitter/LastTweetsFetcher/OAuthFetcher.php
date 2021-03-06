<?php

namespace Knp\Bundle\LastTweetsBundle\Twitter\LastTweetsFetcher;

use Knp\Bundle\LastTweetsBundle\Twitter\Exception\TwitterException;
use Knp\Bundle\LastTweetsBundle\Twitter\Tweet;

class OAuthFetcher implements FetcherInterface
{
    private $oauth;

    public function __construct($oauth)
    {
        $this->oauth = $oauth;
    }

    public function fetch($usernames, $count = 10, $excludeReplies = true, $includeRts = false, $retryCall = 1)
    {
        if (!is_array($usernames)) {
            $usernames = array((string) $usernames);
        }

        if ($count > 200) {
            throw new TwitterException('Maximum limit of tweets is 200.');
        }

        $i = 0;
        $maxId = 0;
        $page = 1;
        $limit = $count;
        $count *= 2; // in order to decrease api requests
        $combineData = array();

        while ($i < $page && ($page - 1) <= $retryCall) { // using only if we don't have enough tweets
            foreach ($usernames as $username) { // aggregate tweets for every username

                $parameters = array(
                    'screen_name' => urlencode($username),
                    'count' => $count,
                    'trim_user' => 1,
                    'exclude_replies' => (int) $excludeReplies,
                    'include_rts' => (int) $includeRts,
                    'page' => $page
                );

                if($maxId) {
                    $parameters['max_id'] = $maxId;
                }

                $data = $this->fetchTweets($parameters);

                // we need to inject username, when use "trim_user"
                array_walk($data, function(&$tweet) use($username) {
                    if (is_array($tweet)) {
                        $tweet['username'] = $username;
                    }
                });

                $combineData = array_merge($combineData, $data);
            }

            if (count($combineData) < $limit) {
                $maxId = end($combineData);
                $maxId = $maxId['id_str'];

                $page++;
            }

            $i++;
        }

        usort($combineData, function($a, $b) {
            return ($a['id_str'] > $b['id_str']) ? -1 : 1;
        });

        $i = 0;
        $tweets = array();

        foreach ($combineData as $tweetData) {
            $tweets[] = new Tweet($tweetData);

            ++$i;
            if ($i >= $limit) {
                break;
            }
        }

        unset($combineData);

        return $tweets;
    }

    protected function fetchTweets($parameters)
    {
        $data = $this->getResponse('statuses/user_timeline', $parameters);
        $data = @json_decode(@json_encode($data), true);

        if (!is_array($data)) {
            throw new TwitterException('Received wrong data.');
        }
        if (null === $data) {
            throw new TwitterException('Unable to decode data: ' . json_last_error());
        }
        if (false === $data) {
            throw new TwitterException('Received empty data from api.twitter.com');
        }
        if (isset($data['error'])) {
            throw new TwitterException(sprintf('Twitter error: %s', $data['error']));
        }

        return $data;
    }

    protected function getResponse($api, $parameters)
    {
        return $this->oauth->getApi()->get($api, $parameters);
    }
}
