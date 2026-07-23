---
Title: Guardian Hardware Architecture
Document: 03_HARDWARE_ARCHITECTURE.md
Version: 0.1
Status: Draft
Project: Guardian
Last Updated: 2026-07-23
Related Sprint: Sprint #001
---

# Guardian Hardware Architecture

## Guardian – Secure Access. Intelligent Control.

## 1. Purpose

This document defines how Guardian represents and communicates with physical access-control hardware.

The architecture separates:

- Sites
- Entrances
- Network controllers
- I/O modules
- Physical input channels
- Physical output channels
- Logical Guardian functions

This separation allows Guardian to support different installations without hard-coding site-specific wiring into the application.

---

# 2. Hardware Hierarchy

Guardian uses the following conceptual hierarchy:

    Site
      |
      +-- Entrance / Gate
             |
             +-- Controller
                    |
                    +-- I/O Module
                           |
                           +-- Inputs
                           |
                           +-- Outputs

A site may contain multiple entrances.

An entrance may use one or more controllers.

A controller may communicate with one or more downstream I/O modules.

---

# 3. Initial Guardian Hardware Platform

The initial Guardian hardware platform consists of:

- ICP DAS I-7188E2D
- ICP DAS I-7065

The communication path is:

    Guardian
       |
       | Ethernet / TCP
       |
       v
    ICP DAS I-7188E2D
       |
       | RS-485 / DCON
       |
       v
    ICP DAS I-7065
       |
       +-- IN1
       +-- IN2
       +-- IN3
       +-- IN4
       |
       +-- Relay 1
       +-- Relay 2
       +-- Relay 3
       +-- Relay 4
       +-- Relay 5

---

# 4. ICP DAS I-7188E2D

Within Guardian, the I-7188E2D is treated as a network-connected controller/gateway.

Guardian communicates directly with the I-7188E2D using Ethernet/TCP.

The controller therefore owns network configuration such as:

- IP address
- TCP port
- Connection timeout
- Communication status
- Last successful communication

Conceptually:

    Guardian Hardware Service
             |
             v
       TCP Connection
             |
             v
        I-7188E2D

The I-7188E2D provides Guardian with access to the downstream RS-485 network.

---

# 5. ICP DAS I-7065

The I-7065 is treated as a downstream I/O module.

It is not treated as a separate Ethernet controller.

The I-7065 therefore does not require its own IP address in the approved Guardian architecture.

Instead, Guardian reaches it through:

    I-7188E2D
         |
         | RS-485 / DCON
         |
         v
       I-7065

The I-7065 is identified by its module address on the downstream communication network.

---

# 6. Controller Versus I/O Module

Guardian must distinguish between these concepts.

## Controller

A controller represents hardware Guardian can communicate with through a transport such as Ethernet/TCP.

For the current installation:

    ICP DAS I-7188E2D = Controller

Typical controller configuration includes:

- Name
- Model
- Site
- Location
- IP address
- TCP port
- Protocol
- Connection timeout
- Enabled state
- Online state
- Last seen

## I/O Module

An I/O module represents hardware connected downstream of a controller.

For the current installation:

    ICP DAS I-7065 = I/O Module

Typical module configuration includes:

- Parent controller
- Module name
- Model
- Module address
- Protocol
- Location
- Enabled state

Network settings such as IP address and TCP port belong to the controller, not the I/O module.

---

# 7. I-7065 Physical Inputs

The current I-7065 provides four configurable digital input channels used by Guardian:

    IN1
    IN2
    IN3
    IN4

Guardian must distinguish between the physical input number and its logical function.

For example:

    Physical Input
         IN1
          |
          v
    Guardian Configuration
          |
          v
    Logical Function
      Loop Detector

The function assigned to an input is installer configurable.

Possible logical functions may include:

- Loop detector
- No-touch sensor
- Safety input
- Emergency input
- Other supported input functions

These are logical roles and should not be permanently associated with a particular input number.

---

# 8. I-7065 Relay Outputs

The current I-7065 provides five relay outputs used by Guardian:

    Relay 1
    Relay 2
    Relay 3
    Relay 4
    Relay 5

As with inputs, Guardian separates the physical relay number from the logical function.

Example:

    Physical Output
        Relay 2
           |
           v
    Guardian Configuration
           |
           v
     Logical Function
      Green Light

Possible output functions may include:

- Boom control
- Green traffic light
- Red traffic light
- Alarm
- Other configured functions

