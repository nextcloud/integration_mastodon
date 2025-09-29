<?php

/**
 * Nextcloud - Mastodon
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2020
 */

return [
	'routes' => [
		['name' => 'config#setConfig', 'url' => '/config', 'verb' => 'PUT'],
		['name' => 'config#setSensitiveConfig', 'url' => '/sensitive-config', 'verb' => 'PUT'],
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'config#setSensitiveAdminConfig', 'url' => '/sensitive-admin-config', 'verb' => 'PUT'],
		['name' => 'config#oauthRedirect', 'url' => '/oauth-redirect', 'verb' => 'GET'],
		['name' => 'config#popupSuccessPage', 'url' => '/popup-success', 'verb' => 'GET'],

		['name' => 'mastodonAPI#getNotifications', 'url' => '/notifications', 'verb' => 'GET'],
		['name' => 'mastodonAPI#getHomeTimeline', 'url' => '/home', 'verb' => 'GET'],
		['name' => 'mastodonAPI#getMastodonUrl', 'url' => '/url', 'verb' => 'GET'],
		['name' => 'mastodonAPI#getMastodonAvatar', 'url' => '/avatar', 'verb' => 'GET'],
		['name' => 'mastodonAPI#declareApp', 'url' => '/oauth-app', 'verb' => 'POST'],
	]
];
