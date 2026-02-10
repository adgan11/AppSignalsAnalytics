import Foundation
#if canImport(CrashReporter)
import CrashReporter
#endif
#if canImport(Darwin)
import Darwin
#endif

/// Crash Report Model
struct CrashReport: Codable {
    let crashId: String
    let timestamp: Double
    let exceptionType: String
    let exceptionMessage: String?
    let stackTrace: String
    let context: CrashContext
    
    enum CodingKeys: String, CodingKey {
        case crashId = "crash_id"
        case timestamp
        case exceptionType = "exception_type"
        case exceptionMessage = "exception_message"
        case stackTrace = "stack_trace"
        case context
    }
}

struct CrashContext: Codable {
    let deviceId: String
    let osVersion: String
    let deviceModel: String
    let appVersion: String
    let appBuild: String?
    let userId: String?
    let sessionId: String?
    
    enum CodingKeys: String, CodingKey {
        case deviceId = "device_id"
        case osVersion = "os_version"
        case deviceModel = "device_model"
        case appVersion = "app_version"
        case appBuild = "app_build"
        case userId = "user_id"
        case sessionId = "session_id"
    }
}

/// Crash Handler - Captures uncaught exceptions and signals
final class CrashHandler: @unchecked Sendable {
    
    static let shared = CrashHandler()
    
    private var config: AppSignalsConfiguration?
    private var previousExceptionHandler: (@convention(c) (NSException) -> Void)?
    private var isStarted = false
    private var crashReportURL: URL?
    #if canImport(CrashReporter)
    private var crashReporter: PLCrashReporter?
    #endif
    
    private init() {}
    
    func start(config: AppSignalsConfiguration) {
        guard !isStarted else { return }
        isStarted = true

        self.config = config
        // Warm up crash report path and device ID before any crash occurs.
        crashReportURL = buildCrashReportURL()
        _ = config.deviceId
        
        // Store previous handler
        previousExceptionHandler = NSGetUncaughtExceptionHandler()
        
        // Set our handler
        NSSetUncaughtExceptionHandler { exception in
            CrashHandler.shared.handleException(exception)
        }
        
        // Set up PLCrashReporter for signal-based crash capture.
        setupCrashReporter()
        
        // Check for pending crash reports from previous run
        sendPendingCrashReports()
        
        AppSignalsLogger.info("Crash handler started")
    }
    
    private func handleException(_ exception: NSException) {
        let crashReport = CrashReport(
            crashId: UUID().uuidString,
            timestamp: Date().timeIntervalSince1970,
            exceptionType: exception.name.rawValue,
            exceptionMessage: exception.reason,
            stackTrace: exception.callStackSymbols.joined(separator: "\n"),
            context: buildContext()
        )
        
        // Save crash report to disk (we can't do async network here)
        saveCrashReport(crashReport)
        
        // Call previous handler if any
        previousExceptionHandler?(exception)
    }
    
    private func setupCrashReporter() {
        #if canImport(CrashReporter)
        let signalHandlerType: PLCrashReporterSignalHandlerType = {
            if isDebuggerAttached() {
                return PLCrashReporterSignalHandlerType(rawValue: 0) ?? .mach
            }
            return .mach
        }()
        let config = PLCrashReporterConfig(
            signalHandlerType: signalHandlerType,
            symbolicationStrategy: .all
        )
        guard let reporter = PLCrashReporter(configuration: config) else {
            AppSignalsLogger.warning("Could not create PLCrashReporter instance.")
            return
        }
        crashReporter = reporter

        do {
            try reporter.enableAndReturnError()
        } catch {
            AppSignalsLogger.warning("Failed to enable PLCrashReporter: \(error)")
        }
        #endif
    }

    private func isDebuggerAttached() -> Bool {
        #if canImport(Darwin)
        var info = kinfo_proc()
        var size = MemoryLayout<kinfo_proc>.stride
        var mib: [Int32] = [CTL_KERN, KERN_PROC, KERN_PROC_PID, getpid()]
        let result = sysctl(&mib, u_int(mib.count), &info, &size, nil, 0)
        return result == 0 && (info.kp_proc.p_flag & P_TRACED) != 0
        #else
        return false
        #endif
    }
    
