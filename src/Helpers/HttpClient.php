<?php

namespace Yshabanei\BugTracker\Helpers;
use GuzzleHttp\Client;

class HttpClient extends Client
{
    public function __construct()
    {
        $config = Config::get('app');
        parent::__construct(['base_url'=>$config['base_url']]);
    }
}