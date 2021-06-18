<?php


namespace Last1971\ChipDipParser;


use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;

class ChipDipParser
{
    /**
     * @var array|mixed
     */
    private array $config;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var Response
     */
    private Response $response;

    /**
     * ChipDipParser constructor.
     */
    public function __construct()
    {
        $this->config = require_once __DIR__ . '/../config/chipdip.php';
        if(file_exists(__DIR__ . '/../../../../config/chipdip.php')) {
            $appConfig = require __DIR__ . '/../../../../config/chipdip.php';
            $this->config = array_merge($this->config, $appConfig);
        }
        $jar = CookieJar::fromArray(
            [
                'TownId' => $this->config['town-id'],
            ],
            $this->config['main-url']
        );
        $this->client = new Client([
            'base_uri' => $this->config['protocol'] . $this->config['main-url'],
            'cookies' => $jar,
        ]);
    }

    /**
     * @param string $search
     * @return ChipDipParser
     * @throws GuzzleException
     */
    private function byNameRequest(string $search): ChipDipParser
    {
        try {
            $this->response = $this->client->request(
                'GET',
                $this->config['search-by-name'],
                [
                    'query' => [
                        'searchtext' => $search
                    ]
                ]
            );
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                $this->response = $e->getResponse();
            } else {
                throw $e;
            }
        }
        return $this;
    }

    /**
     * @param string $code
     * @return ChipDipParser
     * @throws GuzzleException
     */
    private function byCodeRequest(string $code): ChipDipParser
    {
        try {
            $this->response = $this->client->request(
                'GET',
                $this->config['search-by-code'] . '/' . $code
            );
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                $this->response = $e->getResponse();
            } else {
                throw $e;
            }
        }
        return $this;
    }

    /**
     * @throws InvalidSelectorException
     */
    private function parseCode(): array
    {
        $parser = new ChipDipProductParser($this->response->getBody()->getContents());
        return $parser();
    }

    /**
     * @return array
     * @throws InvalidSelectorException
     */
    private function parseCodes(): array
    {
        $document = new Document($this->response->getBody()->getContents());
        $lines = $document->find('.itemlist tr.with-hover');
        return array_map(function ($line) {
            usleep($this->config['ttl']);
            return $this->searchByCode(substr($line->attr('id'), 4));
        }, $lines);
    }

    /**
     * @param string $search
     * @return array
     * @throws GuzzleException
     * @throws InvalidSelectorException
     */
    public function searchByName(string $search): array
    {
        if (strlen($search) < 3) throw new Exception('Short search string');
        return $this
            ->byNameRequest($search)
            ->parseCodes();
    }

    /**
     * @param string $code
     * @return array
     * @throws GuzzleException
     * @throws InvalidSelectorException
     */
    public function searchByCode(string $code): array
    {
        return $this
            ->byCodeRequest($code)
            ->parseCode();
    }
}