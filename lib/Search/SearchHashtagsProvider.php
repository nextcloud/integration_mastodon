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
use OCA\Mastodon\Service\UtilsService;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class SearchHashtagsProvider implements IProvider {

	private IAppManager $appManager;
	private IL10N $l10n;
	private IConfig $config;
	private MastodonAPIService $mastodonAPIService;
	private IURLGenerator $urlGenerator;
	private UtilsService $utilsService;

	public function __construct(IAppManager        $appManager,
								IL10N              $l10n,
								IConfig            $config,
								IURLGenerator      $urlGenerator,
								UtilsService       $utilsService,
								MastodonAPIService     $mastodonAPIService) {
		$this->appManager = $appManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->mastodonAPIService = $mastodonAPIService;
		$this->urlGenerator = $urlGenerator;
		$this->utilsService = $utilsService;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'mastodon-search-hashtags';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Mastodon hashtags');
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

		$searchEnabled = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'search_hashtags_enabled', '1') === '1';
		if (!$searchEnabled) {
			return SearchResult::paginated($this->getName(), [], 0);
		}

		$searchResult = $this->mastodonAPIService->search($user->getUID(), $term, 'hashtags', $offset, $limit);
		if (isset($searchResult['error'])) {
			$items = [];
		} else {
			$items = $searchResult;
		}

		$formattedResults = array_map(function (array $entry): MastodonSearchResultEntry {
			[$rounded, $thumbnailUrl] = $this->getThumbnailUrl($entry);
			return new MastodonSearchResultEntry(
				$thumbnailUrl,
				$this->getMainText($entry),
				$this->getSubline($entry),
				$this->getLink($entry),
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
		return $entry['name'];
	}

	protected function getSubline(array $entry): string {
		if (isset($entry['history']) && is_array($entry['history']) && isset($entry['history'][0])) {
			return $this->l10n->t('Used %1$s times by %2$s accounts', [$entry['history'][0]['uses'], $entry['history'][0]['accounts']]);
		}
		return '';
	}

	protected function getLink(array $entry): string {
		return $entry['url'];
	}

	protected function getIconUrl(array $entry): string {
		return '';
	}

	protected function getThumbnailUrl(array $entry): array {
		$url = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => '#', 'size' => 44]);
		return [true, $url];
	}
}
