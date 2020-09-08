/* jshint esversion: 6 */

/**
 * Nextcloud - mastodon
 *
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

import Vue from 'vue'
import './bootstrap'
import DashboardHome from './views/DashboardHome'

document.addEventListener('DOMContentLoaded', function() {

	OCA.Dashboard.register('mastodon_home_timeline', (el, { widget }) => {
		const View = Vue.extend(DashboardHome)
		new View({
			propsData: { title: widget.title },
		}).$mount(el)
	})

})
