<template>
    <DashboardWidget :items="items"
          :showMore="true"
          @moreClicked="onMoreClick"
          :loading="state === 'loading'">
        <template v-slot:empty-content>
            <div v-if="state === 'no-token'">
                <a :href="settingsUrl">
                    {{ t('mastodon', 'Click here to configure the access to your Mastodon account.')}}
                </a>
            </div>
            <div v-else-if="state === 'error'">
                <a :href="settingsUrl">
                    {{ t('mastodon', 'Incorrect access token.') }}
                    {{ t('mastodon', 'Click here to configure the access to your Mastodon account.')}}
                </a>
            </div>
            <div v-else-if="state === 'ok'">
                {{ t('mastodon', 'Nothing to show') }}
            </div>
        </template>
    </DashboardWidget>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl, imagePath } from '@nextcloud/router'
import { DashboardWidget } from '@nextcloud/vue-dashboard'
import { showSuccess, showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { getLocale } from '@nextcloud/l10n'

export default {
    name: 'Dashboard',

    props: [],
    components: {
        DashboardWidget
    },

    beforeMount() {
        this.launchLoop()
    },

    mounted() {
    },

    data() {
        return {
            mastodonUrl: null,
            notifications: [],
            locale: getLocale(),
            loop: null,
            state: 'loading',
            settingsUrl: generateUrl('/settings/user/linked-accounts'),
            themingColor: OCA.Theming ? OCA.Theming.color.replace('#', '') : '0082C9',
        }
    },

    computed: {
        items() {
            return this.notifications.map((n) => {
                return {
                    id: this.getUniqueKey(n),
                    targetUrl: this.getNotificationTarget(n),
                    avatarUrl: this.getAuthorAvatarUrl(n),
                    //avatarUsername: '',
                    overlayIconUrl: this.getNotificationTypeImage(n),
                    mainText: this.getAuthorNameAndID(n),
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

    methods: {
        async launchLoop() {
            // get mastodon URL first
            try {
                const response = await axios.get(generateUrl('/apps/mastodon/url'))
                this.mastodonUrl = response.data.replace(/\/+$/, '')
            } catch (error) {
                console.log(error)
            }
            // then launch the loop
            this.fetchNotifications()
            this.loop = setInterval(() => this.fetchNotifications(), 30000)
        },
        fetchNotifications() {
            const req = {}
            req.params = {
                since: this.lastId
            }
            axios.get(generateUrl('/apps/mastodon/notifications'), req).then((response) => {
                this.processNotifications(response.data)
                this.state = 'ok'
            }).catch((error) => {
                clearInterval(this.loop)
                if (error.response && error.response.status === 400) {
                    this.state = 'no-token'
                } else if (error.response && error.response.status === 401) {
                    showError(t('mastodon', 'Failed to get Mastodon notifications.'))
                    this.state = 'error'
                } else {
                    // there was an error in notif processing
                    console.log(error)
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
            if (['favourite', 'mention', 'reblog'].includes(n.type)) {
                return n.status.url
            } else if (['follow'].includes(n.type)) {
                return n.account.url
            } else if (['follow_request'].includes(n.type)) {
                return this.mastodonUrl + '/web/follow_requests'
            }
            return ''
        },
        getSubline(n) {
            if (['favourite', 'mention', 'reblog'].includes(n.type)) {
                return this.html2text(n.status.content)
            } else if (n.type === 'follow') {
                return t('mastodon', 'is following you')
            } else if (n.type === 'follow_request') {
                return t('mastodon', 'wants to follow you')
            }
            return ''
        },
        getNotificationContent(n) {
            if (['favourite', 'mention', 'reblog'].includes(n.type)) {
                return this.html2text(n.status.content)
            } else if (n.type === 'follow') {
                return t('mastodon', '{name} is following you', {name: this.getAuthorNameAndID(n)})
            } else if (n.type === 'follow_request') {
                return t('mastodon', '{name} wants to follow you', {name: this.getAuthorNameAndID(n)})
            }
            return ''
        },
        html2text(s) {
            if (!s || s === '') {
                return ''
            }
            let temp = document.createElement('template')
            s = s.trim()
            temp.innerHTML = s
            return temp.content.firstChild.textContent
        },
        getUniqueKey(n) {
            return n.id
        },
        getAuthorAvatarUrl(n) {
            return (n.account && n.account.avatar) ?
                    generateUrl('/apps/mastodon/avatar?') + encodeURIComponent('url') + '=' + encodeURIComponent(n.account.avatar) :
                    ''
        },
        getNotificationTypeImage(n) {
            if (n.type === 'mention') {
                return generateUrl('/svg/core/actions/sound?color=' + this.themingColor)
            } else if (['follow', 'follow_request'].includes(n.type)) {
                return generateUrl('/svg/core/actions/toggle?color=' + this.themingColor)
            } else if (['favourite'].includes(n.type)) {
                return generateUrl('/svg/core/actions/starred?color=' + this.themingColor)
            } else if (['reblog'].includes(n.type)) {
                return generateUrl('/svg/core/actions/play-next?color=' + this.themingColor)
            }
            return ''
        },
        getFormattedDate(n) {
            return moment(n.created_at).locale(this.locale).format('LLL')
        },
        getAuthorNameAndID(n) {
            return n.account.display_name ?
                n.account.display_name + ' (' + n.account.acct + ')' :
                n.account.acct
        },
        onMoreClick() {
            const win = window.open(this.mastodonUrl + '/web/notifications', '_blank')
            win.focus()
        },
    },
}
</script>

<style scoped lang="scss">
</style>