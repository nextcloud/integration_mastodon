/**
 * Nextcloud - mastodon
 *
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2020
 */

import { linkTo } from '@nextcloud/router'
import { getCSPNonce } from '@nextcloud/auth'

__webpack_nonce__ = getCSPNonce() // eslint-disable-line
__webpack_public_path__ = linkTo('integration_mastodon', 'js/') // eslint-disable-line

document.addEventListener('DOMContentLoaded', () => {
	if (!OCA.Dashboard) {
		console.error('Mastodon dashboard widget should not be loaded outside of the dashboard page')
		return
	}

	OCA.Dashboard.register('mastodon_home_timeline', async (el, { widget }) => {
		const { createApp } = await import('vue')
		const { default: DashboardHome } = await import(/* webpackChunkName: "dashboard-home-lazy" */'./views/DashboardHome.vue')
		const app = createApp(
			DashboardHome,
			{
				title: widget.title,
			},
		)
		app.mixin({ methods: { t, n } })
		app.mount(el)
	})
})
