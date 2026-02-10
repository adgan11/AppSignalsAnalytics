import Foundation
#if canImport(Compression)
import Compression
#endif

/// Network client for sending events to the backend
final class NetworkClient: @unchecked Sendable {
    
    // MARK: - Properties
    
    private let config: AppSignalsConfiguration
    private let session: URLSession
    private let baseURL: URL
    
    // MARK: - Initialization
    
    init(config: AppSignalsConfiguration) {
        self.config = config
        self.baseURL = URL(string: config.serverURL)!
        
        let sessionConfig = URLSessionConfiguration.default
        sessionConfig.timeoutIntervalForRequest = 30
        sessionConfig.timeoutIntervalForResource = 60
        sessionConfig.waitsForConnectivity = true
        
        self.session = URLSession(configuration: sessionConfig)
    }
    
    // MARK: - API Endpoints
    
    enum Endpoint: String {
        case ingest = "/api/v1/ingest"
        case crash = "/api/v1/crash"
        case replay = "/api/v1/replay"
    }
    
    // MARK: - Send Events
    
    func sendEvents(batch: EventBatch) async throws {
        let url = baseURL.appendingPathComponent(Endpoint.ingest.rawValue)
        
        let encoder = JSONEncoder()
        encoder.dateEncodingStrategy = .iso8601
        let jsonData = try encoder.encode(batch)
        
        // Compress with GZIP
        let compressedData = try compress(data: jsonData)
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.httpBody = compressedData
        request.setValue(config.apiKey, forHTTPHeaderField: "X-API-Key")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("gzip", forHTTPHeaderField: "Content-Encoding")
        request.setValue("AppSignals-Swift-SDK/\(config.sdkVersion)", forHTTPHeaderField: "User-Agent")
        
        let (_, response) = try await session.data(for: request)
        
        guard let httpResponse = response as? HTTPURLResponse else {
            throw NetworkError.invalidResponse
        }
        
        switch httpResponse.statusCode {
        case 200...299:
            return // Success
        case 401:
            throw NetworkError.unauthorized
        case 429:
            throw NetworkError.rateLimited
        case 500...599:
            throw NetworkError.serverError(httpResponse.statusCode)
        default:
            throw NetworkError.httpError(httpResponse.statusCode)
        }
    }
    
    // MARK: - Send Crash Report
    
    func sendCrash(report: CrashReport) async throws {
        let url = baseURL.appendingPathComponent(Endpoint.crash.rawValue)
        
        let encoder = JSONEncoder()
        encoder.dateEncodingStrategy = .iso8601
        let jsonData = try encoder.encode(report)
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.httpBody = jsonData
        request.setValue(config.apiKey, forHTTPHeaderField: "X-API-Key")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("AppSignals-Swift-SDK/\(config.sdkVersion)", forHTTPHeaderField: "User-Agent")
        
        let (_, response) = try await session.data(for: request)
        
        guard let httpResponse = response as? HTTPURLResponse,
              (200...299).contains(httpResponse.statusCode) else {
            throw NetworkError.uploadFailed
        }
    }
    
    // MARK: - Send Replay Frames
    
    func sendReplayFrames(sessionId: String, frames: [ReplayFrame]) async throws {
        let url = baseURL.appendingPathComponent(Endpoint.replay.rawValue)
        
        let payload = ReplayPayload(
            sessionId: sessionId,
            frames: frames,
            context: ReplayContext(userId: config.userId)
        )
        
        let encoder = JSONEncoder()
        let jsonData = try encoder.encode(payload)
        let compressedData = try compress(data: jsonData)
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.httpBody = compressedData
        request.setValue(config.apiKey, forHTTPHeaderField: "X-API-Key")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("gzip", forHTTPHeaderField: "Content-Encoding")
        
        let (_, response) = try await session.data(for: request)
        
        guard let httpResponse = response as? HTTPURLResponse,
              (200...299).contains(httpResponse.statusCode) else {
            throw NetworkError.uploadFailed
        }
    }
    
    // MARK: - GZIP Compression
    
    private func compress(data: Data) throws -> Data {
        #if canImport(Compression)
        var compressedData = Data()
        
        // Add GZIP header
        compressedData.append(contentsOf: [0x1f, 0x8b, 0x08, 0x00])  // Magic, method
        compressedData.append(contentsOf: [0x00, 0x00, 0x00, 0x00])  // Mtime
        compressedData.append(contentsOf: [0x00, 0x03])              // XFL, OS
        
        let inputData = data as NSData
        
        let compressed = try inputData.compressed(using: .zlib)
        compressedData.append(compressed as Data)
        
        // Add CRC32 and size
        let crc = data.crc32()
        compressedData.append(contentsOf: withUnsafeBytes(of: crc.littleEndian) { Array($0) })
        let size = UInt32(data.count)
        compressedData.append(contentsOf: withUnsafeBytes(of: size.littleEndian) { Array($0) })
        
        return compressedData
        #else
        return data
        #endif
    }
}

// MARK: - Network Errors

enum NetworkError: Error, LocalizedError {
    case invalidResponse
    case unauthorized
    case rateLimited
    case serverError(Int)
    case httpError(Int)
    case uploadFailed
    case noConnection
    
    var errorDescription: String? {
        switch self {
        case .invalidResponse: return "Invalid server response"
        case .unauthorized: return "Invalid API key"
        case .rateLimited: return "Rate limit exceeded"
        case .serverError(let code): return "Server error (\(code))"
        case .httpError(let code): return "HTTP error (\(code))"
        case .uploadFailed: return "Upload failed"
        case .noConnection: return "No network connection"
        }
    }
}

// MARK: - Data Extensions

extension Data {
    func crc32() -> UInt32 {
        var crc: UInt32 = 0xFFFFFFFF
        for byte in self {
            let index = Int((crc ^ UInt32(byte)) & 0xFF)
            crc = Self.crc32Table[index] ^ (crc >> 8)
        }
        return crc ^ 0xFFFFFFFF
    }
    
    private static let crc32Table: [UInt32] = {
        (0..<256).map { i -> UInt32 in
            var c = UInt32(i)
            for _ in 0..<8 {
                c = (c & 1 == 1) ? (0xEDB88320 ^ (c >> 1)) : (c >> 1)
            }
            return c
        }
    }()
}
