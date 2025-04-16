#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <DHT.h>
#include "secrets.h"

#define SERIAL_LOG_ENABLED 1

#define NO_PUMP -1
#define NO_TANK -1
#define NO_SOIL -1
#define PUMP_0 D1
#define PUMP_1 D2
#define TANK_0 D3
#define TANK_1 D5
#define SOIL_0 A0
#define SOIL_1 D7
#define DHT_PIN D6

#define PUMP_ON LOW
#define PUMP_OFF HIGH
#define TANK_EMPTY HIGH

#define MQTT_RPC_IN_TOPIC "plant/rpc/in"
#define MQTT_RPC_OUT_TOPIC "plant/rpc/out"
#define MQTT_EVENTS_TOPIC "plant/events"
#define MQTT_SENSORS_TOPIC "plant/sensors"

DHT dht(DHT_PIN, DHT22);
WiFiClient espClient;
PubSubClient mqtt(espClient);

int wlanWatchDogNextCheck = 0;
int mqttWatchDogNextCheck = 0;
int restartWatchDogNextCheck = 0;

int runningPump = NO_PUMP;
int stopPumpAt = 0;

int queuedPump = NO_PUMP;
int runQueuedPumpFor = 0;

void serialLog(String msg) {
  if (SERIAL_LOG_ENABLED) {
    Serial.println(msg);
  }
}

bool runPump(int pump, int tank, int seconds, String &msg) {
  msg = "";

  /*int tankState = digitalRead(tank);
  if (tankState = TANK_EMPTY) {
    msg = "Tank is empty";
    return false;
  } else */if (runningPump == NO_PUMP) {
    serialLog("Running pump on pin " + (String)pump + " for " + (String)seconds + " seconds");
    runningPump = pump;
    stopPumpAt = millis() + seconds * 1000;
    digitalWrite(pump, PUMP_ON);
    return true;
  } else if (queuedPump == NO_PUMP) {
    serialLog("Queued pump on pin " + (String)pump);
    queuedPump = pump;
    runQueuedPumpFor = seconds;
    msg = "Pump queued";
    return true;
  } else {
    serialLog("Queue is full");
    msg = "Another pump is already queued";
    return false;
  }
}

void loopPumps() {
  if (runningPump != NO_PUMP) {
    int now = millis();
    if (stopPumpAt <= now) {
      serialLog("Stopped pump on pin " + (String)runningPump);
      digitalWrite(runningPump, PUMP_OFF);
      if (queuedPump != NO_PUMP) {
        serialLog("Running queued pump on pin " + (String)queuedPump + " for " + (String)runQueuedPumpFor + " seconds");
        runningPump = queuedPump;
        stopPumpAt = now + runQueuedPumpFor * 1000;
        queuedPump = NO_PUMP;
        runQueuedPumpFor = 0;
        digitalWrite(runningPump, PUMP_ON);
      } else {
        runningPump = NO_PUMP;
        stopPumpAt = 0;
      }
    }
  }
}

String processRpcRequest(String requestRaw) {
  JsonDocument reqJson;
  DeserializationError error = deserializeJson(reqJson, requestRaw);
  if (error) {
    serialLog("Failed to deserialize JSON: " + (String)error.c_str());
  }

  JsonDocument resJson;
  resJson["id"] = reqJson["id"];
  resJson["ok"] = true;
  resJson["msg"] = "";

  String method(reqJson["method"]);
  if (method == "runPump") {
    int pump = NO_PUMP;
    int tank = NO_TANK;
    switch (static_cast<int>(reqJson["params"]["pump"])) {
      case 0:
        pump = PUMP_0;
        tank = TANK_0;
        break;
      case 1:
        pump = PUMP_1;
        tank = TANK_1;
        break;
      default:
        resJson["ok"] = false;
        resJson["msg"] = "Bad pump number";
        break;
    }
    
    if (pump != NO_PUMP) {
      String msg;
      resJson["ok"] = runPump(pump, tank, reqJson["params"]["seconds"], msg);
      resJson["msg"] = msg;
    }
  } else if (method == "getTankState") {
    int tank = NO_TANK;
    switch (static_cast<int>(reqJson["params"]["tank"])) {
      case 0:
        tank = TANK_0;
        break;
      case 1:
        tank = TANK_1;
        break;
      default:
        resJson["ok"] = false;
        resJson["msg"] = "Bad tank number";
        break;
    }
    if (tank != NO_TANK) {
      resJson["data"]["tankState"] = (bool)digitalRead(tank);
    }
  } else if (method == "getSoilHumidity") {
    int soilSensor = NO_SOIL;
    switch (static_cast<int>(reqJson["params"]["plant"])) {
      case 0:
        soilSensor = SOIL_0;
        break;
      case 1:
        soilSensor = SOIL_1;
        break;
      default:
        resJson["ok"] = false;
        resJson["msg"] = "Bad plant number";
        break;
    }
    if (soilSensor != NO_SOIL) {
      resJson["data"]["humidity"] = analogRead(soilSensor);
    }
  } else if (method == "getAmbientTemp") {
    float temp = dht.readTemperature();
    if (isnan(temp)) {
      resJson["ok"] = false;
      resJson["msg"] = "Could not read value from sensor";
    } else {
      resJson["data"]["temperature"] = temp;
    }
  } else if (method == "getAmbientHum") {
    float hum = dht.readHumidity();
    if (isnan(hum)) {
      resJson["ok"] = false;
      resJson["msg"] = "Could not read value from sensor";
    } else {
      resJson["data"]["humidity"] = hum;
    }
  } else {
    resJson["ok"] = false;
    resJson["msg"] = "Unknown RPC method: " + method;
  }

  String resPayload;
  serializeJson(resJson, resPayload);

  return resPayload;
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
  serialLog("Got message from ");
  serialLog(topic);

  String msg;
  for (int i = 0; i < length; i++) {
    msg = msg + (char)payload[i];
  }

  serialLog(msg);

  if ((String)topic == MQTT_RPC_IN_TOPIC) {
    String rpcResponse = processRpcRequest(msg);
    if (!mqtt.publish(MQTT_RPC_OUT_TOPIC, rpcResponse.c_str())) {
      serialLog("Failed to publish RPC response");
    }
  } else {
    serialLog("Messages from this topic are ignored");
    serialLog(topic);
  }
}

