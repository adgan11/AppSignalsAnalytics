import Foundation

/// Replay Frame Models

struct ReplayPayload: Encodable {
    let sessionId: String
    let frames: [ReplayFrame]
    let context: ReplayContext
    
    enum CodingKeys: String, CodingKey {
        case sessionId = "session_id"
        case frames
        case context
    }
}

struct ReplayFrame: Encodable {
    let chunkIndex: Int
    let frameType: FrameType
    let timestamp: Double
    let wireframe: [String: Any]
    
    enum FrameType: String, Encodable {
        case full
        case delta
    }
    
    enum CodingKeys: String, CodingKey {
        case chunkIndex = "chunk_index"
        case frameType = "frame_type"
        case timestamp
        case wireframe
    }
    
    func encode(to encoder: Encoder) throws {
        var container = encoder.container(keyedBy: CodingKeys.self)
        try container.encode(chunkIndex, forKey: .chunkIndex)
        try container.encode(frameType, forKey: .frameType)
        try container.encode(timestamp, forKey: .timestamp)
        
        let encoded = wireframe.mapValues { AnyCodable($0) }
        try container.encode(encoded, forKey: .wireframe)
    }
}

struct ReplayContext: Encodable {
    let userId: String?
    
    enum CodingKeys: String, CodingKey {
        case userId = "user_id"
    }
}

#if os(iOS)
import UIKit

/// Session Replay Recorder - Captures UI wireframes
final class SessionReplayRecorder {
    
    static let shared = SessionReplayRecorder()
    
    private var isRecording = false
    private var frameIndex = 0
    private var frames: [ReplayFrame] = []
    private var timer: Timer?
    private var config: AppSignalsConfiguration?
    
    private let captureInterval: TimeInterval = 0.5  // 2 FPS
    private let maxFramesPerBatch = 50
    
    private init() {}
    
    func start(config: AppSignalsConfiguration) {
        guard !isRecording else { return }
        
        self.config = config
        self.isRecording = true
        self.frameIndex = 0
        self.frames = []
        
        // Start capture timer on main thread
        DispatchQueue.main.async {
            self.timer = Timer.scheduledTimer(
                withTimeInterval: self.captureInterval,
                repeats: true
            ) { [weak self] _ in
                self?.captureFrame()
            }
        }
        
        AppSignalsLogger.info("Session replay started")
    }
    
    func stop() {
        isRecording = false
        timer?.invalidate()
        timer = nil
        
        // Send remaining frames
        if !frames.isEmpty {
            sendFrames()
        }
        
        AppSignalsLogger.info("Session replay stopped")
    }
    
    private func captureFrame() {
        guard isRecording,
              let window = UIApplication.shared.connectedScenes
                .compactMap({ $0 as? UIWindowScene })
                .flatMap({ $0.windows })
                .first(where: { $0.isKeyWindow }) else {
            return
        }
        
        let wireframe = captureViewHierarchy(view: window)
        
        let frame = ReplayFrame(
            chunkIndex: frameIndex,
            frameType: frameIndex == 0 ? .full : .delta,
            timestamp: Date().timeIntervalSince1970,
            wireframe: wireframe
        )
        
        frames.append(frame)
        frameIndex += 1
        
        // Send batch if threshold reached
        if frames.count >= maxFramesPerBatch {
            sendFrames()
        }
    }
    
    private func captureViewHierarchy(view: UIView) -> [String: Any] {
        var node: [String: Any] = [
            "type": String(describing: type(of: view)),
            "frame": [
                "x": view.frame.origin.x,
                "y": view.frame.origin.y,
                "width": view.frame.size.width,
                "height": view.frame.size.height
            ],
            "hidden": view.isHidden,
            "alpha": view.alpha
        ]
        
        // Add specific properties for common view types
        if let label = view as? UILabel {
            node["text"] = redactText(label.text)
            node["textColor"] = colorToHex(label.textColor)
        } else if let button = view as? UIButton {
            node["title"] = redactText(button.currentTitle)
        } else if let imageView = view as? UIImageView {
            node["hasImage"] = imageView.image != nil
        } else if view is UITextField || view is UITextView {
            node["text"] = "***"  // Always redact input fields
        }
        
        // Recursively capture children
        if !view.subviews.isEmpty {
            node["children"] = view.subviews.map { captureViewHierarchy(view: $0) }
        }
        
        return node
    }
    
    private func redactText(_ text: String?) -> String {
        guard let text = text, !text.isEmpty else { return "" }
        
        // Redact potentially sensitive data
        let sensitivePatterns = [
            "\\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}\\b",  // Email
            "\\b\\d{4}[\\s-]?\\d{4}[\\s-]?\\d{4}[\\s-]?\\d{4}\\b",     // Credit card
            "\\b\\d{3}[\\s-]?\\d{2}[\\s-]?\\d{4}\\b",                   // SSN
        ]
        
        var result = text
        for pattern in sensitivePatterns {
            if let regex = try? NSRegularExpression(pattern: pattern) {
                let range = NSRange(result.startIndex..., in: result)
                result = regex.stringByReplacingMatches(in: result, range: range, withTemplate: "***")
            }
        }
        
        return result
    }
    
    private func colorToHex(_ color: UIColor?) -> String? {
        guard let color = color else { return nil }
        
        var r: CGFloat = 0, g: CGFloat = 0, b: CGFloat = 0, a: CGFloat = 0
        color.getRed(&r, green: &g, blue: &b, alpha: &a)
        
        return String(format: "#%02X%02X%02X", Int(r * 255), Int(g * 255), Int(b * 255))
    }
    
    private func sendFrames() {
        guard let config = config, !frames.isEmpty else { return }
        
        let framesToSend = frames
        frames = []
        
        Task {
            do {
                let networkClient = NetworkClient(config: config)
                try await networkClient.sendReplayFrames(
                    sessionId: config.sessionId,
                    frames: framesToSend
                )
                AppSignalsLogger.debug("Sent \(framesToSend.count) replay frames")
            } catch {
                AppSignalsLogger.error("Failed to send replay frames: \(error)")
            }
        }
    }
}

#endif
