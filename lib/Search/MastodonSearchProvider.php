<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Mastodon\Search;

use OCA\Mastodon\Service\MastodonAPIService;
use OCA\Mastodon\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class MastodonSearchProvider implements IProvider {

	private IAppManager $appManager;
	private IL10N $l10n;
	private IConfig $config;
	private MastodonAPIService $mastodonAPIService;
	private IURLGenerator $urlGenerator;

	public function __construct(IAppManager        $appManager,
								IL10N              $l10n,
								IConfig            $config,
								IURLGenerator      $urlGenerator,
								MastodonAPIService     $mastodonAPIService) {
		$this->appManager = $appManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->mastodonAPIService = $mastodonAPIService;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'mastodon-search-multi';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Mastodon accounts, hashtags and statuses');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		if (strpos($route, Application::APP_ID . '.') === 0) {
			// Active app, prefer Mastodon results
			return -1;
		}

		return 20;
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		if (!$this->appManager->isEnabledForUser(Application::APP_ID, $user)) {
			return SearchResult::complete($this->getName(), []);
		}

		$limit = $query->getLimit();
		$term = $query->getTerm();
		$offset = $query->getCursor();
		$offset = $offset ? intval($offset) : 0;

		$searchEnabled = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'search_enabled', '0') === '1';
		if (!$searchEnabled) {
			return SearchResult::paginated($this->getName(), [], 0);
		}

		$searchResult = $this->mastodonAPIService->search($user->getUID(), $term, null, $offset, $limit);
		if (isset($searchResult['error'])) {
			$items = [];
		} else {
			$items = $searchResult;
		}

		$mastodonUrl = $this->mastodonAPIService->getMastodonUrl($user->getUID());
		$formattedResults = array_map(function (array $entry) use ($mastodonUrl): MastodonSearchResultEntry {
			[$rounded, $thumbnailUrl] = $this->getThumbnailUrl($entry);
			return new MastodonSearchResultEntry(
				$thumbnailUrl,
				$this->getMainText($entry),
				$this->getSubline($entry),
				$this->getLink($entry, $mastodonUrl),
				$this->getIconUrl($entry),
				$rounded
			);
		}, $items);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit
		);
	}

	protected function getMainText(array $entry): string {
		if ($entry['type'] === 'account') {
			if (isset($entry['display_name'])) {
				return '👤 ' . $entry['display_name'] . ' (' . $entry['acct'] . ')';
			} else {
				return '👤 ' . $entry['acct'];
			}
		} elseif ($entry['type'] === 'status') {
			if (isset($entry['content']) && $entry['content']) {
				return '💬 ' . $entry['content'];
			} elseif (isset($entry['reblog'], $entry['reblog']['content']) && $entry['reblog']['content']) {
				return '💬 ' . $entry['reblog']['content'];
			}
		} elseif ($entry['type'] === 'hashtag') {
			return $entry['name'];
		}
		return '';
	}

	protected function getSubline(array $entry): string {
		if ($entry['type'] === 'account') {
			return $entry['acct'];
		} elseif ($entry['type'] === 'status') {
			if (isset($entry['reblog'], $entry['reblog']['account'], $entry['reblog']['account']['acct']) && $entry['reblog']['account']['acct']) {
				return $entry['reblog']['account']['acct'] . ' (' . $this->l10n->t('Reblog from %1$s', [$entry['account']['acct']]) . ')';
			} elseif (isset($entry['account'], $entry['account']['display_name']) && $entry['account']['display_name']) {
				return $entry['account']['display_name'] . ' (' . $entry['account']['acct'] . ')';
			} else {
				return $entry['account']['acct'];
			}
		} elseif ($entry['type'] === 'hashtag') {
			if (isset($entry['history']) && is_array($entry['history']) && isset($entry['history'][0])) {
				return $this->l10n->t('Used %1$s times by %2$s accounts', [$entry['history'][0]['uses'], $entry['history'][0]['accounts']]);
			}
		}
		return '';
	}

	protected function getLink(array $entry, string $mastodonUrl): string {
		if ($entry['type'] === 'account') {
//			return $entry['url'];
			return $mastodonUrl . '/@' . $entry['acct'];
		} elseif ($entry['type'] === 'status') {
			return $mastodonUrl . '/@' . $entry['account']['acct'] . '/' . $entry['id'];
		} elseif ($entry['type'] === 'hashtag') {
			error_log('HHHHHHHHHHHHHH '.json_encode($entry));
			return $entry['url'];
		}
		return '';
	}

	protected function getIconUrl(array $entry): string {
		return '';
	}

	protected function getThumbnailUrl(array $entry): array {
		if ($entry['type'] === 'account') {
			$url = $this->urlGenerator->linkToRouteAbsolute(
				Application::APP_ID . '.mastodonAPI.getMastodonAvatar',
				['imageUrl' => $entry['avatar']]
			);
		} elseif ($entry['type'] === 'status') {
			$url = $this->urlGenerator->linkToRouteAbsolute(
				Application::APP_ID . '.mastodonAPI.getMastodonAvatar',
				['imageUrl' => $entry['account']['avatar']]
			);
		} elseif ($entry['type'] === 'hashtag') {
			$url = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => '#', 'size' => 44]);
		}
		return [true, $url];
	}
}
