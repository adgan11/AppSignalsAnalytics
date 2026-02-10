import SwiftUI
import AppSignalsSDK
import UIKit

struct ContentView: View {
    @State private var eventCount = 0
    @State private var userId = ""
    @State private var showingAlert = false
    @State private var alertMessage = ""
    @State private var hasSentContext = false

    @Environment(\.colorScheme) private var colorScheme
    @Environment(\.layoutDirection) private var layoutDirection
    @Environment(\.locale) private var locale
    
    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 24) {
                    headerSection
                    eventTrackingSection
                    identitySection
                    metricsSection
                    navigationSection
                    crashSection
                    infoSection
                }
                .padding()
            }
            .navigationTitle("AppSignals Demo")
            .onAppear {
                if !hasSentContext {
                    sendDeviceContext()
                    hasSentContext = true
                }
            }
            .alert("Event Sent", isPresented: $showingAlert) {
                Button("OK") { }
            } message: {
                Text(alertMessage)
            }
        }
    }
    
    // MARK: - Header
    
    private var headerSection: some View {
        VStack(spacing: 12) {
            Image(systemName: "eye.circle.fill")
                .font(.system(size: 60))
                .foregroundStyle(.linearGradient(
                    colors: [.purple, .blue],
                    startPoint: .topLeading,
                    endPoint: .bottomTrailing
                ))
            
            Text("AppSignalsSDK Test App")
                .font(.title2)
                .fontWeight(.semibold)
            
            Text("Test all SDK features")
                .font(.subheadline)
                .foregroundColor(.secondary)
        }
        .padding(.vertical)
    }
    
    // MARK: - Event Tracking
    
    private var eventTrackingSection: some View {
        VStack(alignment: .leading, spacing: 16) {
            Label("Event Tracking", systemImage: "chart.line.uptrend.xyaxis")
                .font(.headline)
            
            LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 12) {
                
                EventButton(title: "Button Click", icon: "hand.tap.fill", color: .blue) {
                    AppSignals.track("button_clicked", properties: [
                        "button_id": "demo_button",
                        "screen": "home"
                    ])
                    eventCount += 1
                    showAlert("button_clicked sent!")
                }
                
                EventButton(title: "Purchase", icon: "cart.fill", color: .green) {
                    AppSignals.track("purchase_completed", properties: [
                        "product_id": "pro_subscription",
                        "price": 9.99,
                        "currency": "USD"
                    ])
                    eventCount += 1
                    showAlert("purchase_completed sent!")
                }
                
                EventButton(title: "Sign Up", icon: "person.badge.plus", color: .orange) {
                    AppSignals.track("signup_completed", properties: [
                        "method": "email"
                    ])
                    eventCount += 1
                    showAlert("signup_completed sent!")
                }
                
                EventButton(title: "Custom", icon: "star.fill", color: .purple) {
                    AppSignals.track("custom_event", properties: [
                        "timestamp": Date().timeIntervalSince1970
                    ])
                    eventCount += 1
                    showAlert("custom_event sent!")
                }
            }
            
            HStack {
                Text("Events this session:")
                Spacer()
                Text("\(eventCount)")
                .font(.headline)
                .foregroundColor(.blue)
            }
            .padding()
            .background(Color(.systemGray6))
            .cornerRadius(10)
            
            Button {
                AppSignals.flush()
                showAlert("Events flushed!")
            } label: {
                Label("Flush Events Now", systemImage: "arrow.up.circle.fill")
                    .frame(maxWidth: .infinity)
            }
            .buttonStyle(.bordered)
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(16)
        .shadow(color: .black.opacity(0.05), radius: 10)
    }
    
    // MARK: - Identity
    
    private var identitySection: some View {
        VStack(alignment: .leading, spacing: 16) {
            Label("User Identity", systemImage: "person.circle")
                .font(.headline)
            
            TextField("Enter User ID", text: $userId)
                .textFieldStyle(.roundedBorder)
            
            HStack(spacing: 12) {
                Button {
                    guard !userId.isEmpty else { return }
                    AppSignals.identify(userId: userId)
                    showAlert("Identified: \(userId)")
                } label: {
                    Label("Identify", systemImage: "person.badge.key")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.borderedProminent)
                
                Button {
                    AppSignals.reset()
                    userId = ""
                    showAlert("User reset")
                } label: {
                    Label("Reset", systemImage: "arrow.counterclockwise")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)
            }
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(16)
        .shadow(color: .black.opacity(0.05), radius: 10)
    }

    // MARK: - Metrics Payload

    private var metricsSection: some View {
        VStack(alignment: .leading, spacing: 16) {
            Label("Metrics Payload", systemImage: "gauge.with.dots.needle.67percent")
                .font(.headline)

            LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 12) {
                EventButton(title: "Device Context", icon: "iphone", color: .indigo) {
                    sendDeviceContext()
                    eventCount += 1
                    showAlert("device_context sent!")
                }

                EventButton(title: "User Input Error", icon: "keyboard", color: .red) {
                    AppSignals.track("user_input_error", properties: [
                        "error_category": "user_input",
                        "error_type": "invalid_email",
                        "field": "email"
                    ])
                    eventCount += 1
                    showAlert("user_input_error sent!")
                }

                EventButton(title: "App State Error", icon: "exclamationmark.triangle", color: .orange) {
                    AppSignals.track("app_state_error", properties: [
                        "error_category": "app_state",
                        "error_type": "missing_state",
                        "state": "checkout"
                    ])
                    eventCount += 1
                    showAlert("app_state_error sent!")
                }

                EventButton(title: "Exception Event", icon: "bolt.fill", color: .purple) {
                    AppSignals.track("exception", properties: [
                        "error_category": "exception",
                        "error_type": "nil_reference",
                        "source": "demo"
                    ])
                    eventCount += 1
                    showAlert("exception sent!")
                }
            }
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(16)
        .shadow(color: .black.opacity(0.05), radius: 10)
    }
    
    // MARK: - Navigation
    
    private var navigationSection: some View {
        VStack(alignment: .leading, spacing: 16) {
            Label("Screen Tracking", systemImage: "rectangle.stack")
                .font(.headline)
            
            Text("Navigate to test auto screen tracking")
                .font(.caption)
                .foregroundColor(.secondary)
            
            NavigationLink {
                DetailView(title: "Products", icon: "bag.fill")
            } label: {
                navRow(icon: "bag.fill", title: "Products", color: .orange)
            }
            
            NavigationLink {
                DetailView(title: "Settings", icon: "gear")
            } label: {
                navRow(icon: "gear", title: "Settings", color: .gray)
            }
            
            NavigationLink {
                DetailView(title: "Profile", icon: "person.fill")
            } label: {
                navRow(icon: "person.fill", title: "Profile", color: .blue)
            }
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(16)
        .shadow(color: .black.opacity(0.05), radius: 10)
    }
    
    private func navRow(icon: String, title: String, color: Color) -> some View {
        HStack {
            Image(systemName: icon)
                .foregroundColor(color)
            Text(title)
                .foregroundColor(.primary)
            Spacer()
            Image(systemName: "chevron.right")
                .foregroundColor(.secondary)
        }
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(10)
    }
    
    // MARK: - Crash Testing
    
    private var crashSection: some View {
        VStack(alignment: .leading, spacing: 16) {
            Label("Crash Testing", systemImage: "exclamationmark.triangle.fill")
                .font(.headline)
            
            Text("⚠️ These will crash the app!")
                .font(.caption)
                .foregroundColor(.orange)
            
            HStack(spacing: 12) {
                Button {
                    fatalError("Test crash")
                } label: {
                    Label("Fatal Error", systemImage: "xmark.octagon.fill")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)
                .tint(.red)
                
                Button {
                    let arr: [Int] = []
                    _ = arr[10]
                } label: {
                    Label("Index Error", systemImage: "exclamationmark.triangle")
                        .frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)
                .tint(.red)
            }
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(16)
        .shadow(color: .black.opacity(0.05), radius: 10)
    }
    
    // MARK: - Info
    
    private var infoSection: some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Session Info", systemImage: "info.circle")
                .font(.headline)
            
            InfoRow(label: "SDK", value: "1.0.0")
            InfoRow(label: "Server", value: "localhost:8000")
            InfoRow(label: "Status", value: "Connected", color: .green)
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(16)
        .shadow(color: .black.opacity(0.05), radius: 10)
    }
    
    private func showAlert(_ msg: String) {
        alertMessage = msg
        showingAlert = true
    }

    private func sendDeviceContext() {
        let screenBounds = UIScreen.main.nativeBounds
        let deviceOrientation = UIDevice.current.orientation
        let isLandscape = deviceOrientation.isValidInterfaceOrientation
            ? deviceOrientation.isLandscape
            : screenBounds.width > screenBounds.height
        let orientationValue = isLandscape ? "landscape" : "portrait"
        let preferredLanguage = Locale.preferredLanguages.first ?? locale.identifier
        let appLanguage = locale.languageCode ?? locale.identifier
        let region = locale.regionCode ?? ""
        let layoutValue = layoutDirection == .rightToLeft ? "rtl" : "ltr"

        let properties: [String: Any] = [
            "screen_width": Int(screenBounds.width),
            "screen_height": Int(screenBounds.height),
            "orientation": orientationValue,
            "color_scheme": colorScheme == .dark ? "dark" : "light",
            "platform": UIDevice.current.systemName,
            "os_name": UIDevice.current.systemName,
            "app_language": appLanguage,
            "preferred_language": preferredLanguage,
            "layout_direction": layoutValue,
            "preferred_content_size": UIApplication.shared.preferredContentSizeCategory.rawValue,
            "bold_text": UIAccessibility.isBoldTextEnabled,
            "reduce_motion": UIAccessibility.isReduceMotionEnabled,
            "reduce_transparency": UIAccessibility.isReduceTransparencyEnabled,
            "darker_system_colors": UIAccessibility.isDarkerSystemColorsEnabled,
            "differentiate_without_color": UIAccessibility.shouldDifferentiateWithoutColor,
            "invert_colors": UIAccessibility.isInvertColorsEnabled,
            "sdk_version": "1.0.0",
            "app_build": Bundle.main.infoDictionary?["CFBundleVersion"] as? String ?? "unknown",
            "region": region
        ]

        AppSignals.track("device_context", properties: properties)
    }
}

// MARK: - Components

struct EventButton: View {
    let title: String
    let icon: String
    let color: Color
    let action: () -> Void
    
    var body: some View {
        Button(action: action) {
            VStack(spacing: 8) {
                Image(systemName: icon)
                    .font(.title2)
                    Text(title)
                    .font(.caption)
            }
            .frame(maxWidth: .infinity)
            .padding(.vertical, 16)
            .background(color.opacity(0.1))
            .foregroundColor(color)
            .cornerRadius(12)
        }
    }
}

struct InfoRow: View {
    let label: String
    let value: String
    var color: Color = .primary
    
    var body: some View {
        HStack {
            Text(label).foregroundColor(.secondary)
            Spacer()
            Text(value).foregroundColor(color).fontWeight(.medium)
        }
    }
}

struct DetailView: View {
    let title: String
    let icon: String
    
    var body: some View {
        VStack(spacing: 20) {
            Image(systemName: icon)
                .font(.system(size: 80))
                .foregroundColor(.blue)
            
            Text(title)
                .font(.largeTitle)
                .fontWeight(.bold)
            
            Text("Screen view auto-tracked by AppSignalsSDK")
                .foregroundColor(.secondary)
            
            Button("Track Action") {
                AppSignals.track("detail_action", properties: ["screen": title])
            }
            .buttonStyle(.borderedProminent)
        }
        .padding()
        .navigationTitle(title)
    }
}

#Preview {
    ContentView()
}
