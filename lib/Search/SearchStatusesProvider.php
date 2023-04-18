<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Julien Veyssier
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
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
use OCA\Mastodon\Service\UtilsService;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class SearchStatusesProvider implements IProvider {

	public function __construct(private IAppManager        $appManager,
								private IL10N              $l10n,
								private IConfig            $config,
								private IURLGenerator      $urlGenerator,
								private UtilsService       $utilsService,
								private MastodonAPIService $mastodonAPIService) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'mastodon-search-statuses';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Mastodon toots');
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

		$searchEnabled = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'search_statuses_enabled', '1') === '1';
		if (!$searchEnabled) {
			return SearchResult::paginated($this->getName(), [], 0);
		}

		$searchResult = $this->mastodonAPIService->search($user->getUID(), $term, 'statuses', $offset, $limit);
		if (isset($searchResult['error'])) {
			$items = [];
		} else {
			$items = $searchResult;
		}

		$mastodonUrl = $this->mastodonAPIService->getMastodonUrl($user->getUID());
		$formattedResults = array_map(function (array $entry) use ($mastodonUrl): SearchResultEntry {
			[$rounded, $thumbnailUrl] = $this->getThumbnailUrl($entry);
			return new SearchResultEntry(
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
		$content = '';
		if (isset($entry['content']) && $entry['content']) {
			$content = $this->utilsService->html2text($entry['content']);
		} elseif (isset($entry['reblog'], $entry['reblog']['content']) && $entry['reblog']['content']) {
			$content = $this->utilsService->html2text($entry['reblog']['content']);
		}
		return '💬 ' . $content;
	}

	protected function getSubline(array $entry): string {
		if (isset($entry['reblog'], $entry['reblog']['account'], $entry['reblog']['account']['acct']) && $entry['reblog']['account']['acct']) {
			return $entry['reblog']['account']['acct'] . ' (' . $this->l10n->t('Reblog from %1$s', [$entry['account']['acct']]) . ')';
		} elseif (isset($entry['account'], $entry['account']['display_name']) && $entry['account']['display_name']) {
			return $entry['account']['display_name'] . ' (' . $entry['account']['acct'] . ')';
		}
		return $entry['account']['acct'];
	}

	protected function getLink(array $entry, string $mastodonUrl): string {
		return $mastodonUrl . '/@' . $entry['account']['acct'] . '/' . $entry['id'];
	}

	protected function getIconUrl(array $entry): string {
		return '';
	}

	protected function getThumbnailUrl(array $entry): array {
		$url = $this->urlGenerator->linkToRouteAbsolute(
			Application::APP_ID . '.mastodonAPI.getMastodonAvatar',
			['imageUrl' => $entry['account']['avatar']]
		);
		return [true, $url];
	}
}
