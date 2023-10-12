<?php

namespace ToroDigital\Joy;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use stdClass;

class ClientJoy
{
    private static $host = 'https://esiwxrbiye73aqmve16bn46cd.joyapp.mx';

    private static $cookies;
    private static $client;
    private static $promotion_id;

    private static $endpoint = '/participations/v1/tickets-ranking-by-promo/';
    private static $endPointBlock = '/promotions/v1/getBlocks';
    private static $endPointBlockRanking = '/participations/v1/getRankingByBlocks';

    public static function init($promotion_id)
    {
        self::$client = new Client([
            'base_uri' => self::$host,
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

    public static function getBlocks($limit = 100000, $page = 1){
        try{
            $res = self::$client->get(self::$endPointBlock.'?promotion='.self::$promotion_id.'&page='.$page.'&limit='.$limit);

            $blocks = self::jsonp_decode($res->getBody());

            $data = new stdClass;
            $data->data = $blocks->data->blocks;
            $data->next_page = $blocks->data->next_page;
            $data->prev_page = $blocks->data->prev_page;
            $data->page = $blocks->data->pages;

            return $data;

        }catch(ServerException $se){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(ClientException $clientException){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(ConnectException $ce){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(Exception $e){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }
    }
    
    public static function getRanking($block_id, $page = 1, $limit = 10)
    {
        try{
            $res = self::$client->get(self::$endPointBlockRanking.'?promotion='.self::$promotion_id.'&block_id='.$block_id.'&page='.$page.'&limit='.$limit);
            $ranking = self::jsonp_decode($res->getBody());

            $data = $ranking->ranking_blocks;
            $data->links = self::pagination($data->links);
            $ranking = new Collection($data->data);

            return ['data' => $ranking, 'pages' => $data->last_page,
                    'current_page' => $data->current_page, 'links' => $data->links,
                    'from' => $data->from, 'to' => $data->to, 'total' => 0];

        }catch(ServerException $se){
            return ['data' => [], 'pages' => 0, 'current_page' => 0, 'total' => 0];
        }catch(ClientException $clientException){
            return ['data' => [], 'pages' => 0, 'current_page' => 0, 'total' => 0];
        }catch(ConnectException $ce){
            return ['data' => [], 'pages' => 0, 'current_page' => 0, 'total' => 0];
        }catch(Exception $e){
            return ['data' => [], 'pages' => 0, 'current_page' => 0, 'total' => 0];
        }
    }

    public static function pagination($links){
        $links = Collection::make($links);

        $points = $links->map(function($item, $key){
            return $item->label == "...";
        })->reject(function($i){
            return $i == false;
        });

        $keys = $points->keys();

        $b = $links->countBy(function($l){
            return $l->url == null;
        });

        if($points->count() == 2){
            $links->splice(0, $keys[0] + 1);
            $keys[1] = $keys[1] - ($keys[0] + 1);

            $links->splice($keys[1], $links->count());
        }
        else if($points->count() == 1){
            $links->splice($keys[0], $links->count());
            $links->splice(0, 1);
        }else if($b->count() == 2){
            $links->splice(0, 1);
            $links->splice(-1, 1);
        }

        return $links;
    }

}