The actual assignment is installation-specific.

---

# 9. Configurable I/O Principle

Guardian must never assume that a physical channel always performs the same function.

Incorrect architectural approach:

    if IN1 active
        vehicle detected

Preferred Guardian approach:

    Input IN1
        |
        v
    Input Configuration
        |
        v
    InputType = Loop Detector
        |
        v
    LOOP_ACTIVE event

This allows Guardian to understand the meaning of the input rather than simply reacting to a channel number.

The same principle applies to relay outputs.

---

# 10. Logical Hardware Operations

Higher-level Guardian components should work with logical operations wherever practical.

For example, the Control Engine should conceptually request:

    openBoom()

rather than:

    turnRelay1On()

The hardware configuration determines which physical relay implements the logical operation.

Similarly:

    setTrafficLight(RED)

should eventually resolve to the correctly configured physical output.

This provides hardware abstraction and installation flexibility.

---

# 11. Hardware Service Responsibilities

The Guardian Hardware Service sits between the Control Engine and physical hardware.

Conceptually:

    Guardian Control Engine
             |
             v
       Hardware Service
             |
             v
        Protocol Layer
             |
             v
         Controller
             |
             v
         I/O Module

The Hardware Service is responsible for operations such as:

- Establishing controller communication
- Selecting the correct module
- Reading inputs
- Reading outputs
- Setting outputs
- Testing communication
- Reporting hardware state

---

# 12. DCON Protocol Layer

The initial Guardian implementation uses the DCON protocol.

The DCON layer is responsible for:

- Module addressing
- Command construction
- Command transmission
- Response parsing
- Response validation

For example, Guardian has already successfully used a module-information command against the current hardware.

A development response such as:

    !017065

confirmed communication with the addressed 7065 module.

This proves the basic path:

    Guardian
       |
       v
    I-7188E2D
       |
       v
    RS-485 / DCON
       |
       v
    I-7065

The next implementation objective is reliable input-state reading and relay control.

---

# 13. Hardware Status

Guardian should distinguish between controller communication status and module communication status.

For example:

    Main Gate Controller
        I-7188E2D
        ONLINE

does not necessarily prove that every downstream module is responding correctly.

Guardian should eventually be capable of representing:

    Controller ONLINE
         |
         +-- 7065 Address 01 ONLINE
         |
         +-- Future Module Address 02 OFFLINE

This provides more accurate diagnostics.

---

# 14. Live Input Monitoring

Guardian should be capable of displaying the current state of each configured input.

Example:

    Main Gate
    --------------------------------

    IN1   Loop Detector      OFF
    IN2   No-Touch           OFF
    IN3   Safety Input       OFF
    IN4   Configurable       OFF

The names above are examples of logical configuration.

The interface should obtain these names from Guardian configuration rather than hard-coded labels.

---

# 15. Live Output Monitoring

Guardian should also display output state.

Example:

    Main Gate
    --------------------------------

    Relay 1   Boom Control       OFF
    Relay 2   Green Light        OFF
    Relay 3   Red Light          ON
    Relay 4   Alarm              OFF
    Relay 5   Configurable       OFF

Again, the physical-to-logical assignments are configuration driven.

---

# 16. Manual Hardware Testing

Guardian should provide installer diagnostics that allow individual hardware channels to be tested.

Examples include:

- Read all inputs
- Read all relay states
- Test an individual input
- Activate a configured output
- Deactivate a configured output
- Pulse an output
- Test module communication

Manual hardware testing is an installer/diagnostic function.

It must remain separate from normal automated Control Engine behaviour.

---

# 17. No-Touch Sensor

A no-touch sensor is treated as a digital input source.

Conceptually:

    Person activates No-Touch
             |
             v
          Sensor
             |
             v
        7065 Input
             |
             v
       Guardian reads
          input state
             |
             v
      NO_TOUCH event
             |
             v
    Guardian Control Engine

The sensor should not directly contain Guardian access logic.

Its purpose is to provide an input event.

Guardian determines what that event means for the configured entrance.

---

# 18. Loop Detector

The loop detector is also treated as a digital input source.

Conceptually:

    Vehicle
       |
       v
    Inductive Loop
       |
       v
    Loop Detector
       |
       v
    7065 Input
       |
       v
    Guardian
       |
       v
    LOOP_ACTIVE / LOOP_CLEAR

