---
Title: Guardian System Architecture
Document: 01_SYSTEM_ARCHITECTURE.md
Version: 0.1
Status: Draft
Project: Guardian
Last Updated: 2026-07-23
Related Sprint: Sprint #001
---

# Guardian System Architecture

## Guardian – Secure Access. Intelligent Control.

## 1. Purpose

This document defines the high-level software and hardware architecture of Guardian.

The objective is to maintain clear separation between:

- User interface
- Application logic
- Access-control decisions
- Hardware communication
- Protocol implementation
- Physical controllers
- Physical inputs and outputs
- Data storage

This separation allows Guardian to grow without embedding hardware-specific behaviour throughout the application.

---

# 2. Architectural Overview

The approved high-level architecture is:

    Users / Operators / Installers
                |
                v
        Guardian Web Interface
                |
                v
          CodeIgniter 4
                |
                v
       Application Services
                |
                v
      Guardian Control Engine
                |
                v
         Hardware Service
                |
                v
         Protocol Driver
                |
                v
      ICP DAS I-7188E2D
         Ethernet / TCP
                |
                v
          RS-485 / DCON
                |
                v
        ICP DAS I-7065
                |
        +-------+-------+
        |               |
        v               v
    IN1-IN4         Relay 1-5
        |               |
        v               v
    Sensors        Physical Control

---

# 3. CodeIgniter 4 Application

CodeIgniter 4 provides the application framework for Guardian.

The application is responsible for areas such as:

- Authentication
- Authorization
- User management
- Site management
- Controller configuration
- Hardware configuration
- Visitor management
- Resident management
- Dashboards
- Reporting
- Diagnostics
- Configuration
- Event history

Controllers should primarily handle HTTP requests and responses.

Complex operational logic should not be implemented directly inside HTTP controllers.

---

# 4. Application Services

Guardian uses service classes to separate business logic from HTTP controllers.

Examples include:

    Controller
        |
        v
    Service
        |
        v
    Model / Control Engine / Hardware Service

This allows the same business logic to be reused from different interfaces in the future.

Potential interfaces may include:

- Web interface
- Mobile application
- API
- Background services
- Scheduled processes

---

# 5. Guardian Control Engine

The Guardian Control Engine is the central decision-making component for physical entrance behaviour.

All access methods should ultimately submit their approved access requests to the Control Engine.

Examples include:

    ANPR ------------------+
                           |
    No-Touch --------------+
                           |
    QR --------------------+
                           |
    Visitor Access --------+----> Guardian Control Engine
                           |
    Resident Access -------+
                           |
    Security Operator -----+
                           |
    Remote Command --------+

The access method determines how the request originates.

The Control Engine determines how the entrance behaves.

---

# 6. Control Engine Responsibilities

The Control Engine will be responsible for coordinating behaviour such as:

- Access state
- Boom opening
- Boom closing
- Traffic-light state
- Loop detector events
- Safety conditions
- Hold periods
- Close delays
- Re-trigger handling
- Tailgating conditions
- Operational event generation

The Control Engine must not communicate directly with TCP sockets or construct hardware protocol commands.

Those responsibilities belong to the Hardware Service and protocol layer.

---

# 7. Hardware Service

The Hardware Service provides the abstraction between Guardian business logic and physical equipment.

Conceptually:

    Guardian Control Engine

             |

             v

       Hardware Service

             |

      +------+------+
      |             |
      v             v

    DCON         Future
    Driver       Drivers

The Control Engine should request logical operations.

Examples:

    readInput()

    setOutput()

    pulseOutput()

    getControllerStatus()

The hardware layer determines how those operations are performed on the actual equipment.

---

# 8. Protocol Layer

The protocol layer implements manufacturer or protocol-specific communication.

The initial Guardian hardware implementation uses DCON communication.

The protocol layer is responsible for functionality such as:

- Building commands
- Formatting module addresses
- Parsing responses
- Validating responses
- Converting hardware responses into usable application values

Hardware protocol syntax should not be spread through Guardian controllers or views.

---

