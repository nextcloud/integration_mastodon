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

use Datetime;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCP\IL10N;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\Http\Client\IClientService;

use OCA\Mastodon\AppInfo\Application;
use Throwable;

class MastodonAPIService {
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var IL10N
	 */
	private $l10n;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var \OCP\Http\Client\IClient
	 */
	private $client;

	/**
	 * Service to make requests to Mastodon v1 API
	 */
	public function __construct (string $appName,
								LoggerInterface $logger,
								IL10N $l10n,
								IConfig $config,
								IClientService $clientService) {
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->client = $clientService->newClient();
	}

	/**
	 * @param string $userId
	 * @return string
	 */
	public function getMastodonUrl(string $userId): string {
		$adminOauthUrl = $this->config->getAppValue(Application::APP_ID, 'oauth_instance_url');
		$mastodonUrl = $this->config->getUserValue($userId, Application::APP_ID, 'url', $adminOauthUrl) ?: $adminOauthUrl;
		if ($mastodonUrl !== '' && substr($mastodonUrl, 0, 4) !== 'http') {
			$mastodonUrl = 'https://' . $mastodonUrl;
		}
		return trim($mastodonUrl, "/ \n\r\t\v\x00");
	}

	/**
	 * @param string|null $userId
	 * @param ?int $since
	 * @return array
	 */
	public function getHomeTimeline(?string $userId, ?int $since = null): array {
		$params = [
			'limit' => 30
		];
		// get home timeline
		if (!is_null($since)) {
			$params['since_id'] = $since;
		}
		$home = $this->request($userId, 'timelines/home', $params);
		foreach ($home as $key => $value) {
			$home[$key]['type'] = 'home';
		}
		return $home;
	}

	/**
	 * @param string|null $userId
	 * @param ?int $since
	 * @return array
	 * @throws Exception
	 */
	public function getNotifications(?string $userId, ?int $since = null): array {
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
		$notifications = $this->request($userId, 'notifications', $params);

		// sort merged results by date
		usort($notifications, function($a, $b) {
			$a = new Datetime($a['created_at']);
			$ta = $a->getTimestamp();
			$b = new Datetime($b['created_at']);
			$tb = $b->getTimestamp();
			return ($ta > $tb) ? -1 : 1;
		});

		return $notifications;
	}

	/**
	 * @param string $avatarUrl
	 * @param string $mastodonUrl
	 * @param string $userId
	 * @return ?string
	 */
	public function getMastodonAvatar(string $avatarUrl, string $mastodonUrl, string $userId): ?string {
		// read or get instance avatar URL
		$instanceImageHostname = $this->config->getUserValue($userId, Application::APP_ID, 'instance_image_hostname');
		$instanceContactImageHostname = $this->config->getUserValue($userId, Application::APP_ID, 'instance_contact_image_hostname');

		// check the avatar hostname is the same as the account avatar one or the mastodon instance one
		$mUrl = parse_url($mastodonUrl);
		$aUrl = parse_url($avatarUrl);
		if ($aUrl['host'] === $instanceImageHostname
			|| $aUrl['host'] === $instanceContactImageHostname
			|| $aUrl['host'] === $mUrl['host']
		) {
			return $this->client->get($avatarUrl)->getBody();
		}
		return null;
	}

	/**
	 * @param string|null $userId
	 * @param string $query
	 * @param string|null $type
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public function search(?string $userId, string $query, ?string $type = null, int $offset = 0, int $limit = 5): array {
		$params = [
			'limit' => $limit,
			'offset' => $offset,
			'q' => $query,
		];
		if ($type !== null) {
			$params['type'] = $type;
		}
		$searchResult = $this->request($userId, 'search', $params, 'GET', 2);
		if ($type !== null) {
			return $searchResult[$type] ?? $searchResult;
		}
		if (!isset($searchResult['error'])) {
			$accounts = $searchResult['accounts'] ?? [];
			$accounts = array_map(static function (array $entry) {
				$entry['type'] = 'account';
				return $entry;
			}, $accounts);

			$statuses = $searchResult['statuses'] ?? [];
			$statuses = array_map(static function (array $entry) {
				$entry['type'] = 'status';
				return $entry;
			}, $statuses);

			$hashtags = $searchResult['hashtags'] ?? [];
			$hashtags = $searchResult['hashtags'] ?? [];
			$hashtags = array_map(static function (array $entry) {
				$entry['type'] = 'hashtag';
				return $entry;
			}, $hashtags);

			return array_merge($accounts, $statuses, $hashtags);
		}
		return $searchResult;
	}

	/**
	 * @param string $url
	 * @param string $redirect_uri
	 * @return array
	 */
	public function declareApp(string $url, string $redirect_uri): array {
		$params = [
			'client_name' => $this->l10n->t(Application::APP_ID, 'Nextcloud Mastodon integration app'),
			'redirect_uris' => $redirect_uri,
			'scopes' => 'read write follow',
			'website' => 'https://github.com/nextcloud/integration_mastodon'
		];
		return $this->anonymousRequest($url, 'apps', $params, 'POST');
	}

	/**
	 * @param string $url
	 * @param string $endPoint
	 * @param array $params
	 * @param string $method
	 * @return array
	 */
	public function anonymousRequest(string $url, string $endPoint, array $params = [], string $method = 'GET'): array {
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
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true);
			}
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Mastodon API error : '.$e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @param string|null $userId
	 * @param string $endPoint
	 * @param array $params
	 * @param string $method
	 * @param int $apiVersion
	 * @return array
	 */
	public function request(?string $userId, string $endPoint, array $params = [], string $method = 'GET', int $apiVersion = 1): array {
		$url = $this->getMastodonUrl($userId);
		$accessToken = $this->config->getUserValue($userId, Application::APP_ID, 'token');
		try {
			$url = $url . '/api/v' . $apiVersion . '/' . $endPoint;
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
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true);
			}
		} catch (ClientException | ServerException $e) {
			$responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			if ($e->getResponse()->getStatusCode() === 404) {
				$this->logger->debug('1Mastodon API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			} else {
				$this->logger->warning('2Mastodon API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			}
			return [
				'error' => $e->getMessage(),
				'body' => $parsedResponseBody,
			];
		} catch (Exception | Throwable $e) {
			$this->logger->warning('3Mastodon API error : '.$e, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @param string $mastodonUrl
	 * @param array $params
	 * @param string $method
	 * @return array
	 */
	public function requestOAuthAccessToken(string $mastodonUrl, array $params = [], string $method = 'GET'): array {
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
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('OAuth access token refused')];
			} else {
				return json_decode($body, true);
			}
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Mastodon OAuth error : '.$e, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