void loopRestartWatchDog() {
  int now = millis();
  if (restartWatchDogNextCheck == 0) {
    restartWatchDogNextCheck = now + 60000;
  }

  if (restartWatchDogNextCheck < now && WiFi.status() != WL_CONNECTED) {
    serialLog("WLAN disconnected for more than a minute, restarting");
    ESP.restart();
  }
}

void resetRestartWatchDog() {
  restartWatchDogNextCheck = 0;
}

void connectWlan() {
  if (SERIAL_LOG_ENABLED) {
    Serial.print("Connecting to WLAN " + (String)WLAN_SSID);
  }

  WiFi.begin(WLAN_SSID, WLAN_PSK);
  while(WiFi.status() != WL_CONNECTED) {
    if (SERIAL_LOG_ENABLED) {
      Serial.print(F("."));
    }
    loopRestartWatchDog();
    delay(1000);
  }
  if (SERIAL_LOG_ENABLED) {
    Serial.println(F(""));
  }

  serialLog("Connected");
  serialLog(WiFi.localIP().toString());
  WiFi.setAutoReconnect(true);
  resetRestartWatchDog();
}

void loopWlanWatchDog() {
  int now = millis();
  if (wlanWatchDogNextCheck == 0) {
    wlanWatchDogNextCheck = now + 30000;
  }

  if (wlanWatchDogNextCheck < now && WiFi.status() != WL_CONNECTED) {
    serialLog("Lost WLAN connection, trying to reconnect");
    WiFi.disconnect();
    connectWlan();
  }
}

void resetWlanWatchDog() {
  wlanWatchDogNextCheck = 0;
}

void connectMqtt() {
  mqtt.setServer(MQTT_HOST, MQTT_PORT);
  mqtt.setCallback(mqttCallback);

  serialLog("Connecting to MQTT");
  while (!mqtt.connect(MQTT_USER, MQTT_USER, MQTT_PASS)) {
    serialLog("MQTT state = " + mqtt.state());
    loopWlanWatchDog();
    delay(1000);
  }
  serialLog("Connected");

  mqtt.publish(MQTT_EVENTS_TOPIC, "ready");
  mqtt.subscribe(MQTT_RPC_IN_TOPIC);

  resetWlanWatchDog();
}

void loopMqttWatchDog() {
  int now = millis();
  if (mqttWatchDogNextCheck == 0 || mqttWatchDogNextCheck <= now) {
    mqttWatchDogNextCheck = now + 30000;

    if (!mqtt.connected()) {
      serialLog("Lost MQTT connection, trying to reconnect");
      mqtt.disconnect();
      connectMqtt();
    }
  }
}

void loopWatchDogs() {
  loopWlanWatchDog();
}

void setup() {
  if (SERIAL_LOG_ENABLED) {
    Serial.begin(9600);
    while (!Serial) {
      delay(1);
    }
  }
  dht.begin();

  pinMode(PUMP_0, OUTPUT);
  digitalWrite(PUMP_0, PUMP_OFF);
  pinMode(PUMP_1, OUTPUT);
  digitalWrite(PUMP_1, PUMP_OFF);
  pinMode(TANK_0, INPUT_PULLUP);
  pinMode(TANK_1, INPUT_PULLUP);
  pinMode(SOIL_0, INPUT);
  pinMode(SOIL_1, INPUT);

  connectWlan();
  connectMqtt();
}

void loop() {
  mqtt.loop();
  loopPumps();
  loopMqttWatchDog();
}
