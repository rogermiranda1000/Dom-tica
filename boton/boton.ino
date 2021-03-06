#include <DomoticConnector.h>
const char *const WIFI_NAME = "...";
const char *const WIFI_PASS = "...";

#define DEBUG 0
#if (DEBUG==1)
  #define DEBUG_PRINT(str) Serial.print(str)
  #define DEBUG_PRINTLN(str) Serial.println(str)
#else
  #define DEBUG_PRINT(str)
  #define DEBUG_PRINTLN(str)
#endif

#define MQTT_SERVER "192.168.1.229"
#define MQTT_PORT   1883
#define GROUP       "btn"

#define BUTTON_PIN  D2
#define SD_PIN      D8
#define FILE_NAME   "credentials.txt"

DomoticConnector *connector;

void setup() {
#if DEBUG
  Serial.begin(9600);
  while (!Serial);
#endif

  Connector.setup(DEBUG, WIFI_NAME, WIFI_PASS, SD_PIN, FILE_NAME);
  connector = new DomoticConnector(MQTT_SERVER, MQTT_PORT, GROUP);
  
  pinMode(BUTTON_PIN, INPUT_PULLUP);
}

void loop() {
  connector->loop();
  if (Serial.available()) Connector.eepromUpdate(Serial.readString());
  
  static bool pressed = false;
  if(!pressed) {
    // apretar el botón?
    if (digitalRead(BUTTON_PIN) == HIGH) return; // not pressed
    
    // falsa pulsación?
    unsigned long inicial = millis();
    while (millis()-inicial <= 17) {
      if(digitalRead(BUTTON_PIN) == HIGH) return;
    }

    DEBUG_PRINTLN("Botón pulsado.");
    connector->publish("central", connector->getStringID() + " btn " + connector->getStringID()); // el botón ID notifica que se ha pulsado, y que realize la acción 'ID'
    
    pressed = true;
  }
  else {
    // soltar el botón?
    if (digitalRead(BUTTON_PIN) == LOW) return; // still pressed
    
    // falso release?
    unsigned long inicial = millis();
    while (millis()-inicial <= 17) {
      if(digitalRead(BUTTON_PIN) == LOW) return;
    }
    
    pressed = false;
  }
}
