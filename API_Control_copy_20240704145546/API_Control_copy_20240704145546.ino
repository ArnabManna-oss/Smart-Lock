#include <WiFi.h>
#include <WebServer.h>

// Replace with your network credentials
const char* ssid = "POCO M4 Pro 5G";
const char* password = "12345678";

// Create an instance of the web server on port 80
WebServer server(80);

// Global variable
int a = 0;

// GPIO pin for the LED
#define LED_PIN 13

void handleRoot() {
  server.send(200, "text/plain", "Hello, World!");
}

void handleOn() {
  a = 1;
  digitalWrite(LED_PIN, HIGH);  // Turn off the LED
  server.send(200, "text/plain", "LED is OFF");
}

void handleOff() {
  a = 0;
  digitalWrite(LED_PIN, LOW); // Turn on the LED
  server.send(200, "text/plain", "LED is ON");
}

void setup() {
  // Initialize serial communication at 9600 baud rate
  Serial.begin(9600);
  delay(1000); // Allow time for the Serial to initialize

  // Initialize the LED pin as an output
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW); // Ensure LED is off initially

  // Connect to Wi-Fi
  Serial.print("Connecting to ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);

  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 30) {
    delay(1000);
    Serial.print(".");
    retries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("");
    Serial.println("WiFi connected.");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("");
    Serial.println("Failed to connect to WiFi");
  }

  // Start the server
  server.on("/", handleRoot);
  server.on("/ON", handleOn);
  server.on("/OFF", handleOff);
  server.begin();
  Serial.println("HTTP server started");
}

void loop() {
  // Handle client requests
  server.handleClient();
}
