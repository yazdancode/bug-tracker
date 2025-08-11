<?php

namespace Yshabanei\BugTracker\contracts;

interface DatabaseConnetionInterface
{
    public function connect();

    public function getConnection();


}
