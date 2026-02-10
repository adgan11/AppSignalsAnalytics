import Foundation

#if os(iOS)
import UIKit

/// Automatic event tracking for screen views and app lifecycle
final class AutoTracker {
    
    static let shared = AutoTracker()
    
    private var isEnabled = false
    private var observers: [NSObjectProtocol] = []
    
    private init() {}
    
    func start() {
        guard !isEnabled else { return }
        isEnabled = true
        
        // Track app lifecycle
        trackAppLifecycle()
        
        // Swizzle viewDidAppear for screen tracking
        swizzleViewDidAppear()
        
        AppSignalsLogger.info("Auto tracking enabled")
    }
    
    func stop() {
        isEnabled = false
        observers.forEach { NotificationCenter.default.removeObserver($0) }
        observers = []
    }
    
    // MARK: - App Lifecycle
    
    private func trackAppLifecycle() {
        let willEnterForeground = NotificationCenter.default.addObserver(
            forName: UIApplication.willEnterForegroundNotification,
            object: nil,
            queue: .main
        ) { _ in
            AppSignals.track("app_opened", properties: ["source": "background"])
        }
        
        let didEnterBackground = NotificationCenter.default.addObserver(
            forName: UIApplication.didEnterBackgroundNotification,
            object: nil,
            queue: .main
        ) { _ in
            AppSignals.track("app_backgrounded")
            AppSignals.flush()
        }
        
        let willTerminate = NotificationCenter.default.addObserver(
            forName: UIApplication.willTerminateNotification,
            object: nil,
            queue: .main
        ) { _ in
            AppSignals.track("app_terminated")
            AppSignals.flush()
        }
        
        observers.append(contentsOf: [willEnterForeground, didEnterBackground, willTerminate])
    }
    
    // MARK: - Screen Tracking (Method Swizzling)
    
    private static var hasSwizzled = false
    
    private func swizzleViewDidAppear() {
        guard !AutoTracker.hasSwizzled else { return }
        AutoTracker.hasSwizzled = true
        
        let originalSelector = #selector(UIViewController.viewDidAppear(_:))
        let swizzledSelector = #selector(UIViewController.appsignals_viewDidAppear(_:))
        
        guard let originalMethod = class_getInstanceMethod(UIViewController.self, originalSelector),
              let swizzledMethod = class_getInstanceMethod(UIViewController.self, swizzledSelector) else {
            return
        }
        
        method_exchangeImplementations(originalMethod, swizzledMethod)
    }
}

// MARK: - UIViewController Extension

extension UIViewController {
    
    @objc func appsignals_viewDidAppear(_ animated: Bool) {
        // Call original implementation
        appsignals_viewDidAppear(animated)
        
        // Skip system view controllers
        let className = String(describing: type(of: self))
        let skipPrefixes = ["UI", "_UI", "CK", "MF", "SF", "PK", "QL"]
        
        if skipPrefixes.contains(where: { className.hasPrefix($0) }) {
            return
        }
        
        // Track screen view
        let screenName = self.title ?? className
        AppSignals.track("screen_viewed", properties: [
            "screen_name": screenName,
            "screen_class": className
        ])
    }
}

#endif
