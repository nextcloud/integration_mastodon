<template>
	<DashboardWidget :items="items"
		:show-more-url="showMoreUrl"
		:show-more-text="title"
		:loading="state === 'loading'">
		<template v-slot:empty-content>
			<div v-if="state === 'no-token'">
				<a :href="settingsUrl">
					{{ t('integration_mastodon', 'Click here to configure the access to your Mastodon account.') }}
				</a>
			</div>
			<div v-else-if="state === 'error'">
				<a :href="settingsUrl">
					{{ t('integration_mastodon', 'Incorrect access token.') }}
					{{ t('integration_mastodon', 'Click here to configure the access to your Mastodon account.') }}
				</a>
			</div>
			<div v-else-if="state === 'ok'">
				{{ t('integration_mastodon', 'Nothing to show') }}
			</div>
		</template>
	</DashboardWidget>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { DashboardWidget } from '@nextcloud/vue-dashboard'
import { showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { getLocale } from '@nextcloud/l10n'

export default {
	name: 'DashboardHome',

	components: {
		DashboardWidget,
	},

	props: {
		title: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			mastodonUrl: null,
			notifications: [],
			locale: getLocale(),
			loop: null,
			state: 'loading',
			settingsUrl: generateUrl('/settings/user/connected-accounts'),
			themingColor: OCA.Theming ? OCA.Theming.color.replace('#', '') : '0082C9',
			darkThemeColor: OCA.Accessibility.theme === 'dark' ? 'ffffff' : '181818',
		}
	},

	computed: {
		showMoreUrl() {
			return this.mastodonUrl
		},
		items() {
			return this.notifications.map((n) => {
				return {
					id: this.getUniqueKey(n),
					targetUrl: this.getNotificationTarget(n),
					avatarUrl: this.getAuthorAvatarUrl(n),
					// avatarUsername: '',
					// overlayIconUrl: this.getNotificationTypeImage(n),
					mainText: this.getMainText(n),
					subText: this.getSubline(n),
				}
			})
		},
		lastId() {
			const nbNotif = this.notifications.length
			return (nbNotif > 0) ? this.notifications[0].id : null
		},
		lastDate() {
			const nbNotif = this.notifications.length
			return (nbNotif > 0) ? this.notifications[0].created_at : null
		},
		lastMoment() {
			return moment(this.lastDate)
		},
	},

	beforeMount() {
		this.launchLoop()
	},

	mounted() {
	},

	methods: {
		async launchLoop() {
			// get mastodon URL first
			try {
				const response = await axios.get(generateUrl('/apps/integration_mastodon/url'))
				this.mastodonUrl = response.data.replace(/\/+$/, '')
			} catch (error) {
				console.debug(error)
			}
			// then launch the loop
			this.fetchNotifications()
			this.loop = setInterval(() => this.fetchNotifications(), 30000)
		},
		fetchNotifications() {
			const req = {}
			req.params = {
				since: this.lastId,
			}
			axios.get(generateUrl('/apps/integration_mastodon/home'), req).then((response) => {
				this.processNotifications(response.data)
				this.state = 'ok'
			}).catch((error) => {
				clearInterval(this.loop)
				if (error.response && error.response.status === 400) {
					this.state = 'no-token'
				} else if (error.response && error.response.status === 401) {
					showError(t('integration_mastodon', 'Failed to get Mastodon home timeline.'))
					this.state = 'error'
				} else {
					// there was an error in notif processing
					console.debug(error)
				}
			})
		},
		processNotifications(newNotifications) {
			if (this.lastId) {
				// just add those which are more recent than our most recent one
				let i = 0
				while (i < newNotifications.length && this.lastMoment.isBefore(newNotifications[i].created_at)) {
					i++
				}
				if (i > 0) {
					const toAdd = this.filter(newNotifications.slice(0, i))
					this.notifications = toAdd.concat(this.notifications)
				}
			} else {
				// first time we don't check the date
				this.notifications = this.filter(newNotifications)
			}
		},
		filter(notifications) {
			// no filtering for the moment
			return notifications
		},
		getNotificationTarget(n) {
			return n.url
				? n.url
				: n.reblog && n.reblog.url
					? n.reblog.url
					: ''
		},
		getSubline(n) {
			return this.getAuthorNameAndID(n)
		},
		getMainText(n) {
			return this.getContent(n)
		},
		getContent(n) {
			return this.html2text(n.content) || t('integration_mastodon', 'No text content')
		},
		html2text(s) {
			if (!s || s === '') {
				return ''
			}
			const temp = document.createElement('template')
			s = s.trim()
			temp.innerHTML = s
			return temp.content.firstChild.textContent
		},
		getUniqueKey(n) {
			return n.id
		},
		getAuthorAvatarUrl(n) {
			return (n.account && n.account.avatar)
				? generateUrl('/apps/integration_mastodon/avatar?') + encodeURIComponent('url') + '=' + encodeURIComponent(n.account.avatar)
				: ''
		},
		getNotificationTypeImage(n) {
			return generateUrl('/svg/core/places/home?color=' + this.darkThemeColor)
		},
		getFormattedDate(n) {
			return moment(n.created_at).locale(this.locale).format('LLL')
		},
		getAuthorNameAndID(n) {
			return n.account.display_name
				? n.account.display_name + ' (' + n.account.acct + ')'
				: n.account.acct
		},
	},
}
</script>

<style scoped lang="scss">
</style>
