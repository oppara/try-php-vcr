<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use VCR\VCR;

class VCRTest extends TestCase
{
    public const URL = 'https://www.google.com';

    /**
     * @test
     * @vcr file_get_contents
     */
    public function fielGetContentsRequest(): void
    {
        $expected = file_get_contents(static::URL);
        $this->assertSameBody($expected);
    }

    /**
     * @test
     * @vcr curl
     */
    public function curlRequest(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $expected = curl_exec($ch);
        curl_close($ch);

        $this->assertSameBody($expected);
    }

    /**
     * @test
     * @vcr guzzle
     */
    public function guzzleRequest(): void
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', static::URL);

        $this->assertSameBody((string)$response->getBody());
    }

    protected function getCasseteName(): string
    {
        $class = \get_class($this);
        $method = $this->getName(false);
        $reflection = new \ReflectionMethod($class, $method);
        $docBlock = $reflection->getDocComment();

        $tag = '@vcr';
        $regex = "/{$tag} (.*)(\\r\\n|\\r|\\n)/U";
        preg_match_all($regex, $docBlock, $matches);

        return $matches[1][0] ?? '';
    }

    protected function assertSameBody(string $expected): void
    {
        $cassetteName = $this->getCasseteName();
        $yamlObjs = new \VCR\Storage\Yaml(dirname(__DIR__) . '/fixtures', $cassetteName);
        $actual = [];
        foreach ($yamlObjs as $obj) {
            $actual[] = $obj;
        }

        $body = $actual[0]['response']['body'];
        $this->assertSame($expected, $body);
    }
}
