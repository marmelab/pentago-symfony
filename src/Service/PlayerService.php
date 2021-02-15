<?php

namespace App\Service;

class PlayerService
{
    // Return a unique hash in order to simulate something like an authentication.
    public function generatePlayerHash(): string
    {
        return hash('sha256', uniqid(), false);
    }
}