# 9. Initial Hardware Platform

Guardian's first hardware platform consists of:

## ICP DAS I-7188E2D

The I-7188E2D is the Ethernet-connected controller/gateway used by Guardian.

Guardian communicates with this device using TCP/IP.

Example architecture:

    Guardian Server
          |
       Ethernet
          |
          v
    I-7188E2D

The configured IP address and TCP port belong to the Ethernet-connected controller.

---

## ICP DAS I-7065

The I-7065 is the physical I/O module connected downstream of the I-7188E2D.

The approved Guardian architecture is:

    Guardian
       |
    Ethernet
       |
       v
    I-7188E2D
       |
     RS-485
       |
       v
    I-7065

The I-7065 therefore does not require its own IP address in this architecture.

It is identified and addressed on the RS-485/DCON network through its module address.

---

# 10. I-7065 I/O

For the current Guardian implementation, the I-7065 provides:

## Configurable Inputs

    IN1
    IN2
    IN3
    IN4

## Configurable Relay Outputs

    Relay 1
    Relay 2
    Relay 3
    Relay 4
    Relay 5

Guardian should store the logical purpose of these channels in configuration.

The application should therefore avoid assumptions such as:

    IN1 always means loop detector

or:

    Relay 2 always means green traffic light

Instead:

    Physical Channel
          |
          v
    Guardian Configuration
          |
          v
    Logical Function

Example:

    IN1
     |
     v
    Input Configuration
     |
     v
    Loop Detector

This allows installers to configure the system according to the physical installation.

---

# 11. Database Layer

Guardian uses MySQL/MariaDB for persistent application data.

The database stores configuration and operational information such as:

- Sites
- Users
- Controllers
- Hardware devices/modules
- Inputs
- Outputs
- Access configuration
- Residents
- Visitors
- Events
- Device status
- Last-seen information

Detailed table design is maintained in:

    02_DATABASE_DESIGN.md

---

# 12. Current Hardware Data Model

The existing Guardian development uses hardware device records containing information such as:

- Site
- Device name
- Device type
- IP address
- TCP port
- Module address
- Baud rate
- Protocol
- Location
- Description
- Online state
- Last seen
- Active state

Guardian also maintains separate configurable input and output records.

Input records currently include concepts such as:

    Device
    Input Number
    Input Name
    Input Type
    Current State
    Last Changed

Output records include:

    Device
    Relay Number
    Relay Name
    Relay Type
    Current State
    Last Changed

The database design document will define these structures in more detail.

---

# 13. Entrance Control Flow

The approved high-level entrance flow is:

    Access Request
          |
          v
    Authorization
          |
          v
    Access Granted
          |
          v
    Green Traffic Light
          |
          v
    Boom Opens
          |
          v
    Wait for Vehicle
          |
          v
    First Loop Activation
          |
          v
    Traffic Light RED
          |
          v
    Vehicle Continues
          |
          v
    Loop Clears
          |
          v
    Clearance / Hold Period
          |
          v
    Final Safety Check
          |
          v
    Close Delay
          |
          v
    Boom Closes
          |
          v
    Idle

The exact implementation is defined by the Guardian Control Engine rather than being distributed across access methods.

---

# 14. Trailer Re-Trigger Behaviour

A vehicle towing a trailer may produce the following physical sequence:

    Vehicle enters loop
          |
          v
    Loop ON
          |
          v
    Vehicle leaves loop
          |
          v
    Loop OFF
          |
      short gap
          |
          v
    Trailer enters loop
          |
          v
    Loop ON again

Guardian must not interpret the short OFF period as permission to immediately close the boom.

Therefore:

> Any valid loop re-trigger during the clearance period cancels or resets the pending close sequence.

The boom remains open.

The traffic light remains red after the first loop activation.

The closing sequence can only continue after the loop has remained clear for the configured period and the required safety conditions are satisfied.

---

# 15. Tailgating

Tailgating presents both a security and safety requirement.

Guardian must detect or identify potential tailgating behaviour where possible.

However:

> A security violation must never cause Guardian to intentionally close the boom onto a detected vehicle.

