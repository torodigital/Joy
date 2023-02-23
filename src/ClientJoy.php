<?php

namespace ToroDigital\Joy;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class ClientJoy
{
    private static $cookies;
    private static $client;
    private static $endpoint = '/participations/v1/tickets-ranking-by-promo/';
    private static $promotion_id;

    public static function init($host, $promotion_id)
    {
        self::$client = new Client([
            'base_uri' => $host,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);

        self::$promotion_id = $promotion_id;
    }

    public static function response($res)
    {
        return json_decode($res->getBody());
    }

    public static function jsonp_decode($jsonp, $assoc = false) {
        $jsonp = (string)$jsonp;

        if($jsonp[0] !== '[' && $jsonp[0] !== '{') { 
            $jsonp = substr($jsonp, strpos($jsonp, '('));
        }
        $jsonp = trim($jsonp);      // remove trailing newlines
        $jsonp = trim($jsonp,'()'); // remove leading and trailing parenthesis
        
        return json_decode($jsonp, $assoc);
        
    }

    public static function getRanking($page = 1, $limit = 10)
    {
        $res = self::$client->get(self::$endpoint.self::$promotion_id.'?page='.$page.'&limit='.$limit);
        
        $ranking = self::jsonp_decode($res->getBody());

        $ranking = new Collection($ranking->data);

        $total = $ranking->count(); //all list count
        $pages = ceil($total/$limit);

        $ranking = $ranking->forPage($page,$limit);

        return ['data' => $ranking, 'pages' => $pages, 'total' => $total ];
    }
    
}
