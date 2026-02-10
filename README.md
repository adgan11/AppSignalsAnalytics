# AppSignals Analytics

AppSignals Analytics is a self-hosted analytics platform for iOS apps, including:

- A Laravel backend API and dashboard (`backend/`)
- A Swift SDK for event tracking and crash reporting (`AppSignalsSDK/`)
- A sample iOS app integration (`AppSignalsDemo/`)

## Repository Structure

- `backend/`: Laravel 12 backend, ingestion APIs, dashboard, project management
- `AppSignalsSDK/`: Swift Package for iOS analytics/crash/session tracking
- `AppSignalsDemo/`: Example iOS app showing SDK usage
- `Documentation/`: Setup and deployment references

## Quick Start

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

### iOS SDK

Use Swift Package Manager:

```swift
dependencies: [
    .package(path: "../AppSignalsSDK")
]
```

Or host `AppSignalsSDK` in Git and reference it by URL.

## License

This project is licensed under the MIT License. See `LICENSE`.
