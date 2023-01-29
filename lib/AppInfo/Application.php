<?php
/**
 * Nextcloud - Mastodon
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Mastodon\AppInfo;

use Closure;
use OCA\Mastodon\Service\MastodonAPIService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCA\Mastodon\Dashboard\MastodonWidget;
use OCA\Mastodon\Dashboard\MastodonHomeWidget;

/**
 * Class Application
 *
 * @package OCA\Mastodon\AppInfo
 */
class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_mastodon';
	/**
	 * @var mixed
	 */
	private $config;

	/**
	 * Constructor
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->config = $container->get(IConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerDashboardWidget(MastodonWidget::class);
		$context->registerDashboardWidget(MastodonHomeWidget::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerNavigation']));
	}

	public function registerNavigation(IUserSession $userSession, MastodonAPIService $mastodonAPIService): void {
		$user = $userSession->getUser();
		if ($user !== null) {
			$userId = $user->getUID();
			$container = $this->getContainer();

			if ($this->config->getUserValue($userId, self::APP_ID, 'navigation_enabled', '0') === '1') {
				$mastodonUrl = $mastodonAPIService->getMastodonUrl($userId);
				if ($mastodonUrl !== '') {
					$container->get(INavigationManager::class)->add(function () use ($container, $mastodonUrl) {
						$urlGenerator = $container->get(IURLGenerator::class);
						$l10n = $container->get(IL10N::class);
						return [
							'id' => self::APP_ID,
							'order' => 10,
							'href' => $mastodonUrl,
							'target' => '_blank',
							'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
							'name' => $l10n->t('Mastodon'),
						];
					});
				}
			}
		}
	}
}

