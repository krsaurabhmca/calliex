# Green-API Integration Guide for CallDesk CRM

This guide explains how to connect and use [Green-API](https://green-api.com) for sending automated and manual WhatsApp messages directly from the CallDesk Admin Panel and Mobile App.

---

## 1. Getting Started with Green-API

1.  **Register/Login**: Go to [green-api.com](https://green-api.com) and create an account.
2.  **Create an Instance**:
    *   Once logged in, click "Create Instance".
    *   Choose a plan (they offer a free "Developer" plan for testing).
3.  **Scan QR Code**:
    *   After your instance is ready, you'll see a QR code.
    *   Open WhatsApp on your phone → Settings → Linked Devices → Link a Device.
    *   Scan the QR code to link your account to Green-API.
4.  **Copy Credentials**:
    *   From the Green-API Dashboard, copy the **Id Instance** and **Api Token Instance**.

---

## 2. Configuring CallDesk

1.  Login to your **CallDesk Admin Panel**.
2.  Navigate to **Settings** → **WhatsApp API** tab.
3.  Toggle the checkbox to **Activate Green-API Services**.
4.  Paste your **ID Instance** and **API Token Instance**.
5.  Keep the **API Host Base URL** as `https://api.green-api.com` (unless instructed otherwise by Green-API).
6.  Click **UPDATE WHATSAPP**.

---

## 3. Developer Documentation

The integration uses the `WhatsAppHelper` class located in `includes/whatsapp_helper.php`.

### Sending a basic text message:

```php
require_once 'includes/whatsapp_helper.php';

$whatsapp = new WhatsAppHelper($conn, $org_id);
$result = $whatsapp->sendMessage('919876543210', 'Hello from CallDesk!');

if ($result['success']) {
    echo "Message Sent! ID: " . $result['msgId'];
} else {
    echo "Error: " . $result['message'];
}
```

### Auto-Detect Number Formatting:
The helper automatically cleans numbers and prepends the country code if missing:
- `9876543210` → `919876543210` (India)
- `+91 98765-43210` → `919876543210`

---

## 4. Key Benefits
- **Reliability**: No need to keep the phone screen on or the app open.
- **Background Mode**: Send notifications or welcome messages automatically when a lead is added.
- **Reporting**: You can track instance status (Connected, Authorized, etc.) from the Green-API dashboard.

## 5. Troubleshooting
- **Not Sending?**: Ensure your instance status is `Authorized` on the Green-API dashboard.
- **HTTP 401 Error**: This means your API Token is incorrect.
- **HTTP 466 Error**: Your instance has run out of points (quota reached).
