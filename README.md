# Stop Spammers for Piwigo

Improved anti-spam plugin for Piwigo comments and Contact Form.

This version extends the original Stop Spammers plugin with several modern protections while keeping the plugin lightweight and privacy-friendly.

---

# Features

## Existing protections

- StopForumSpam IP reputation checks
- Local cache for blocked IPs

## Additional protections

- HTTPS requests to StopForumSpam
- Honeypot protection for Contact Form
- Link count filtering
- Spam keyword detection
- Configurable whitelist
- Configurable cache duration
- Configurable StopForumSpam threshold
- Complete administration interface inside Piwigo

---

# Installation

1. Copy the plugin into:

```text
/plugins/stop_spammers/
```

2. Activate the plugin from the Piwigo administration panel.

3. Open:

```text
Administration → Plugins → Stop Spammers → Settings
```

4. Configure the plugin directly from the admin interface.

---

# Administration Interface

The plugin now includes a full settings page directly inside Piwigo.

Available settings:

| Setting | Description |
|---|---|
| StopForumSpam threshold | Spam confidence threshold |
| Cache duration | Duration of local IP blocking |
| Maximum links | Maximum links allowed before rejection |
| Spam keywords | Blocked keywords and expressions |
| IP whitelist | Trusted IP addresses |

No manual PHP configuration is required anymore.

---

# Honeypot Protection

The plugin automatically injects a hidden honeypot field into Contact Form templates.

Field name:

```text
website_url
```

Normal users never see this field, but many spambots automatically fill it.

If the field is filled, the message is immediately rejected.

No manual template modification is required.

---

# Link Filtering

The plugin can reject messages containing too many links.

Example:

```text
google.com apple.com microsoft.com
```

With a maximum link setting of `3`, the message is rejected.

The detection works with:
- https://example.com
- www.example.com
- example.com

---

# Keyword Filtering

Messages containing configured keywords can be rejected automatically.

Default examples:

```text
seo
marketing
backlinks
systeme.io
bit.ly
traffic
ai ads
```

Keyword matching is case-insensitive.

---

# StopForumSpam

The plugin queries:

```text
https://www.stopforumspam.com/
```

using the visitor IP address.

Blocked IPs are cached locally to reduce external requests.

IPv4 and IPv6 are both supported.

---

# Technical Notes

## Hooks used

```php
user_comment_check
contact_form_check
```

The plugin protects:
- native Piwigo comments
- Contact Form messages

provided that Contact Form triggers `contact_form_check`.

---

# Default Values

| Setting | Default |
|---|---|
| StopForumSpam threshold | 20 |
| Cache duration | 30 days |
| Maximum links | 2 |

---

# Compatibility

Tested with:
- modern Piwigo versions
- PHP 7+
- PHP 8+

---

# Philosophy

This plugin intentionally avoids:
- Google reCAPTCHA
- Cloudflare Turnstile
- external JavaScript dependencies

Goals:
- lightweight
- privacy-friendly
- simple to maintain
- no external frontend dependencies

---

# Troubleshooting

## Honeypot not working

Check that the generated HTML contains:

```html
<input type="text" name="website_url">
```

---

## False positives

Add the IP address to the whitelist in plugin settings.

---

## Link filtering seems too strict

Increase:
- Maximum links

from the administration panel.

---

## Spam still passes

Try:
- lowering the StopForumSpam threshold
- adding more spam keywords
- reducing maximum allowed links

---

# Credits

Original plugin by:
- Pierrick Le Gall (plg)

Improved edition:
- additional anti-spam protections
- administration interface
- modernized filtering system