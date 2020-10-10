<template>
	<DashboardWidget :items="items"
		:show-more-url="showMoreUrl"
		:show-more-text="title"
		:loading="state === 'loading'">
		<template v-slot:empty-content>
			<EmptyContent
				v-if="emptyContentMessage"
				:icon="emptyContentIcon">
				<template #desc>
					{{ emptyContentMessage }}
					<div v-if="state === 'no-token' || state === 'error'" class="connect-button">
						<a class="button" :href="settingsUrl">
							{{ t('integration_mastodon', 'Connect to Mastodon') }}
						</a>
					</div>
				</template>
			</EmptyContent>
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
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

export default {
	name: 'DashboardHome',

	components: {
		DashboardWidget, EmptyContent,
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
					overlayIconUrl: this.getNotificationTypeImage(n),
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
		emptyContentMessage() {
			if (this.state === 'no-token') {
				return t('integration_mastodon', 'No Mastodon account connected')
			} else if (this.state === 'error') {
				return t('integration_mastodon', 'Error connecting to Mastodon')
			} else if (this.state === 'ok') {
				return t('integration_mastodon', 'No Mastodon home toots!')
			}
			return ''
		},
		emptyContentIcon() {
			if (this.state === 'no-token') {
				return 'icon-mastodon'
			} else if (this.state === 'error') {
				return 'icon-close'
			} else if (this.state === 'ok') {
				return 'icon-checkmark'
			}
			return 'icon-checkmark'
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
			this.loop = setInterval(() => this.fetchNotifications(), 60000)
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
					showError(t('integration_mastodon', 'Failed to get Mastodon home timeline'))
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
			return n.reblog && n.reblog.account && n.reblog.account.acct
				? n.reblog.account.acct + ' (⮔ ' + n.account.acct + ')'
				: this.getAuthorNameAndID(n)
		},
		getMainText(n) {
			let text = this.getContent(n)
			while (text.startsWith('@')) {
				text = text.replace(/^@[^\s]*\s?/, '')
			}
			return text
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
			return temp.content.textContent
		},
		getUniqueKey(n) {
			return n.id
		},
		getAuthorAvatarUrl(n) {
			return n.reblog && n.reblog.account && n.reblog.account.avatar
				? generateUrl('/apps/integration_mastodon/avatar?') + encodeURIComponent('imageUrl') + '=' + encodeURIComponent(n.reblog.account.avatar)
				: (n.account && n.account.avatar)
					? generateUrl('/apps/integration_mastodon/avatar?') + encodeURIComponent('imageUrl') + '=' + encodeURIComponent(n.account.avatar)
					: ''
		},
		getNotificationTypeImage(n) {
			return n.reblog && n.reblog.account
				? generateUrl('/svg/integration_mastodon/retweet?color=ffffff')
				: ''
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
::v-deep .connect-button {
	margin-top: 10px;
}
</style>
