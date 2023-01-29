<?php
/**
 * @copyright Copyright (c) 2020 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
 *
 */

namespace OCA\Mastodon\Dashboard;

use OCA\Mastodon\Service\MastodonAPIService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IWidget;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

use OCA\Mastodon\AppInfo\Application;

class MastodonHomeWidget implements IWidget {

	/** @var IL10N */
	private $l10n;
	/**
	 * @var IURLGenerator
	 */
	private $url;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IInitialState
	 */
	private $initialStateService;
	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var MastodonAPIService
	 */
	private $mastodonAPIService;

	public function __construct(IL10N $l10n,
								IConfig $config,
								MastodonAPIService $mastodonAPIService,
								IURLGenerator $url,
								IInitialState $initialStateService,
								?string $userId) {
		$this->l10n = $l10n;
		$this->url = $url;
		$this->config = $config;
		$this->initialStateService = $initialStateService;
		$this->userId = $userId;
		$this->mastodonAPIService = $mastodonAPIService;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'mastodon_home_timeline';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Mastodon home timeline');
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
	public function getIconClass(): string {
		return 'icon-mastodon';
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl(): ?string {
		return $this->url->linkToRoute('settings.PersonalSettings.index', ['section' => 'connected-accounts']);
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		$url = $this->mastodonAPIService->getMastodonUrl($this->userId);
		$oauthPossible = $url !== '';
		$usePopup = $this->config->getAppValue(Application::APP_ID, 'use_popup', '0');

		$userConfig = [
			'oauth_is_possible' => $oauthPossible,
			'use_popup' => ($usePopup === '1'),
			'url' => $url,
		];
		$this->initialStateService->provideInitialState('user-config', $userConfig);
		Util::addScript(Application::APP_ID, Application::APP_ID . '-dashboardHome');
		Util::addStyle(Application::APP_ID, 'dashboard');
	}
}
