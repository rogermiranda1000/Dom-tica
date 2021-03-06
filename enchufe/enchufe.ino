#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <SPI.h>
#include <SD.h>

String ssid = "";
String password = "";
char* mqtt_server = "192.168.1.230";
char* mqtt_server2 = "192.168.1.229";
bool dest = false;
const char* cliente = "INT01";

const int rele = D0;

WiFiClient espClient;
PubSubClient client(espClient);

File myFile;

const int btn = D9;

void callback(char* topic, byte* payload, unsigned int length) {
  String mensaje = "";
  for (int i=0;i<length;i++) mensaje += (char)payload[i];
  
  if(mensaje=="1") digitalWrite(rele, HIGH);
  else if(mensaje=="0") digitalWrite(rele, LOW);
}
 
 
void reconnect() {
  while (!client.connected()) {
    Serial.print("Conectando...");
    if (client.connect(cliente)) {
      Serial.println(" Conectado!");
      client.subscribe(cliente);
      client.publish("central", (const char*) (String(cliente) + " enchufe").c_str());
    } else {
      Serial.print("fallo, rc=");
      Serial.print(client.state());
      Serial.println(" intentado otra vez en 2 segundos...");
      cambiarDestino();
      delay(2000);
    }
  }
}

void cambiarDestino() {
  dest = !dest;
  if(dest) client.setServer(mqtt_server, 1883);
  else client.setServer(mqtt_server2, 1883);
}

void setup() {
  pinMode(btn, INPUT);
  Serial.begin(9600);
  pinMode(rele, OUTPUT);
  digitalWrite(rele, LOW);

  if (!SD.begin(4)) {
    Serial.println("Error en la lectura de la MicroSD.");
  }
  
  myFile = SD.open("send.txt");
  if (myFile) {
    Serial.println("SD:");
    int x = 0;
    while (myFile.available()) {
      String msgo = myFile.readStringUntil('\n');
      Serial.println(msgo);
      if(x==0) ssid = msgo;
      else if(x==1) password = msgo;

      x++;
    }
    myFile.close();
  } else {
    Serial.println("Error en la lectura de la MicroSD.");
  }

  ssid.remove(ssid.length()-1);
  Serial.println("ssid: " + ssid);
  Serial.println("pass: " + password);
  
  Serial.print("Connecting to ");
 Serial.print(ssid);
 WiFi.mode(WIFI_STA);
 WiFi.begin((const char*) ssid.c_str(), (const char*) password.c_str());
 while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.println("WiFi connected");
  
  cambiarDestino();
  client.setCallback(callback);
}

void loop() {
  if (!client.connected()) reconnect();
  client.loop();
}