If another vehicle occupies or re-triggers the protected detection area, the physical control logic keeps the boom in a safe state.

The security layer can separately:

- Record the event
- Mark a potential tailgating condition
- Trigger a configured alarm
- Notify security
- Associate camera information in future implementations

This implements Guardian's principle:

> Every security decision must also be a safe decision.

---

# 16. Event Architecture

Guardian should generate meaningful events as the entrance changes state.

Examples:

    ACCESS_REQUESTED
    ACCESS_GRANTED
    ACCESS_DENIED

    BOOM_OPEN_REQUESTED
    BOOM_OPENED
    BOOM_CLOSE_REQUESTED
    BOOM_CLOSED

    LOOP_ACTIVE
    LOOP_CLEAR

    NO_TOUCH_ACTIVE

    CONTROLLER_ONLINE
    CONTROLLER_OFFLINE

    TAILGATING_DETECTED

The exact event names may evolve during Control Engine implementation.

The important architectural requirement is that significant state changes are observable and can be logged.

---

# 17. Status Monitoring

Guardian should maintain visibility into hardware health.

Relevant information includes:

- Controller online/offline state
- Last successful communication
- Response time
- Input states
- Output states
- Communication errors

The live dashboard will use this information to provide operational visibility.

---

# 18. Multi-Site Architecture

Guardian already associates hardware devices with a site.

This allows the architecture to evolve toward:

    Guardian Platform
          |
      +---+---+
      |       |
      v       v
    Site A   Site B
      |       |
      v       v
    Gates    Gates
      |       |
      v       v
    Hardware Hardware

Site isolation must remain part of the application architecture as Guardian grows.

---

# 19. Separation of Responsibilities

Guardian should maintain the following general boundaries:

## Views

Presentation only.

## Controllers

HTTP request/response handling.

## Models

Database interaction.

## Services

Application/business operations.

## Control Engine

Entrance state and physical-control decisions.

## Hardware Service

Logical hardware operations.

## Protocol Driver

Hardware-specific command and response handling.

## Gateway / Transport

TCP/socket communication.

This separation is a core maintainability requirement.

---

# 20. Current Communication Example

The current development hardware has successfully demonstrated communication using the following path:

    Guardian
       |
       v
    TCP Connection
       |
       v
    192.168.x.x : configured port
       |
       v
    I-7188E2D
       |
       v
    DCON request
       |
       v
    I-7065

During development, a module-information request produced a valid response identifying the 7065 module.

This demonstrates that the basic communication path can operate successfully.

The next objective is to build reliable live I/O reading and output control on top of this communication layer.

---

# 21. Architectural Rule

No access module should directly manipulate physical hardware.

For example, ANPR logic should not contain:

    open relay 1
    wait
    change relay 2
    wait
    close relay

Instead:

    ANPR
      |
      v
    Access Granted
      |
      v
    Guardian Control Engine
      |
      v
    Hardware Service
      |
      v
    Physical Hardware

This ensures that ANPR, no-touch, QR, visitors, residents, and future access methods all receive identical entrance-control behaviour.

---

# 22. Future Expansion

The architecture is intended to permit future support for:

- Additional ICP DAS modules
- Additional controller manufacturers
- Modbus TCP
- Modbus RTU
- API-controlled hardware
- Cloud-connected controllers
- Remote diagnostics
- Mobile applications
- ANPR platforms
- Additional sensors
- Additional safety systems

Future integrations should use the existing abstraction layers rather than bypassing them.

---

# Related Documents

- `README.md`
- `00_PROJECT_VISION.md`
- `02_DATABASE_DESIGN.md`
- `03_HARDWARE_ARCHITECTURE.md`
- `04_CONTROL_ENGINE.md`
- `05_ACCESS_METHODS.md`
- `07_DEVELOPER_GUIDE.md`
- `10_DECISIONS.md`

---

# Revision History

| Version | Date | Description |
|---|---|---|
| 0.1 | 2026-07-23 | Initial Guardian system architecture documented for Sprint #001. |