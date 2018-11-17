<?php
declare(strict_types = 1);

namespace ISTSI\Services;

use GuzzleHttp\Client;
use ISTSI\Exception\Exception;
use Psr\Container\ContainerInterface;

class Fenix
{
    protected $c;
    protected $client;
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $refreshToken;
    private $expirationTime;
    private $redirectUri;
    private $apiBaseUrl;

    public function __construct(ContainerInterface $c)
    {
        $this->c = $c;

        $settings = $this->c->get('settings')['fenix'];
        $this->clientId = $settings['clientId'];
        $this->clientSecret = $settings['clientSecret'];
        $this->redirectUri = $settings['redirectUri'];
        $this->apiBaseUrl = $settings['apiBaseUrl'];

        $this->client = new Client(['base_uri' => $this->apiBaseUrl]);

        $session = $this->c->get('session');

        if (isset($_SESSION['accessToken'])) {
            $this->accessToken = $_SESSION['accessToken'];
            $this->refreshToken = $_SESSION['refreshToken'];
            $this->expirationTime = $_SESSION['expires'];
        } else {
            $this->accessToken = $this->refreshToken = $this->expirationTime = null;
        }
    }

    private function get(string $endpoint, bool $public = false)
    {
        $response = $this->client->request('GET', '/api/fenix/v1/' . $endpoint, $public ? [] : [
            'query' => ['access_token' => $this->getAccessToken()]
        ]);

        $data = json_decode($response->getBody()->getContents());

        if ($response->getStatusCode() !== 200) {
            throw new Exception($data->error_description);
        }

        return $data;
    }

    private function post(string $endpoint, array $body, bool $public = false)
    {
        $response = $this->client->request('POST', $endpoint, [
            'query' => $public ? [] : ['access_token' => $this->getAccessToken()],
            'form_params' => $body
        ]);

        $data = json_decode($response->getBody()->getContents());

        if ($response->getStatusCode() !== 200) {
            throw new \Exception($data->error_description);
        }

        return $data;
    }

    public function getAuthUrl($state)
    {
        $query = http_build_query([
            'client_id'    => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state'        => $state
        ], '', '&');

        return $this->apiBaseUrl . '/oauth/userdialog?' . $query;
    }

    public function getAccessTokenFromCode(string $code)
    {
        $data = $this->post('/oauth/access_token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code,
            'grant_type'    => 'authorization_code'
        ], true);

        $this->accessToken = $_SESSION['accessToken'] = $data->access_token;
        $this->refreshToken = $_SESSION['refreshToken'] = $data->refresh_token;
        $this->expirationTime = $_SESSION['expires'] = time() + $data->expires_in;
    }

    private function getAccessToken()
    {
        if ($this->expirationTime <= time()) {
            $this->refreshAccessToken();
        }

        return $this->accessToken;
    }

    private function refreshAccessToken()
    {
        $data = $this->post('/oauth/refresh_token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken
        ]);

        $this->accessToken = $_SESSION['accessToken'] = $data->access_token;
        $this->expirationTime = $_SESSION['expires'] = time() + $data->expires;
    }

    private function getPerson()
    {
        return $this->get('person');
    }

    private function getCurriculum()
    {
        return $this->get('person/curriculum');
    }

    public function getUid()
    {
        return $this->getPerson()->username;
    }

    public function getName()
    {
        return $this->getPerson()->name;
    }

    public function getEmail()
    {
        return $this->getPerson()->email;
    }

    private function getCurrentCourse()
    {
        $curriculum = $this->getCurriculum();
        for ($i = count($curriculum) - 1; $i <= 0; --$i) {
            if (!$curriculum[$i]->isFinished) {
                return $curriculum[$i];
            }
        }
        return null;
    }

    public function getCourse()
    {
        $course = $this->getCurrentCourse();
        return $course->degree->acronym === '' ? 'ISOLATEDSUBJS' : $course->degree->acronym;
    }

    public function getYear()
    {
        $course = $this->getCurrentCourse();
        return $course->currentYear;
    }
}
