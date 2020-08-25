<?php
/**
 * Nextcloud - mastodon
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Mastodon\Service;

use OCP\IL10N;
use OCP\ILogger;
use OCP\Http\Client\IClientService;

use OCA\Mastodon\AppInfo\Application;

class MastodonAPIService {

    private $l10n;
    private $logger;

    /**
     * Service to make requests to Mastodon v1 API
     */
    public function __construct (
        string $appName,
        ILogger $logger,
        IL10N $l10n,
        IClientService $clientService
    ) {
        $this->appName = $appName;
        $this->l10n = $l10n;
        $this->logger = $logger;
        $this->clientService = $clientService;
        $this->client = $clientService->newClient();
    }

    public function getHomeTimeline($url, $accessToken, $since = null) {
        $params = [
            'limit' => 30
        ];
        // get home timeline
        if (!is_null($since)) {
            $params['since_id'] = $since;
        }
        $home = $this->request($url, $accessToken, 'timelines/home', $params);
        foreach ($home as $key => $value) {
            $home[$key]['type'] = 'home';
        }
        return $home;
    }

    public function getNotifications($url, $accessToken, $since = null) {
        $params = [
            'limit' => 30
        ];

        // get notifications
        if (!is_null($since)) {
            $params['since_id'] = $since;
        } else {
            unset($params['since_id']);
        }
        $params['exclude_types'] = ['poll'];
        $notifications = $this->request($url, $accessToken, 'notifications', $params);

        // sort merged results by date
        $a = usort($notifications, function($a, $b) {
            $a = new \Datetime($a['created_at']);
            $ta = $a->getTimestamp();
            $b = new \Datetime($b['created_at']);
            $tb = $b->getTimestamp();
            return ($ta > $tb) ? -1 : 1;
        });

        return $notifications;
    }

    public function getMastodonAvatar($url) {
        return $this->client->get($url)->getBody();
    }

    public function declareApp($url, $redirect_uris) {
        $params = [
            'client_name' => $this->l10n->t(Application::APP_ID, 'Nextcloud Mastodon integration app'),
            'redirect_uris' => $redirect_uris,
            'scopes' => 'read write follow',
            'website' => 'https://github.com/nextcloud/integration_mastodon'
        ];
        return $this->anonymousRequest($url, 'apps', $params, 'POST');
    }

    public function anonymousRequest($url, $endPoint, $params = [], $method = 'GET') {
        try {
            $url = $url . '/api/v1/' . $endPoint;
            $options = [
                'headers' => [
                    'User-Agent'  => 'Nextcloud Mastodon integration',
                ]
            ];

            if (count($params) > 0) {
                if ($method === 'GET') {
                    $paramsContent = http_build_query($params);
                    $url .= '?' . $paramsContent;
                } else {
                    $options['body'] = $params;
                }
            }

            if ($method === 'GET') {
                $response = $this->client->get($url, $options);
            } else if ($method === 'POST') {
                $response = $this->client->post($url, $options);
            } else if ($method === 'PUT') {
                $response = $this->client->put($url, $options);
            } else if ($method === 'DELETE') {
                $response = $this->client->delete($url, $options);
            }
            $body = $response->getBody();
            $respCode = $response->getStatusCode();

            if ($respCode >= 400) {
                return $this->l10n->t('Bad credentials');
            } else {
                return json_decode($body, true);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Mastodon API error : '.$e, array('app' => $this->appName));
            return $e;
        }
    }

    public function request($url, $accessToken, $endPoint, $params = [], $method = 'GET') {
        try {
            $url = $url . '/api/v1/' . $endPoint;
            $options = [
                'headers' => [
                    'Authorization'  => 'Bearer ' . $accessToken,
                    'User-Agent' => 'Nextcloud Mastodon integration',
                ]
            ];

            if (count($params) > 0) {
                if ($method === 'GET') {
                    // manage array parameters
                    $paramsContent = '';
                    foreach ($params as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $oneArrayValue) {
                                $paramsContent .= $key . '[]=' . urlencode($oneArrayValue) . '&';
                            }
                            unset($params[$key]);
                        }
                    }
                    $paramsContent .= http_build_query($params);
                    $url .= '?' . $paramsContent;
                } else {
                    $options['body'] = $params;
                }
            }

            if ($method === 'GET') {
                $response = $this->client->get($url, $options);
            } else if ($method === 'POST') {
                $response = $this->client->post($url, $options);
            } else if ($method === 'PUT') {
                $response = $this->client->put($url, $options);
            } else if ($method === 'DELETE') {
                $response = $this->client->delete($url, $options);
            }
            $body = $response->getBody();
            $respCode = $response->getStatusCode();

            if ($respCode >= 400) {
                return $this->l10n->t('Bad credentials');
            } else {
                return json_decode($body, true);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Mastodon API error : '.$e, array('app' => $this->appName));
            return $e;
        }
    }

    public function requestOAuthAccessToken($mastodonUrl, $params = [], $method = 'GET') {
        try {
            $url = $mastodonUrl . '/oauth/token';
            $options = [
                'headers' => [
                    'User-Agent'  => 'Nextcloud Mastodon integration',
                ]
            ];

            if (count($params) > 0) {
                if ($method === 'GET') {
                    $paramsContent = http_build_query($params);
                    $url .= '?' . $paramsContent;
                } else {
                    $options['body'] = $params;
                }
            }

            if ($method === 'GET') {
                $response = $this->client->get($url, $options);
            } else if ($method === 'POST') {
                $response = $this->client->post($url, $options);
            } else if ($method === 'PUT') {
                $response = $this->client->put($url, $options);
            } else if ($method === 'DELETE') {
                $response = $this->client->delete($url, $options);
            }
            $body = $response->getBody();
            $respCode = $response->getStatusCode();

            if ($respCode >= 400) {
                return $this->l10n->t('OAuth access token refused');
            } else {
                return json_decode($body, true);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Mastodon OAuth error : '.$e, array('app' => $this->appName));
            return $e;
        }
    }

}
