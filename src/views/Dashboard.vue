<template>
    <div>
        <ul v-if="state === 'ok'" class="notification-list">
            <li v-for="n in notifications"
                :key="getUniqueKey(n)"
                @mouseover="$set(hovered, getUniqueKey(n), true)" @mouseleave="$set(hovered, getUniqueKey(n), false)">
                <div class="popover-container">
                    <Popover :open="hovered[getUniqueKey(n)]" placement="top" class="content-popover" offset="40">
                        <template>
                            <div class="popover-author">
                                <Avatar
                                    class="popover-author-avatar"
                                    :size="24"
                                    :url="getAuthorAvatarUrl(n)"
                                    :tooltipMessage="n.account.display_name"
                                    />
                                <span class="popover-author-name">{{ getAuthorNameAndID(n) }}</span>
                            </div>
                            {{ getFormattedDate(n) }}
                            <br/>
                            <p>
                                {{ getNotificationContent(n) }}
                            </p>
                        </template>
                    </Popover>
                </div>
                <a :href="getNotificationTarget(n)" target="_blank" class="notification-list__entry">
                    <Avatar
                        class="author-avatar"
                        :url="getAuthorAvatarUrl(n)"
                        :tooltipMessage="n.account.display_name"
                        />
                    <img class="mastodon-notification-icon" :src="getNotificationTypeImage(n)"/>
                    <div class="notification__details">
                        <h3>
                            {{ getAuthorNameAndID(n) }}
                        </h3>
                        <p class="message">
                            {{ getNotificationContent(n) }}
                        </p>
                    </div>
                </a>
            </li>
        </ul>
        <div v-else-if="state === 'no-token'">
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
        <div v-else-if="state === 'loading'" class="icon-loading-small"></div>
    </div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl, imagePath } from '@nextcloud/router'
import { Avatar, Popover } from '@nextcloud/vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { getLocale } from '@nextcloud/l10n'

export default {
    name: 'Dashboard',

    props: [],
    components: {
        Avatar, Popover
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
            hovered: {},
        }
    },

    computed: {
        lastHomeId() {
            const nbNotif = this.notifications.length
            let i = 0
            while (i < nbNotif && this.notifications[i].type !== 'home') {
                i++
            }
            return (i < nbNotif) ? this.notifications[i].id : null
        },
        lastMentionId() {
            const nbNotif = this.notifications.length
            let i = 0
            while (i < nbNotif && this.notifications[i].type !== 'mention') {
                i++
            }
            return (i < nbNotif) ? this.notifications[i].id : null
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
                sinceHome: this.lastHomeId,
                sinceMention: this.lastMentionId
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
            if (this.lastHomeId || this.lastMentionId) {
                // just add those which are more recent than our most recent one
                let i = 0
                while (i < newNotifications.length && this.lastMoment.isBefore(newNotifications[i].created_at)) {
                    i++
                }
                if (i > 0) {
                    const toAdd = this.filter(newNotifications.slice(0, i))
                    this.notifications = toAdd.concat(this.notifications).slice(0, 7)
                }
            } else {
                // first time we don't check the date
                this.notifications = this.filter(newNotifications).slice(0, 7)
            }
        },
        filter(notifications) {
            // no filtering for the moment
            return notifications
        },
        getNotificationTarget(n) {
            if (n.type === 'home') {
                return n.url
            } else if (n.type === 'mention') {
                return n.status.url
            }
        },
        getNotificationContent(n) {
            if (n.type === 'home') {
                return this.html2text(n.content)
            } else if (n.type === 'mention') {
                return this.html2text(n.status.content)
            }
        },
        html2text(s) {
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
            if (n.type === 'home') {
                return generateUrl('/svg/core/places/home?color=' + this.themingColor)
            } else if (n.type === 'mention') {
                return generateUrl('/svg/core/actions/sound?color=' + this.themingColor)
            } else {
                return generateUrl('/svg/core/actions/sound?color=' + this.themingColor)
            }
        },
        getFormattedDate(n) {
            return moment(n.created_at).locale(this.locale).format('LLL')
        },
        getAuthorNameAndID(n) {
            return n.account.display_name ?
                n.account.display_name + ' (' + n.account.acct + ')' :
                n.account.acct
        },
    },
}
</script>

<style scoped lang="scss">
li .notification-list__entry {
    display: flex;
    align-items: flex-start;
    padding: 8px;

    &:hover,
    &:focus {
        background-color: var(--color-background-hover);
        border-radius: var(--border-radius-large);
    }
    .author-avatar {
        position: relative;
        margin-top: auto;
        margin-bottom: auto;
    }
    .notification__details {
        padding-left: 8px;
        max-height: 44px;
        flex-grow: 1;
        overflow: hidden;
        h3,
        .message {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .message span {
            width: 10px;
            display: inline-block;
            margin-bottom: -3px;
        }
        h3 {
            font-size: 100%;
            margin: 0;
        }
        .message {
            width: 100%;
            color: var(--color-text-maxcontrast);
        }
    }
    img.mastodon-notification-icon {
        position: absolute;
        width: 14px;
        height: 14px;
        margin: 27px 0 10px 24px;
    }
    button.primary {
        padding: 21px;
        margin: 0;
    }
}
.date-popover {
    position: relative;
    top: 7px;
}
.content-popover {
    height: 0px;
    width: 0px;
    margin-left: auto;
    margin-right: auto;
}
.popover-container {
    width: 100%;
    height: 0px;
}
.popover-author-name {
    vertical-align: top;
    line-height: 24px;
}
</style>