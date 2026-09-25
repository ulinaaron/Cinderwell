# Cinderwell Members Portal — Client Guide

## How It Works

Cinderwell Members Portal creates a private area on your site. Only approved members can log in and see protected content.

## How Members Sign Up

### Option A: Public Sign-Up with Approval

Visitors see a "Register" link. They fill in the form, and you get notified by email. You review each registration in **Cinderwell > Members Portal > Pending** and approve or reject.

### Option B: Admin-Issued Logins

You create accounts manually from **Cinderwell > Members Portal > Members**. You can:
- Add one at a time from the admin form
- Bulk import via WP All Import
- Send a welcome email so the user can set their password

You can also enable **both** modes simultaneously.

## How to Protect Content

### Per-Page Protection

1. Open any page or post in the editor
2. In the right sidebar, check **"Members only"**
3. Save the page

Non-members see a teaser and a "Log in" link. Members see the full page.

### Partial Protection (Block)

Use the **Member Only** block:
1. Add the block in the editor
2. Place content inside it
3. Only logged-in members see what's inside

## How to Customize Emails

Go to **Cinderwell > Members Portal > Email Templates**.

There are 5 templates:
- **Welcome** — sent when an admin creates an account with the welcome option
- **Approval** — sent when a registration is approved
- **Rejection** — sent when a registration is rejected (can be turned off)
- **Admin Notification** — sent to you when someone registers
- **Password Setup** — sent with a link to set the initial password

Use placeholders like `{first_name}`, `{email}`, `{login_url}`, `{setup_url}`, `{approval_url}`, `{site_name}`.

Click **"Send test"** to preview any template.

## Common Tasks

| Task | Where |
|------|-------|
| Approve a registration | Members Portal > Pending > Approve |
| Reject a registration | Members Portal > Pending > Reject |
| Create a member manually | Members Portal > Members > Add New Member |
| Edit a member's info | Members Portal > Members > click their name |
| Change registration mode | Members Portal > General > Registration Mode |
| Customize email templates | Members Portal > Email Templates |

## FAQ

**Q: Can members reset their own password?**
A: Yes. The login form has a "Lost your password?" link that sends a reset email.

**Q: Can I import members from a CSV?**
A: Yes. Use WP All Import with the "Create Users" feature. Set the role to `cinderwell_member`.

**Q: What happens to pending members if I switch to "admin-issued only"?**
A: They stay in the pending list. You can still approve or reject them. New public registrations are blocked.
