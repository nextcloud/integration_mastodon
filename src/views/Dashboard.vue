<template>
	<NcDashboardWidget :items="items"
		:show-more-url="showMoreUrl"
		:show-more-label="title"
		:loading="state === 'loading'">
		<template #empty-content>
			<NcEmptyContent v-if="emptyContentMessage"
				:title="emptyContentMessage">
				<template #icon>
					<component :is="emptyContentIcon" />
				</template>
				<template #action>
					<div v-if="state === 'no-token' || state === 'error'" class="connect-button">
						<a v-if="!initialState.oauth_is_possible"
							:href="settingsUrl">
							<NcButton>
								<template #icon>
									<LoginVariantIcon />
								</template>
								{{ t('integration_mastodon', 'Connect to Mastodon') }}
							</NcButton>
						</a>
						<NcButton v-else
							@click="onOauthClick">
							<template #icon>
								<LoginVariantIcon />
							</template>
							{{ t('integration_mastodon', 'Connect to {url}', { url: mastodonUrl }) }}
						</NcButton>
					</div>
				</template>
			</NcEmptyContent>
		</template>
	</NcDashboardWidget>
</template>

<script>
import LoginVariantIcon from 'vue-material-design-icons/LoginVariant.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'

import MastodonIcon from '../components/icons/MastodonIcon.vue'

