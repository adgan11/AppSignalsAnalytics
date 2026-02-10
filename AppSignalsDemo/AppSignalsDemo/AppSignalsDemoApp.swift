import SwiftUI
import AppSignalsSDK

@main
struct AppSignalsDemoApp: App {
    
    init() {
        // Initialize AppSignals SDK
        AppSignals.initialize(
            apiKey: "ok_live_doqxiZ5CqDozKUVbdBNcKA8LCUsXwbnT",
            serverURL: "http://127.0.0.1:8000"
        )
        
        // Enable debug logging
        AppSignals.debugLogging = true
        
        // Enable features
        AppSignals.enableAutoTracking = true
        AppSignals.enableCrashReporting = true
        
        print("🟢 AppSignals SDK initialized")
    }
    
    var body: some Scene {
        WindowGroup {
            ContentView()
        }
    }
}
