import Foundation
import SQLite

/// Event persistence using SQLite (store-and-forward pattern)
actor EventPersistence {
    
    // MARK: - Properties
    
    private var db: Connection?
    
    // Table definitions
    private let events = Table("events")
    private let id = SQLite.Expression<String>("id")
    private let timestamp = SQLite.Expression<Double>("timestamp")
    private let eventType = SQLite.Expression<Int>("type")
    private let payload = SQLite.Expression<Data>("payload")
    private let status = SQLite.Expression<Int>("status")
    private let retryCount = SQLite.Expression<Int>("retry_count")
    
    // MARK: - Initialization
    
    func initialize() async {
        do {
            let dbPath = try getDatabasePath()
            db = try Connection(dbPath)
            try createTables()
            AppSignalsLogger.debug("Database initialized at: \(dbPath)")
        } catch {
            AppSignalsLogger.error("Failed to initialize database: \(error)")
        }
    }
    
    private func getDatabasePath() throws -> String {
        let fileManager = FileManager.default
        let appSupport = try fileManager.url(
            for: .applicationSupportDirectory,
            in: .userDomainMask,
            appropriateFor: nil,
            create: true
        )
        let appSignalsDir = appSupport.appendingPathComponent("AppSignals", isDirectory: true)
        try fileManager.createDirectory(at: appSignalsDir, withIntermediateDirectories: true)
        return appSignalsDir.appendingPathComponent("events.sqlite").path
    }
    
    private func createTables() throws {
        try db?.run(events.create(ifNotExists: true) { t in
            t.column(id, primaryKey: true)
            t.column(timestamp)
            t.column(eventType)
            t.column(payload)
            t.column(status, defaultValue: 0)
            t.column(retryCount, defaultValue: 0)
        })
        
        // Create index for faster queries
        try db?.run(events.createIndex(status, timestamp, ifNotExists: true))
    }
    
    // MARK: - Store Events
    
    func store(event: AppSignalsEvent) async {
        do {
            let encoder = JSONEncoder()
            encoder.dateEncodingStrategy = .iso8601
            let payloadData = try encoder.encode(event)
            
            try db?.run(events.insert(
                id <- event.eventId,
                timestamp <- event.timestamp.timeIntervalSince1970,
                eventType <- 0, // Track event
                payload <- payloadData,
                status <- 0,  // Pending
                retryCount <- 0
            ))
            
            AppSignalsLogger.debug("Event stored: \(event.eventId)")
        } catch {
            AppSignalsLogger.error("Failed to store event: \(error)")
        }
    }
    
    // MARK: - Retrieve Events
    
    func getPendingEvents(limit: Int = 100) async -> [AppSignalsEvent] {
        do {
            let query = events
                .filter(status == 0)  // Pending
                .filter(retryCount < 5)  // Max retries
                .order(timestamp.asc)
                .limit(limit)
            
            var results: [AppSignalsEvent] = []
            
            for row in try db!.prepare(query) {
                let payloadData = row[payload]
                let decoder = JSONDecoder()
                decoder.dateDecodingStrategy = .iso8601
                
                if let event = try? decoder.decode(AppSignalsEvent.self, from: payloadData) {
                    results.append(event)
                }
            }
            
            return results
        } catch {
            AppSignalsLogger.error("Failed to get pending events: \(error)")
            return []
        }
    }
    
    // MARK: - Update Status
    
    func markDelivered(eventId: String) async {
        do {
            let event = events.filter(id == eventId)
            try db?.run(event.delete())
            AppSignalsLogger.debug("Event delivered and removed: \(eventId)")
        } catch {
            AppSignalsLogger.error("Failed to mark event as delivered: \(error)")
        }
    }
    
    func markInFlight(eventIds: [String]) async {
        do {
            let query = events.filter(eventIds.contains(id))
            try db?.run(query.update(status <- 1))
        } catch {
            AppSignalsLogger.error("Failed to mark events as in-flight: \(error)")
        }
    }
    
    func incrementRetryCount(eventId: String) async {
        do {
            let event = events.filter(id == eventId)
            try db?.run(event.update(
                status <- 0,  // Reset to pending
                retryCount <- retryCount + 1
            ))
        } catch {
            AppSignalsLogger.error("Failed to increment retry count: \(error)")
        }
    }
    
    // MARK: - Cleanup
    
    func deleteOldEvents(olderThan days: Int = 7) async {
        do {
            let cutoff = Date().addingTimeInterval(-Double(days * 24 * 60 * 60)).timeIntervalSince1970
            let oldEvents = events.filter(timestamp < cutoff)
            let count = try db?.run(oldEvents.delete()) ?? 0
            AppSignalsLogger.debug("Deleted \(count) old events")
        } catch {
            AppSignalsLogger.error("Failed to delete old events: \(error)")
        }
    }
    
    func getEventCount() async -> Int {
        do {
            return try db?.scalar(events.count) ?? 0
        } catch {
            return 0
        }
    }
}