import axios from '@nextcloud/axios'
import { generateUrl, imagePath } from '@nextcloud/router'
import NcDashboardWidget from '@nextcloud/vue/dist/Components/NcDashboardWidget.js'
import { showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { getLocale } from '@nextcloud/l10n'
import { loadState } from '@nextcloud/initial-state'

import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

import { oauthConnect, oauthConnectConfirmDialog, truncateString } from '../utils.js'

export default {
	name: 'Dashboard',

	components: {
		NcButton,
		NcDashboardWidget,
		NcEmptyContent,
		LoginVariantIcon,
		CloseIcon,
		CheckIcon,
		MastodonIcon,
	},

	props: {
		title: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			notifications: [],
			locale: getLocale(),
			loop: null,
			state: 'loading',
			settingsUrl: generateUrl('/settings/user/connected-accounts'),
			themingColor: OCA.Theming ? OCA.Theming.color.replace('#', '') : '0082C9',
			initialState: loadState('integration_mastodon', 'user-config'),
			windowVisibility: true,
		}
	},

	computed: {
		mastodonUrl() {
			return this.initialState?.url?.replace(/\/+$/, '')
		},
		showMoreUrl() {
			return this.mastodonUrl + '/web/notifications'
		},
		items() {
			return this.notifications.map((n) => {
				return {
					id: this.getUniqueKey(n),
					targetUrl: this.getNotificationTarget(n),
					avatarUrl: this.getAuthorAvatarUrl(n),
					avatarUsername: '',
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
				return t('integration_mastodon', 'No Mastodon notifications!')
			}
			return ''
		},
		emptyContentIcon() {
			if (this.state === 'no-token') {
				return MastodonIcon
			} else if (this.state === 'error') {
				return CloseIcon
			} else if (this.state === 'ok') {
				return CheckIcon
			}
			return CheckIcon
		},
	},

	watch: {
		windowVisibility(newValue) {
			if (newValue) {
				this.launchLoop()
			} else {
				this.stopLoop()
			}
		},
	},

	beforeDestroy() {
		document.removeEventListener('visibilitychange', this.changeWindowVisibility)
	},

	beforeMount() {
		this.launchLoop()
		document.addEventListener('visibilitychange', this.changeWindowVisibility)
	},

	mounted() {
	},

	methods: {
		onOauthClick() {
			oauthConnectConfirmDialog(this.mastodonUrl).then((result) => {
				if (result) {
					if (this.initialState.use_popup) {
						this.state = 'loading'
						oauthConnect(this.mastodonUrl, null, true)
							.then((data) => {
								this.stopLoop()
								this.launchLoop()
							})
					} else {
						oauthConnect(this.mastodonUrl, 'dashboard')
					}
				}
			})
		},
		changeWindowVisibility() {
			this.windowVisibility = !document.hidden
		},
		stopLoop() {
			clearInterval(this.loop)
		},
		launchLoop() {
			this.fetchNotifications()
			this.loop = setInterval(() => this.fetchNotifications(), 60000)
		},
		fetchNotifications() {
			const req = {}
			req.params = {
				since: this.lastId,
			}
			axios.get(generateUrl('/apps/integration_mastodon/notifications'), req).then((response) => {
				this.processNotifications(response.data)
				this.state = 'ok'
			}).catch((error) => {
				clearInterval(this.loop)
				if (error.response && error.response.status === 400) {
					this.state = 'no-token'
				} else if (error.response && error.response.status === 401) {
					showError(t('integration_mastodon', 'Failed to get Mastodon notifications'))
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
			// avoid mentions with no status
			return notifications.filter(n => n.type !== 'mention' || !!n.status)
		},
		getNotificationTarget(n) {
			console.warn(n)
			if (['favourite', 'mention', 'reblog', 'status'].includes(n.type)) {
				return this.mastodonUrl + '/@' + n.account?.acct + '/' + n.status?.id
			} else if (['follow'].includes(n.type)) {
				return this.mastodonUrl + '/@' + n.account?.acct
			} else if (['follow_request'].includes(n.type)) {
				return this.mastodonUrl + '/web/follow_requests'
			}
			return ''
		},
		getMainText(n) {
			if (['favourite', 'mention', 'reblog'].includes(n.type)) {
				if (n.status) {
					let text = this.html2text(n.status.content)
					while (text.startsWith('@')) {
						text = text.replace(/^@[^\s]*\s?/, '')
					}
					return text
				}
			} else if (n.type === 'follow') {
				return t('integration_mastodon', '{name} is following you', { name: this.getShortName(n) })
			} else if (n.type === 'follow_request') {
				return t('integration_mastodon', '{name} wants to follow you', { name: this.getShortName(n) })
			}
			return ''
		},
		getShortName(n) {
			return n.account.display_name
				? truncateString(n.account.display_name, 10)
				: truncateString(n.account.acct, 10)
		},
		getSubline(n) {
			return this.getAuthorNameAndID(n)
		},
		getAuthorNameAndID(n) {
			return n.account.display_name
				? n.account.display_name + ' (' + n.account.acct + ')'
				: n.account.acct
		},
		getNotificationContent(n) {
			if (['favourite', 'mention', 'reblog'].includes(n.type)) {
				return this.html2text(n.status.content)
			} else if (n.type === 'follow') {
				return t('integration_mastodon', '{name} is following you', { name: this.getAuthorNameAndID(n) })
			} else if (n.type === 'follow_request') {
				return t('integration_mastodon', '{name} wants to follow you', { name: this.getAuthorNameAndID(n) })
			}
			return ''
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
				? generateUrl('/apps/integration_mastodon/avatar?imageUrl={url}', { url: n.account.avatar })
				: ''
		},
		getNotificationTypeImage(n) {
			if (n.type === 'mention') {
				return imagePath('integration_mastodon', 'arobase.svg')
			} else if (['follow', 'follow_request'].includes(n.type)) {
				return imagePath('integration_mastodon', 'add_user.svg')
			} else if (['favourite'].includes(n.type)) {
				return imagePath('integration_mastodon', 'starred.svg')
			} else if (['reblog'].includes(n.type)) {
				return imagePath('integration_mastodon', 'retweet.svg')
			}
			return ''
		},
		getFormattedDate(n) {
			return moment(n.created_at).locale(this.locale).format('LLL')
		},
	},
}
</script>

<style scoped lang="scss">
::v-deep .connect-button {
	margin-top: 10px;
}
</style>
