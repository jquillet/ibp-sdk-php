<?php

namespace SdV\Ibp;

use SdV\Ibp\Exceptions\ApiException;
use SdV\Ibp\Resources\Error;
use Psr\Http\Message\ResponseInterface;

trait MakesHttpRequests
{
    /**
     * Make a GET request to Forge servers and return the response.
     *
     * @param  string $uri
     * @return mixed
     */
    private function get($uri, array $query = [])
    {
        return $this->request('GET', $uri, [
            'query' => $query,
        ]);
    }

    /**
     * Make a POST request to Forge servers and return the response.
     *
     * @param  string $uri
     * @param  array $payload
     * @return mixed
     */
    private function post($uri, array $payload = [])
    {
        return $this->request('POST', $uri, $payload);
    }

    /**
     * Make a PUT request to Forge servers and return the response.
     *
     * @param  string $uri
     * @param  array $payload
     * @return mixed
     */
    private function put($uri, array $payload = [])
    {
        return $this->request('PUT', $uri, $payload);
    }

    /**
     * Make a DELETE request to Forge servers and return the response.
     *
     * @param  string $uri
     * @param  array $payload
     * @return mixed
     */
    private function delete($uri, array $payload = [])
    {
        return $this->request('DELETE', $uri, $payload);
    }

    /**
     * Make a POST request for uploading file to IBP servers.
     *
     * @param  string $uri
     * @param  stream $contents
     * @param  array $payload
     * @return mixed
     */
    private function upload($uri, $contents, array $payload = [])
    {
        $params = [
            'headers' => [
                'X-Application-Id' => $this->applicationId,
                'Authorization' => 'Bearer '.$this->uploadToken,
                'Accept' => 'application/json',
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $contents
                ]
            ],
        ];

        if (!empty($payload)) {
            $params['form_params'] = $payload;
        }

        $response = $this->client->request('POST', $uri, $params);

        $statusCode = $response->getStatusCode();

        if (!in_array($statusCode, [200, 201])) {
            return $this->handleRequestError($response);
        }

        $responseBody = (string) $response->getBody();

        return json_decode($responseBody, true) ?: $responseBody;
    }

    /**
     * Make request to Forge servers and return the response.
     *
     * @param  string $verb
     * @param  string $uri
     * @param  array $payload
     * @return mixed
     */
    private function request($verb, $uri, array $payload = [])
    {
        $params = [
            'headers' => [
                'X-Application-Id' => $this->applicationId,
                'Authorization' => 'Bearer '.$this->applicationToken,
                'Accept' => 'application/json',
            ],
        ];

        $response = $this->client->request(
            $verb,
            $uri,
            empty($payload) ? $params : array_merge($params, ['form_params' => $payload])
        );

        $statusCode = $response->getStatusCode();

        if (!in_array($statusCode, [200, 201, 204])) {
            return $this->handleRequestError($response);
        }

        $responseBody = (string) $response->getBody();

        return json_decode($responseBody, true) ?: $responseBody;
    }

    /**
     * @param  \Psr\Http\Message\ResponseInterface $response
     * @return void
     */
    private function handleRequestError(ResponseInterface $response)
    {
        throw new ApiException(
            'IBP Api Error',
            $this->toError($response->getBody())
        );
    }

    private function toError(\GuzzleHttp\Psr7\Stream $stream)
    {
        return new Error(json_decode((string) $stream, true)['error']);
    }
}
