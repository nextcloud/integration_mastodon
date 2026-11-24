# Mastodon integration into Nextcloud

🐘 Put a Mastodon in your cloud!

This app provides dashboard widgets displaying your important Mastodon notifications and your home timeline.

## 🔧 Configuration

### Administrator settings

The default Mastodon instance address must be the instance root URL, for example
`https://mastodon.social`. Do not enter a profile URL such as
`https://mastodon.social/@username`: the integration appends OAuth and API paths
to this address, so a profile URL produces a `404 Not Found` error.

### User settings

The account configuration happens in the "Connected accounts" user settings section.
Enter the instance root URL there as well, not a profile URL.

A link to the "Connected accounts" user settings section will be displayed in the widget for users who didn't configure a Mastodon account.

## 🛠️ State of maintenance

While there are some things that could be done to further improve this app, the app is currently maintained with **limited effort**. This means:

* The main functionality works for the majority of the use cases
* We will ensure that the app will continue to work like this for future releases and we will fix bugs that we classify as 'critical'
* We will not invest further development resources ourselves in advancing the app with new features
* We do review and enthusiastically welcome community PR's

We would be more than excited if you would like to collaborate with us. We will merge pull requests for new features and fixes. We also would love to welcome co-maintainers.

If you are a customer of Nextcloud and you have a strong business case for any development of this app, we will consider your wishes for our roadmap. Please contact your account manager to talk about the possibilities.
