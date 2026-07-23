---
Title: Guardian Documentation
Document: README.md
Version: 0.1
Status: Draft
Project: Guardian
Last Updated: 2026-07-23
Related Sprint: Sprint #001
---

# Guardian Documentation

## Guardian – Secure Access. Intelligent Control.

Guardian is an intelligent, reliable, and secure access control platform designed for residential estates, commercial properties, industrial facilities, and other controlled-access environments.

Guardian combines access control, industrial automation, hardware integration, event-driven decision-making, monitoring, and management within a single platform.

---

## Mission Statement

> Guardian provides intelligent, reliable, and secure access control solutions that protect people, property, and assets while ensuring safe operation through robust engineering and event-driven automation.

---

# Guardian Core Principles

## Principle 0 – Design Before Development

> If a feature cannot be explained clearly in the documentation, it is not ready to be implemented.

Guardian features must first be understood and documented before implementation begins.

---

## Principle 1 – Security and Safety

> Every security decision must also be a safe decision.

Guardian exists to provide secure access control while ensuring that security actions do not create unsafe operating conditions for people, vehicles, or equipment.

Security and safety are complementary requirements of the Guardian platform.

---

## Principle 2 – One Control Engine

All access methods must ultimately use the Guardian Control Engine.

Examples include:

- ANPR
- No-touch sensors
- QR access
- Visitor access
- Resident access
- Manual operator access
- Remote access commands

This ensures consistent control behaviour regardless of how access was requested.

---

## Principle 3 – Event-Driven by Design

Guardian responds to real events and changes in state.

Examples include:

- Access granted
- Access denied
- Loop activated
- Loop cleared
- No-touch activated
- Boom opened
- Boom closed
- Controller offline
- Safety input activated
- Tailgating condition detected

Control decisions should be based on the current system state rather than assumptions.

---

## Principle 4 – Hardware Independence

Guardian business logic must remain separate from individual hardware manufacturers and communication protocols.

Hardware communication is handled through the Guardian Hardware Service and protocol drivers.

This allows additional controllers and protocols to be supported in the future without redesigning the Guardian Control Engine.

---

## Principle 5 – Configuration Before Customisation

Installer and customer requirements should be configurable wherever practical.

Examples include:

- Input assignments
- Relay assignments
- Loop behaviour
- Traffic light behaviour
- Hold times
- Close delays
- Tailgating behaviour

Customer-specific source code should be avoided where configuration can provide the required behaviour.

---

## Principle 6 – Event Logging

Important operational, security, access, and hardware events must be recorded.

This provides Guardian with an audit trail for:

- Security investigations
- Troubleshooting
- Reporting
- System diagnostics
- Operational history

---

## Principle 7 – Modular by Design

Guardian components must have clearly defined responsibilities.

The web interface, business logic, Control Engine, hardware communication, protocol implementation, and data storage should remain appropriately separated.

---

## Principle 8 – Built to Scale

Guardian must be capable of growing from a single controlled entrance to multiple entrances, sites, and estates without requiring fundamental architectural redesign.

---

## Principle 9 – Documentation Is Part of the Product

Documentation is developed alongside Guardian.

Architecture, important decisions, hardware integration, control logic, installation requirements, and changes must be documented as the platform evolves.

---

# Approved Hardware Architecture

The current Guardian hardware architecture is:

    CodeIgniter 4
          |
          v
    Guardian Services / Control Engine
          |
          v
    Hardware Service
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
          +-- Digital Inputs IN1-IN4
          |
          +-- Relay Outputs 1-5

The I-7188E2D is the Ethernet-connected controller.

The I-7065 does not require its own IP address in this architecture. Guardian reaches the I-7065 through the I-7188E2D using the RS-485/DCON communication path.

Input and output functions are intended to be configurable rather than permanently hard-coded.

---

# Current Boom-Control Concept

The approved high-level sequence is:

    Access Request
          |
          v
    Guardian Authorizes Access
          |
          v
    Traffic Light GREEN
          |
          v
    Boom Opens
          |
          v
    First Loop Detection
          |
          v
    Traffic Light RED
          |
          v
    Vehicle / Trailer monitored
          |
          v
    Loop Clears
          |
          v
    Configurable Hold Period
          |
          v
    Final Safety Check
          |
          v
    Configurable Close Delay
          |
          v
    Boom Closes
          |
          v
    Return to Idle

If the loop is triggered again during the clearance period, the pending close sequence must be cancelled or reset.

This is important for vehicles towing trailers: a short clear period between the vehicle and trailer must not cause the boom to close.

Potential tailgating conditions must not cause Guardian to close the boom onto a vehicle. Guardian keeps the system safe while recording the security event and allowing configured alarm or notification behaviour.

Detailed state-machine behaviour will be maintained in `04_CONTROL_ENGINE.md`.

---

# Documentation Index

## Product and Architecture

- [00 – Project Vision](00_PROJECT_VISION.md)
- [01 – System Architecture](01_SYSTEM_ARCHITECTURE.md)
- [02 – Database Design](02_DATABASE_DESIGN.md)
- [03 – Hardware Architecture](03_HARDWARE_ARCHITECTURE.md)
- [04 – Guardian Control Engine](04_CONTROL_ENGINE.md)
- [05 – Access Methods](05_ACCESS_METHODS.md)

## Installation and Development

- [06 – Installation Guide](06_INSTALLATION_GUIDE.md)
- [07 – Developer Guide](07_DEVELOPER_GUIDE.md)

## Project Management

- [08 – Roadmap](08_ROADMAP.md)
- [09 – Changelog](09_CHANGELOG.md)
- [10 – Architectural Decisions](10_DECISIONS.md)
- [Sprint Register](SPRINTS.md)

---

# Development Workflow

Guardian follows the development workflow:

    Requirement
        |
        v
      Design
        |
        v
    Documentation
        |
        v
    Implementation
        |
        v
      Testing
        |
        v
    Git Commit

---

# Current Development Sprint

## Sprint #001 – Guardian Foundation

The immediate objective is to establish the hardware and software foundation required for Guardian to communicate with the real access-control equipment.

Primary objectives include:

- Controller management
- I/O module management
- Configurable inputs
- Configurable outputs
- Hardware communication
- Live hardware diagnostics
- Live I/O monitoring
- Manual output testing
- ICP DAS I-7188E2D integration
- ICP DAS I-7065 integration
- No-touch sensor testing
- Loop detector testing

The Guardian Control Engine will then be implemented on top of the verified hardware communication layer.

---

# Revision History

| Version | Date | Description |
|---|---|---|
| 0.1 | 2026-07-23 | Initial Guardian documentation foundation created for Sprint #001. |