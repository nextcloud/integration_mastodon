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
import { getRequestToken } from '@nextcloud/auth'

__webpack_nonce__ = btoa(getRequestToken()) // eslint-disable-line
__webpack_public_path__ = linkTo('integration_mastodon', 'js/') // eslint-disable-line

document.addEventListener('DOMContentLoaded', () => {
	OCA.Dashboard.register('mastodon_home_timeline', async (el, { widget }) => {
		const { default: Vue } = await import(/* webpackChunkName: "vue-lazy" */'vue')
		Vue.mixin({ methods: { t, n } })
		const { default: DashboardHome } = await import(/* webpackChunkName: "dashboard-home-lazy" */'./views/DashboardHome.vue')
		const View = Vue.extend(DashboardHome)
		new View({
			propsData: { title: widget.title },
		}).$mount(el)
	})
})