The Control Engine uses these state changes to manage vehicle movement and safe boom operation.

---

# 19. Trailer Handling

Guardian must account for a vehicle and trailer producing separate loop activations.

Example:

    Vehicle
       |
       v
    LOOP ACTIVE
       |
       v
    LOOP CLEAR
       |
       | short physical gap
       |
       v
    Trailer
       |
       v
    LOOP ACTIVE

The first LOOP CLEAR does not automatically authorize boom closure.

Guardian starts a configurable clearance period.

If the loop becomes active again during this period:

    Pending Close
         |
         v
       CANCEL
         |
         v
    Keep Boom Open
         |
         v
    Reset Clearance Logic

This protects trailers and long vehicle combinations.

---

# 20. Tailgating and Hardware Safety

A second vehicle may also cause the loop to re-trigger.

From the physical hardware perspective, Guardian must first maintain a safe state.

Therefore:

    LOOP ACTIVE
         |
         v
    Vehicle Present
         |
         v
    Do Not Close Boom

The security layer can separately determine whether the additional detection represents a potential tailgating event.

This maintains the Guardian principle:

> Every security decision must also be a safe decision.

Guardian may record or report the security violation, but physical protection remains active while a vehicle occupies the protected area.

---

# 21. Traffic Lights

Traffic lights are controlled as logical Guardian outputs.

The approved initial operational behaviour is:

    Access Granted
         |
         v
    GREEN
         |
         v
    Boom Opens
         |
         v
    First Loop Detection
         |
         v
    RED

Once the first loop activation occurs, the traffic indication becomes red to discourage another vehicle from entering.

The boom remains physically protected by the loop and safety logic.

Traffic-light outputs should remain configurable to physical relays.

---

# 22. Boom Control

Boom operation is managed by the Guardian Control Engine.

Access methods should not directly control the boom relay.

Correct architecture:

    ANPR / No-Touch / QR / Operator
                 |
                 v
           Access Decision
                 |
                 v
        Guardian Control Engine
                 |
                 v
          Hardware Service
                 |
                 v
         Configured Output
                 |
                 v
             Boom Gate

This guarantees consistent entrance behaviour regardless of the access method.

---

# 23. Failure Behaviour

Hardware failures must be visible to Guardian.

Examples include:

- TCP connection failure
- Controller timeout
- Invalid DCON response
- Module not responding
- Input-read failure
- Output command failure

Guardian must not silently assume that a hardware action succeeded.

Detailed failure-state behaviour will be defined as the Control Engine and hardware layer mature.

---

# 24. Future Hardware Support

The architecture must allow additional hardware to be introduced later.

Examples may include:

    Guardian Hardware Service
              |
      +-------+-------+
      |       |       |
      v       v       v
     DCON   Modbus   Future
                     Driver

Future hardware support should not require rewriting the Guardian access-control logic.

---

# 25. Approved Hardware Model

The following architecture is approved for Guardian Sprint #001:

    SITE
      |
      v
    ENTRANCE
      |
      v
    CONTROLLER
    I-7188E2D
      |
      | Ethernet/TCP connection owned here
      |
      v
    I/O MODULE
    I-7065
      |
      | RS-485/DCON address owned here
      |
      +--------------------+
      |                    |
      v                    v
    INPUTS                OUTPUTS
    IN1-IN4               Relay 1-5
      |                    |
      v                    v
    Logical              Logical
    Functions            Functions

This model will be used as the basis for the Guardian database design.

---

# 26. Architectural Decision

The existing implementation initially represented hardware devices using a common device structure.

Guardian will evolve this model so that network controllers and downstream I/O modules are represented according to their actual responsibilities.

Therefore:

> A 7065 must not be forced to contain an IP address merely because the 7188E2D requires one.

This is one of the primary reasons for separating Controller and I/O Module concepts in the Guardian architecture.

---

# Related Documents

- `README.md`
- `00_PROJECT_VISION.md`
- `01_SYSTEM_ARCHITECTURE.md`
- `02_DATABASE_DESIGN.md`
- `04_CONTROL_ENGINE.md`
- `06_INSTALLATION_GUIDE.md`
- `07_DEVELOPER_GUIDE.md`
- `10_DECISIONS.md`

---

# Revision History

| Version | Date | Description |
|---|---|---|
| 0.1 | 2026-07-23 | Initial Guardian hardware architecture established for the I-7188E2D and I-7065 platform. |