    private func buildContext() -> CrashContext {
        guard let config = config else {
            return CrashContext(
                deviceId: "unknown",
                osVersion: "unknown",
                deviceModel: "unknown",
                appVersion: "unknown",
                appBuild: nil,
                userId: nil,
                sessionId: nil
            )
        }
        
        return CrashContext(
            deviceId: config.deviceId,
            osVersion: config.osVersion,
            deviceModel: config.deviceModel,
            appVersion: config.appVersion,
            appBuild: config.appBuild,
            userId: config.userId,
            sessionId: config.sessionId
        )
    }
    
    // MARK: - Persistence
    
    private func buildCrashReportURL() -> URL? {
        let fileManager = FileManager.default
        do {
            let appSupport = try fileManager.url(
                for: .applicationSupportDirectory,
                in: .userDomainMask,
                appropriateFor: nil,
                create: true
            )
            let appSignalsDir = appSupport.appendingPathComponent("AppSignals", isDirectory: true)
            try fileManager.createDirectory(at: appSignalsDir, withIntermediateDirectories: true)
            return appSignalsDir.appendingPathComponent("pending_crash.json")
        } catch {
            AppSignalsLogger.error("Failed to build crash report path: \(error)")
            return nil
        }
    }

    private func saveCrashReport(_ report: CrashReport) {
        guard let crashReportURL = crashReportURL else { return }
        do {
            let encoder = JSONEncoder()
            let data = try encoder.encode(report)
            try data.write(to: crashReportURL, options: .atomic)
        } catch {
            // Can't log here - might be mid-crash
        }
    }

    private func sendPendingCrashReports() {
        if sendPendingPLCrashReport() {
            return
        }

        guard let path = crashReportURL else { return }

        guard FileManager.default.fileExists(atPath: path.path),
              let config = config else {
            return
        }

        Task {
            do {
                let data = try Data(contentsOf: path)
                let decoder = JSONDecoder()
                let report = try decoder.decode(CrashReport.self, from: data)

                let networkClient = NetworkClient(config: config)
                try await networkClient.sendCrash(report: report)

                // Delete after successful upload
                try FileManager.default.removeItem(at: path)

                AppSignalsLogger.info("Sent pending crash report")
            } catch {
                AppSignalsLogger.error("Failed to send pending crash: \(error)")
            }
        }
    }

    private func sendPendingPLCrashReport() -> Bool {
        #if canImport(CrashReporter)
        guard let crashReporter = crashReporter,
              crashReporter.hasPendingCrashReport(),
              let config = config else {
            return false
        }

        Task {
            do {
                let data = try crashReporter.loadPendingCrashReportDataAndReturnError()
                let report = try PLCrashReport(data: data)
                let textReport = PLCrashReportTextFormatter.stringValue(
                    for: report,
                    with: PLCrashReportTextFormatiOS
                ) ?? "Unable to format crash report."

                let exceptionType = report.exceptionInfo?.exceptionName
                    ?? report.signalInfo?.name
                    ?? "Unknown"
                let exceptionMessage = report.exceptionInfo?.exceptionReason
                    ?? report.signalInfo?.code

                let timestamp = report.systemInfo.timestamp?.timeIntervalSince1970
                    ?? Date().timeIntervalSince1970

                let crashReport = CrashReport(
                    crashId: UUID().uuidString,
                    timestamp: timestamp,
                    exceptionType: exceptionType,
                    exceptionMessage: exceptionMessage,
                    stackTrace: textReport,
                    context: buildContext()
                )

                let networkClient = NetworkClient(config: config)
                try await networkClient.sendCrash(report: crashReport)

                if !crashReporter.purgePendingCrashReport() {
                    AppSignalsLogger.warning("Failed to purge PLCrashReporter pending crash.")
                }

                removeLegacyCrashReportIfNeeded()
                AppSignalsLogger.info("Sent PLCrashReporter crash report")
            } catch {
                AppSignalsLogger.error("Failed to process PLCrashReporter crash: \(error)")
            }
        }

        return true
        #else
        return false
        #endif
    }

    private func removeLegacyCrashReportIfNeeded() {
        guard let path = crashReportURL else { return }
        if FileManager.default.fileExists(atPath: path.path) {
            try? FileManager.default.removeItem(at: path)
        }

        let signalPath = path.deletingLastPathComponent().appendingPathComponent("pending_crash.signal")
        if FileManager.default.fileExists(atPath: signalPath.path) {
            try? FileManager.default.removeItem(at: signalPath)
        }
    }
}
