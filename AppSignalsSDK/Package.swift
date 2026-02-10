// swift-tools-version: 6.0
// AppSignals Analytics SDK for iOS

import PackageDescription

let package = Package(
    name: "AppSignalsSDK",
    platforms: [
        .iOS(.v16),
        .macOS(.v13)
    ],
    products: [
        .library(
            name: "AppSignalsSDK",
            targets: ["AppSignalsSDK"]
        ),
    ],
    dependencies: [
        // SQLite for local persistence (lightweight wrapper)
        .package(url: "https://github.com/stephencelis/SQLite.swift.git", from: "0.15.0"),
        .package(url: "https://github.com/microsoft/plcrashreporter", from: "1.12.2"),
    ],
    targets: [
        .target(
            name: "AppSignalsSDK",
            dependencies: [
                .product(name: "SQLite", package: "SQLite.swift"),
                .product(name: "CrashReporter", package: "PLCrashReporter"),
            ],
            resources: [
                .copy("PrivacyInfo.xcprivacy")
            ],
            swiftSettings: [
                .swiftLanguageMode(.v5)
            ]
        ),
        .testTarget(
            name: "AppSignalsSDKTests",
            dependencies: ["AppSignalsSDK"]
        ),
    ]
)
