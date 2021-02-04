<?php

namespace App\Mercure;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

class JwtProvider
{
    private $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function __invoke(): string
    {

        $key = Key\InMemory::plainText($this->secret);
        $configuration = Configuration::forSymmetricSigner(new Sha256(), $key);

        $token = $configuration->builder()
            ->withClaim('mercure', ['subscribe' => ["*"], 'publish' => ["*"]]) // can also be a URI template, or *
            ->getToken($configuration->signer(), $configuration->signingKey())
            ->toString();

        return $token;
    }
}
