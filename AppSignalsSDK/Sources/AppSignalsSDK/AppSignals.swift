import Foundation

/// AppSignals Analytics SDK - Main entry point
/// Thread-safe analytics for iOS apps
public final class AppSignals: @unchecked Sendable {
    
    // MARK: - Singleton
    
    /// Shared instance of AppSignals SDK
    public static let shared = AppSignals()
    
    // MARK: - Internal Components
    
    private let engine: AnalyticsEngine
    private let config: AppSignalsConfiguration
    private var isInitialized = false
    
    // MARK: - Initialization
    
    private init() {
        self.config = AppSignalsConfiguration()
        self.engine = AnalyticsEngine(config: config)
    }
    
    // MARK: - Public API
    
    /// Initialize the SDK with your API key
    /// - Parameters:
    ///   - apiKey: Your AppSignals API key (starts with `ok_live_` or `ok_test_`)
    ///   - serverURL: Optional custom server URL (defaults to your backend)
    public static func initialize(apiKey: String, serverURL: String? = nil) {
        shared.config.apiKey = apiKey
        if let url = serverURL {
            shared.config.serverURL = url
        }
        shared.isInitialized = true
        
        Task {
            await shared.engine.start()
        }

        #if os(iOS)
        if shared.config.enableAutoTracking {
            DispatchQueue.main.async {
                AutoTracker.shared.start()
            }
        }
        #endif

        if shared.config.enableCrashReporting {
            CrashHandler.shared.start(config: shared.config)
        }
        
        AppSignalsLogger.info("AppSignals SDK initialized")
    }
    
    /// Track a custom event
    /// - Parameters:
    ///   - name: Event name (e.g., "button_clicked", "purchase_completed")
    ///   - properties: Optional dictionary of event properties
    public static func track(_ name: String, properties: [String: Any]? = nil) {
        guard shared.isInitialized else {
            AppSignalsLogger.warning("SDK not initialized. Call AppSignals.initialize() first.")
            return
        }
        
        let event = AppSignalsEvent(
            name: name,
            properties: properties,
            timestamp: Date()
        )
        
        Task {
            await shared.engine.track(event: event)
        }
    }
    
    /// Identify the current user
    /// - Parameter userId: Unique user identifier from your system
    public static func identify(userId: String) {
        guard shared.isInitialized else {
            AppSignalsLogger.warning("SDK not initialized. Call AppSignals.initialize() first.")
            return
        }
        
        shared.config.userId = userId
        
        Task {
            await shared.engine.updateIdentity(userId: userId)
        }
        
        AppSignalsLogger.info("User identified: \(userId)")
    }
    
    /// Reset the current user (call on logout)
    public static func reset() {
        shared.config.userId = nil
        
        Task {
            await shared.engine.reset()
        }
        
        AppSignalsLogger.info("User reset")
    }
    
    /// Manually flush events to the server
    public static func flush() {
        Task {
            await shared.engine.flush()
        }
    }
    
    // MARK: - Configuration
    
    /// Enable or disable debug logging
    public static var debugLogging: Bool {
        get { AppSignalsLogger.isEnabled }
        set { AppSignalsLogger.isEnabled = newValue }
    }
    
    /// Enable or disable automatic screen tracking
    public static var enableAutoTracking: Bool {
        get { shared.config.enableAutoTracking }
        set {
            shared.config.enableAutoTracking = newValue
            guard shared.isInitialized else { return }
            #if os(iOS)
            if newValue {
                DispatchQueue.main.async {
                    AutoTracker.shared.start()
                }
            } else {
                AutoTracker.shared.stop()
            }
            #endif
        }
    }
    
    /// Enable or disable session replay
    public static var enableSessionReplay: Bool {
        get { shared.config.enableSessionReplay }
        set { shared.config.enableSessionReplay = newValue }
    }
    
    /// Enable or disable crash reporting
    public static var enableCrashReporting: Bool {
        get { shared.config.enableCrashReporting }
        set {
            shared.config.enableCrashReporting = newValue
            guard shared.isInitialized, newValue else { return }
            CrashHandler.shared.start(config: shared.config)
        }
    }
}
