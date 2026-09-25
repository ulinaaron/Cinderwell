# Email Placeholders

Available in all email template subjects and bodies:

| Placeholder | Description |
|-------------|-------------|
| `{first_name}` | Recipient's first name |
| `{last_name}` | Recipient's last name |
| `{email}` | Recipient's email address |
| `{site_name}` | Your site name (from Settings > General) |
| `{login_url}` | WordPress login URL |
| `{setup_url}` | Password setup link (for new admin-issued accounts) |
| `{approval_url}` | Admin link to the pending registrations page |

## Usage

Placeholders are replaced at send time. They work in both the subject line and body of any email template.

**Example subject:** `Welcome to {site_name}, {first_name}!`
**Example body:**
```
Hi {first_name},

Your account has been approved. Log in here: {login_url}

Thanks,
{site_name}
```

## Custom Placeholders

Register custom placeholders via the `cinderwell_portal_email_vars` filter:

```php
add_filter('cinderwell_portal_email_vars', function ($vars) {
    $vars['custom_field'] = 'Custom value';
    return $vars;
});
```

Then use `{custom_field}` in your templates.

See [EXTENDING.md](EXTENDING.md) for more details.
