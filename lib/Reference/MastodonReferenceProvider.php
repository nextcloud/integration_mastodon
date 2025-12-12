<?php

/**
 * @copyright Copyright (c) 2023 Julien Veyssier <julien-nc@posteo.net>
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mastodon\Reference;

use OCA\Mastodon\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\LinkReferenceProvider;
use OCP\IConfig;
use OCP\IL10N;

use OCP\IURLGenerator;

class MastodonReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	public function __construct(
		private IAppConfig $appConfig,
		private IConfig $config,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IReferenceManager $referenceManager,
		private LinkReferenceProvider $linkReferenceProvider,
		private ?string $userId,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'mastodon-multi';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		if ($this->userId !== null) {
			$searchStatusesEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_statuses_enabled', '1') === '1';
			$searchAccountsEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_accounts_enabled', '1') === '1';
			$searchTagsEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_hashtags_enabled', '1') === '1';
			if ($searchStatusesEnabled && $searchAccountsEnabled && $searchTagsEnabled) {
				return $this->l10n->t('Mastodon people, toots and hashtags');
			} elseif ($searchStatusesEnabled && $searchAccountsEnabled) {
				return $this->l10n->t('Mastodon people and toots');
			} elseif ($searchStatusesEnabled && $searchTagsEnabled) {
				return $this->l10n->t('Mastodon toots and hashtags');
			} elseif ($searchTagsEnabled && $searchAccountsEnabled) {
				return $this->l10n->t('Mastodon people and hashtags');
			} elseif ($searchTagsEnabled) {
				return $this->l10n->t('Mastodon hashtags');
			} elseif ($searchAccountsEnabled) {
				return $this->l10n->t('Mastodon people');
			} elseif ($searchStatusesEnabled) {
				return $this->l10n->t('Mastodon toots');
			}
			return $this->l10n->t('Mastodon');
		}
		return $this->l10n->t('Mastodon people, toots and hashtags');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedSearchProviderIds(): array {
		if ($this->userId !== null) {
			$searchProviderIds = [];
			$searchStatusesEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_statuses_enabled', '1') === '1';
			if ($searchStatusesEnabled) {
				$searchProviderIds[] = 'mastodon-search-statuses';
			}
			$searchAccountsEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_accounts_enabled', '1') === '1';
			if ($searchAccountsEnabled) {
				$searchProviderIds[] = 'mastodon-search-accounts';
			}
			$searchTagsEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_hashtags_enabled', '1') === '1';
			if ($searchTagsEnabled) {
				$searchProviderIds[] = 'mastodon-search-hashtags';
			}
			return $searchProviderIds;
		}
		return [
			'mastodon-search-statuses',
			'mastodon-search-accounts',
			'mastodon-search-hashtags',
		];
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		$adminLinkPreviewEnabled = $this->appConfig->getAppValueString('link_preview_enabled', '1', lazy: true) === '1';
		$userLinkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled || !$userLinkPreviewEnabled) {
			return false;
		}

		// never resolve mastodon
		// leave it to the linkReferenceProvider which does it pretty well
		return false;

		// link examples:
		// https://instance.org/@user/123456
		// https://instance.org/@user@instance.net/987654
		return preg_match('/^(?:https?:\/\/)?(?:www\.)?[^\/]+\/@[^\/@]+\/\d+/i', $referenceText) === 1
			|| preg_match('/^(?:https?:\/\/)?(?:www\.)?[^\/]+\/@[^\/@]+@[^\/@]+\/\d+/i', $referenceText) === 1;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$urlInfo = $this->getUrlInfo($referenceText);
			if ($urlInfo !== null) {
			}
			// fallback to opengraph
			return $this->linkReferenceProvider->resolveReference($referenceText);
		}

		return null;
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	private function getUrlInfo(string $url): ?array {
		preg_match('/^(?:https?:\/\/)?(?:www\.)?([^\/]+)\/@([^\/@]+)@([^\/@]+)\/(\d+)/i', $url, $matches);
		if (count($matches) > 4) {
			return [
				'instance' => $matches[1],
				'username' => $matches[2],
				'id' => (int)$matches[4],
			];
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?([^\/]+)\/@([^\/@]+)\/(\d+)/i', $url, $matches);
		if (count($matches) > 3) {
			return [
				'instance' => $matches[1],
				'username' => $matches[2],
				'id' => (int)$matches[4],
			];
		}

		return null;
	}

	/**
	 * We use the userId here because when connecting/disconnecting from the GitHub account,
	 * we want to invalidate all the user cache and this is only possible with the cache prefix
	 * @inheritDoc
	 */
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	/**
	 * We don't use the userId here but rather a reference unique id
	 * @inheritDoc
	 */
	public function getCacheKey(string $referenceId): ?string {
		return $referenceId;
	}

	/**
	 * @param string $userId
	 * @return void
	 */
	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
