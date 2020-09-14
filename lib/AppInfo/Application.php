<?php
/**
 * Nextcloud - Mastodon
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Mastodon\AppInfo;

use OCP\IContainer;

use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCA\Mastodon\Controller\PageController;
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
	 * Constructor
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
	}

	public function register(IRegistrationContext $context): void {
		$context->registerDashboardWidget(MastodonWidget::class);
		$context->registerDashboardWidget(MastodonHomeWidget::class);
	}

	public function boot(IBootContext $context): void {
	}
}

