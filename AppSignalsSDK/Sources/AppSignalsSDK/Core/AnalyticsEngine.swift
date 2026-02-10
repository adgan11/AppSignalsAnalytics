import Foundation

/// Thread-safe analytics engine using Swift Actor
actor AnalyticsEngine {
    
    // MARK: - Properties
    
    private let config: AppSignalsConfiguration
    private let persistence: EventPersistence
    private let networkClient: NetworkClient
    private var flushTimer: Task<Void, Never>?
    private var eventBuffer: [AppSignalsEvent] = []
    
    // MARK: - Initialization
    
    init(config: AppSignalsConfiguration) {
        self.config = config
        self.persistence = EventPersistence()
        self.networkClient = NetworkClient(config: config)
    }
    
    // MARK: - Lifecycle
    
    func start() async {
        // Initialize persistence
        await persistence.initialize()
        
        // Start flush timer
        startFlushTimer()
        
        // Retry any pending events from previous session
        await retryPendingEvents()
        
        AppSignalsLogger.debug("Analytics engine started")
    }
    
    func stop() async {
        flushTimer?.cancel()
        flushTimer = nil
        await flush()
    }
    
    // MARK: - Event Tracking
    
    func track(event: AppSignalsEvent) async {
        eventBuffer.append(event)
        
        AppSignalsLogger.debug("Event tracked: \(event.name)")
        
        // Flush if threshold reached
        if eventBuffer.count >= config.flushThreshold {
            await flush()
        }
    }
    
    func updateIdentity(userId: String) async {
        // Persist user ID
        UserDefaults.standard.set(userId, forKey: "appsignals_user_id")
    }
    
    func reset() async {
        UserDefaults.standard.removeObject(forKey: "appsignals_user_id")
    }
    
    // MARK: - Flushing
    
    func flush() async {
        guard !eventBuffer.isEmpty else { return }
        
        let eventsToSend = eventBuffer
        eventBuffer = []
        
        AppSignalsLogger.debug("Flushing \(eventsToSend.count) events")
        
        // Persist events first (store-and-forward)
        for event in eventsToSend {
            await persistence.store(event: event)
        }
        
        // Upload to server
        await uploadPendingEvents()
    }
    
    // MARK: - Private Methods
    
    private func startFlushTimer() {
        flushTimer = Task { [weak self] in
            guard let self = self else { return }
            
            while !Task.isCancelled {
                try? await Task.sleep(nanoseconds: UInt64(config.flushInterval * 1_000_000_000))
                await self.flush()
            }
        }
    }
    
    private func retryPendingEvents() async {
        await uploadPendingEvents()
    }
    
    private func uploadPendingEvents() async {
        // Get pending events from persistence
        let pendingEvents = await persistence.getPendingEvents()
        
        guard !pendingEvents.isEmpty else { return }
        
        // Build batch payload
        let batch = EventBatch(
            batchId: UUID().uuidString,
            sentAt: Date(),
            events: pendingEvents.map { event in
                BatchEvent(
                    eventId: event.eventId,
                    name: event.name,
                    timestamp: event.timestamp.timeIntervalSince1970,
                    properties: event.properties?.mapValues { $0.value }
                )
            },
            context: EventContext(
                deviceId: config.deviceId,
                sessionId: config.sessionId,
                userId: config.userId,
                osVersion: config.osVersion,
                deviceModel: config.deviceModel,
                appVersion: config.appVersion
            )
        )
        
        do {
            try await networkClient.sendEvents(batch: batch)
            
            // Mark as delivered
            for event in pendingEvents {
                await persistence.markDelivered(eventId: event.eventId)
            }
            
            AppSignalsLogger.info("Successfully uploaded \(pendingEvents.count) events")
        } catch {
            AppSignalsLogger.error("Failed to upload events: \(error.localizedDescription)")
            
            // Increment retry count
            for event in pendingEvents {
                await persistence.incrementRetryCount(eventId: event.eventId)
            }
        }
    }
}

// MARK: - Batch Models

struct EventBatch: Encodable {
    let batchId: String
    let sentAt: Date
    let events: [BatchEvent]
    let context: EventContext
    
    enum CodingKeys: String, CodingKey {
        case batchId = "batch_id"
        case sentAt = "sent_at"
        case events
        case context
    }
}

struct BatchEvent: Encodable {
    let eventId: String
    let name: String
    let timestamp: Double
    let properties: [String: Any]?
    
    enum CodingKeys: String, CodingKey {
        case eventId = "event_id"
        case name
        case timestamp
        case properties
    }
    
    func encode(to encoder: Encoder) throws {
        var container = encoder.container(keyedBy: CodingKeys.self)
        try container.encode(eventId, forKey: .eventId)
        try container.encode(name, forKey: .name)
        try container.encode(timestamp, forKey: .timestamp)
        
        if let props = properties {
            let encoded = props.mapValues { AnyCodable($0) }
            try container.encode(encoded, forKey: .properties)
        }
    }
}

struct EventContext: Encodable {
    let deviceId: String
    let sessionId: String
    let userId: String?
    let osVersion: String
    let deviceModel: String
    let appVersion: String
    
    enum CodingKeys: String, CodingKey {
        case deviceId = "device_id"
        case sessionId = "session_id"
        case userId = "user_id"
        case osVersion = "os_version"
        case deviceModel = "device_model"
        case appVersion = "app_version"
    }
}
