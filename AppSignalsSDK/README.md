# AppSignals SDK for iOS

Lightweight, privacy-first analytics for iOS apps.

## Installation

### Swift Package Manager

You can add the SDK as a local package or host it in your own Git repository.

Local package example:

```swift
dependencies: [
    .package(path: "../AppSignalsSDK")
]
```

If you host it in Git, replace the URL with your repository:

```swift
dependencies: [
    .package(url: "https://your-git-host/AppSignalsSDK.git", from: "1.0.0")
]
```

In Xcode: File → Add Package Dependencies → Choose the local folder or enter your repository URL.

Current SDK version: 1.0.0 (tag: v1.0.0)

See `AppSignalsSDK/CHANGELOG.md` and `AppSignalsSDK/RELEASE_NOTES.md` for release history.

## Quick Start

### 1. Initialize the SDK

In your `AppDelegate.swift` or `@main` App struct:

```swift
import AppSignalsSDK

@main
struct YourApp: App {
    init() {
        AppSignals.initialize(
            apiKey: "ok_live_your_api_key_here",
            serverURL: "https://your-appsignals-domain.com"
        )
        
        // Optional: Enable debug logging
        AppSignals.debugLogging = true
    }
    
    var body: some Scene {
        WindowGroup {
            ContentView()
        }
    }
}
```

### 2. Track Events

```swift
// Simple event
AppSignals.track("button_clicked")

// Event with properties
AppSignals.track("purchase_completed", properties: [
    "product_id": "pro_subscription",
    "price": 9.99,
    "currency": "USD"
])
```

### 3. Identify Users

```swift
// After user login
AppSignals.identify(userId: "user_12345")

// After logout
AppSignals.reset()
```

## Configuration Options

```swift
// Enable automatic screen tracking
AppSignals.enableAutoTracking = true

// Enable session replay (captures UI wireframes)
AppSignals.enableSessionReplay = true

// Enable crash reporting
AppSignals.enableCrashReporting = true
```

## Privacy

This SDK is designed with privacy in mind:

- ✅ No third-party tracking
- ✅ GDPR/CCPA compliant
- ✅ PII automatically redacted from session replays
- ✅ Apple Privacy Manifest included

## License

MIT
