import Foundation
#if os(iOS)
import UIKit
#endif

/// SDK Configuration
final class AppSignalsConfiguration: @unchecked Sendable {
    
    // MARK: - API Configuration
    
    /// API key for authentication
    var apiKey: String = ""
    
    /// Server URL for the backend
    var serverURL: String = "http://localhost:8000"
    
    // MARK: - Identity
    
    /// Current user ID (nil if anonymous)
    var userId: String?
    
    /// Device ID (persisted in Keychain)
    lazy var deviceId: String = {
        if let existing = KeychainHelper.get(key: "appsignals_device_id") {
            return existing
        }
        let newId = UUID().uuidString
        KeychainHelper.set(key: "appsignals_device_id", value: newId)
        return newId
    }()
    
    /// Current session ID (regenerated each app launch)
    let sessionId: String = UUID().uuidString
    
    // MARK: - Feature Flags
    
    /// Enable automatic screen view tracking
    var enableAutoTracking: Bool = true
    
    /// Enable session replay capture
    var enableSessionReplay: Bool = false
    
    /// Enable crash reporting
    var enableCrashReporting: Bool = true
    
    // MARK: - Batching Configuration
    
    /// Maximum number of events before auto-flush
    var flushThreshold: Int = 50
    
    /// Maximum time (seconds) before auto-flush
    var flushInterval: TimeInterval = 30
    
    /// Maximum retry attempts for failed uploads
    var maxRetryAttempts: Int = 3
    
    // MARK: - Context
    
    /// App version from bundle
    var appVersion: String {
        Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "unknown"
    }
    
    /// App build number
    var appBuild: String {
        Bundle.main.infoDictionary?["CFBundleVersion"] as? String ?? "unknown"
    }
    
    /// SDK version
    let sdkVersion: String = "1.0.0"
    
    #if os(iOS)
    /// OS version
    var osVersion: String {
        UIDevice.current.systemVersion
    }
    
    /// OS name
    var osName: String {
        UIDevice.current.systemName
    }
    
    /// Device model
    var deviceModel: String {
        var systemInfo = utsname()
        uname(&systemInfo)
        let machineMirror = Mirror(reflecting: systemInfo.machine)
        let identifier = machineMirror.children.reduce("") { identifier, element in
            guard let value = element.value as? Int8, value != 0 else { return identifier }
            return identifier + String(UnicodeScalar(UInt8(value)))
        }
        return identifier
    }
    #else
    var osVersion: String { ProcessInfo.processInfo.operatingSystemVersionString }
    var osName: String { "macOS" }
    var deviceModel: String { "Mac" }
    #endif
}

