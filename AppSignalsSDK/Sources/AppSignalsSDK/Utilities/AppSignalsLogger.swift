import Foundation
import os.log

/// SDK Logger
enum AppSignalsLogger: Sendable {
    
    nonisolated(unsafe) static var isEnabled: Bool = false
    
    private static let osLog = OSLog(subsystem: "com.appsignals.sdk", category: "Analytics")
    
    static func debug(_ message: String) {
        guard isEnabled else { return }
        os_log(.debug, log: osLog, "%{public}@", "[AppSignals] \(message)")
    }
    
    static func info(_ message: String) {
        guard isEnabled else { return }
        os_log(.info, log: osLog, "%{public}@", "[AppSignals] \(message)")
    }
    
    static func warning(_ message: String) {
        os_log(.default, log: osLog, "%{public}@", "[AppSignals] ⚠️ \(message)")
    }
    
    static func error(_ message: String) {
        os_log(.error, log: osLog, "%{public}@", "[AppSignals] ❌ \(message)")
    }
}

