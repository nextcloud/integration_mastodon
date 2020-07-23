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

class MastodonAPIService {

    private $l10n;
    private $logger;

    /**
     * Service to make requests to Mastodon v1 API
     */
    public function __construct (
        string $appName,
        ILogger $logger,
        IL10N $l10n
    ) {
        $this->appName = $appName;
        $this->l10n = $l10n;
        $this->logger = $logger;
    }

    public function getNotifications($url, $accessToken, $since = null) {
        return [];
    }

    public function getMastodonAvatar($url) {
        return file_get_contents($url);
    }

    public function declareApp($url, $redirect_uris) {
        $params = [
            'client_name' => $this->l10n->t('mastodon', 'Nextcloud Mastodon integration app'),
            'redirect_uris' => $redirect_uris,
            'scopes' => 'read write follow',
            'website' => 'https://github.com/nextcloud/mastodon'
        ];
        return $this->anonymousRequest($url, 'apps', $params, 'POST');
    }

    public function anonymousRequest($url, $endPoint, $params = [], $method = 'GET') {
        try {
            $options = [
                'http' => [
                    'header'  => 'User-Agent: Nextcloud Mastodon integration',
                    'method' => $method,
                ]
            ];

            $url = $url . '/api/v1/' . $endPoint;
            if (count($params) > 0) {
                $paramsContent = http_build_query($params);
                if ($method === 'GET') {
                    $url .= '?' . $paramsContent;
                } else {
                    $options['http']['content'] = $paramsContent;
                }
            }

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            if (!$result) {
                return $this->l10n->t('Request error');
            } else {
                return json_decode($result, true);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Mastodon API error : '.$e, array('app' => $this->appName));
            return $e;
        }
    }

    public function request($url, $accessToken, $endPoint, $params = [], $method = 'GET') {
        try {
            $options = [
                'http' => [
                    'header'  => 'Authorization: Bearer ' . $accessToken .
                        "\r\nUser-Agent: Nextcloud Mastodon integration",
                    'method' => $method,
                ]
            ];

            $url = $url . '/api/v1/' . $endPoint;
            if (count($params) > 0) {
                $paramsContent = http_build_query($params);
                if ($method === 'GET') {
                    $url .= '?' . $paramsContent;
                } else {
                    $options['http']['content'] = $paramsContent;
                }
            }

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            if (!$result) {
                return $this->l10n->t('Bad credentials');
            } else {
                return json_decode($result, true);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Mastodon API error : '.$e, array('app' => $this->appName));
            return $e;
        }
    }

}
