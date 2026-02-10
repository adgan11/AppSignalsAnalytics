# Privacy and Data Retention Policy Template

This template is provided for convenience only and does not constitute legal advice.
Adapt it to your business, app, and jurisdiction.

## 1. Overview
- This policy explains what data is collected by the AppSignals installation and how it is used.
- "We", "us", and "our" refer to the operator of this AppSignals instance.

## 2. Data We Collect
- Analytics events (event names, timestamps, and optional properties you choose to send).
- Device context (OS version, device model, app version, app build, session ID).
- Crash reports (exception type, message, stack trace, and device context).
- Session replay wireframes (UI structure and layout metadata; no full screenshots).

## 3. Sensitive Data and Redaction
- Do not send passwords, payment details, or other sensitive data in custom event properties.
- Session replay redacts input fields and common sensitive patterns (emails, credit cards, and SSNs).
- You control which user identifier (if any) is sent as `user_id`.

## 4. Purpose of Processing
- Provide product analytics, crash monitoring, and session replay insights.
- Improve app stability and user experience.

## 5. Data Retention
- Data is stored for the retention period configured per project in the AppSignals dashboard.
- Older data is automatically deleted by scheduled cleanup jobs.
- You can shorten the retention period or purge data at any time in the dashboard settings.

## 6. Data Sharing and Access
- Data is stored in your infrastructure and is not shared with third parties by default.
- Access is restricted to authorized users of your AppSignals dashboard.

## 7. Security
- API keys are required to ingest data.
- Access to the dashboard is protected by user authentication.
- You are responsible for securing the server, database, and backups.

## 8. Your Responsibilities
- Inform your end users about analytics and crash reporting.
- Configure retention to meet your legal and contractual obligations.
- Provide a contact for privacy-related inquiries.

## 9. Contact
- Privacy contact email: `adgan.business@gmail.com`
