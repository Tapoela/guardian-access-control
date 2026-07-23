---
Title: Guardian Project Vision
Document: 00_PROJECT_VISION.md
Version: 0.1
Status: Draft
Project: Guardian
Last Updated: 2026-07-23
Related Sprint: Sprint #001
---

# Guardian Project Vision

## Guardian – Secure Access. Intelligent Control.

Guardian is being developed as a professional, intelligent access-control platform that combines security, safe operation, industrial hardware control, access management, monitoring, and automation within a unified system.

---

# 1. Mission Statement

> Guardian provides intelligent, reliable, and secure access control solutions that protect people, property, and assets while ensuring safe operation through robust engineering and event-driven automation.

---

# 2. Product Vision

Guardian's vision is to provide a flexible access-control platform capable of managing the complete access lifecycle of an entrance.

Guardian is not intended to simply activate a relay to open a gate.

The platform must understand and manage the complete operational process surrounding access:

    Access Request
          |
          v
    Authentication / Authorization
          |
          v
    Access Decision
          |
          v
    Physical Access Control
          |
          v
    Vehicle / Person Movement
          |
          v
    Safety Monitoring
          |
          v
    Secure Completion
          |
          v
    Event Logging

Guardian therefore combines traditional access control with intelligent physical control and event-driven automation.

---

# 3. Security and Safety

Security and safe operation are both fundamental requirements of Guardian.

The governing principle is:

> Every security decision must also be a safe decision.

Guardian must protect entrances against unauthorized access without creating unsafe conditions for people, vehicles, or equipment.

For example, a potential tailgating event is a security concern, but Guardian must not respond by closing a boom onto the second vehicle.

Instead, the Control Engine must maintain safe physical operation while separately handling the security event through logging and configured responses.

This separation between safe physical control and security-event handling is fundamental to Guardian.

---

# 4. Design Before Development

Guardian follows the principle:

> If a feature cannot be explained clearly in the documentation, it is not ready to be implemented.

Features should therefore progress through:

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
      Release

This process is intended to reduce ambiguous behaviour, duplicated logic, undocumented assumptions, and unnecessary rework.

---

# 5. Product Objectives

Guardian is being designed to provide:

- Secure access control
- Safe physical entrance operation
- Reliable hardware communication
- Centralized access decisions
- Configurable entrance behaviour
- Real-time hardware monitoring
- Complete event logging
- Hardware diagnostics
- Installer-friendly configuration
- Multi-site capability
- Expandable hardware support
- Maintainable software architecture

---

# 6. Target Environments

Guardian is intended to support controlled-access environments such as:

- Residential estates
- Residential complexes
- Gated communities
- Commercial properties
- Business parks
- Industrial facilities
- Clubs and private facilities
- Other controlled entrances

The architecture should support installations ranging from a single entrance to multiple entrances across multiple sites.

---

# 7. Guardian Control Philosophy

All access methods should ultimately feed into the same Guardian Control Engine.

Examples may include:

- ANPR
- No-touch sensors
- QR access
- Visitor access
- Resident access
- Manual security-operator access
- Remote access commands

The access method determines how an access request originates.

It should not independently determine how the physical entrance behaves.

The Guardian Control Engine remains responsible for the operational sequence.

This prevents different access methods from implementing conflicting boom, loop, traffic-light, or safety behaviour.

---

# 8. Event-Driven Operation

Guardian is designed around events and system states.

Examples include:

- Access requested
- Access granted
- Access denied
- No-touch input activated
- Loop activated
- Loop cleared
- Boom command issued
- Safety input activated
- Controller communication lost
- Tailgating condition detected

The Control Engine responds according to the current state of the entrance and the event received.

This provides a more predictable architecture than distributing unrelated timing logic throughout controllers, JavaScript, hardware drivers, or access modules.

---

# 9. Hardware Independence

Guardian should not be permanently tied to one hardware manufacturer.

The initial hardware platform is based on ICP DAS equipment, specifically the I-7188E2D and I-7065.

The approved initial communication architecture is:

    Guardian
       |
       v
    Hardware Service
       |
       v
    Protocol Driver
       |
       v
    ICP DAS I-7188E2D
       |
       v
    RS-485 / DCON
       |
       v
    ICP DAS I-7065

Hardware-specific communication should remain below Guardian's business and access-control logic.

This creates a path for additional hardware and protocols to be supported in future versions.

---

# 10. Configuration Before Customisation

Guardian should avoid hard-coded installation behaviour wherever practical.

Installers should eventually be able to configure items such as:

- Input purpose
- Relay purpose
- Entrance behaviour
- Loop behaviour
- Traffic-light behaviour
- Hold periods
- Close delays
- Tailgating responses
- Enabled safety features

For example, Guardian should understand that an input has the role `Loop Detector` rather than assuming that every installation always uses a specific physical input number for that purpose.

This approach allows the same Guardian software to support different site configurations.

---

# 11. Auditability

Guardian should maintain a reliable operational history.

Significant events should be recorded so that administrators and technical personnel can determine:

- What happened
- When it happened
- Where it happened
- Which entrance was involved
- What initiated the event
- What decision Guardian made
- What hardware action occurred

This information is important for security investigations, troubleshooting, reporting, diagnostics, and future analytics.

---

# 12. User Experience

Although Guardian may contain complex automation internally, the user experience should remain clear and practical.

Different users require different levels of information.

Examples include:

### Security Personnel

Security personnel require immediate operational information and simple access-control actions.

### Estate or Site Management

Management requires visibility into access activity, residents, visitors, incidents, devices, and reports.

### Installers and Technical Personnel

Installers require controller configuration, I/O assignment, diagnostics, communication testing, and system-health information.

Guardian should expose the correct level of complexity to each role.

---

# 13. Scalability

Guardian should be capable of evolving through the following deployment levels:

    Single Entrance
          |
          v
    Multiple Entrances
          |
          v
    Single Managed Site
          |
          v
    Multiple Sites
          |
          v
    Centrally Managed Platform

The architecture should avoid assumptions that restrict Guardian to one gate, one controller, or one estate.

---

# 14. Long-Term Direction

Guardian is intended to evolve into a broader access-management platform.

Potential future capabilities include:

- ANPR integration
- Resident self-service
- Visitor management
- Mobile applications
- QR access
- Delivery management
- Cloud monitoring
- Multi-site administration
- Remote diagnostics
- Subscription management
- Notifications
- Advanced reporting
- Intelligent event analysis
- Additional controller manufacturers and protocols

Future functionality must continue to comply with Guardian's core principles and architecture.

---

# 15. Definition of Success

Guardian succeeds when it provides customers with a system that is:

**Secure**

Unauthorized access is controlled and security events are visible.

**Safe**

Physical entrance operation considers vehicles, people, equipment, and detected hazards.

**Reliable**

The system performs consistently and hardware failures or communication problems can be identified.

**Understandable**

Operators and installers can clearly understand the state of the entrance and system.

**Configurable**

Different installations can be supported without rewriting Guardian.

**Traceable**

Important decisions and events can be investigated afterwards.

**Maintainable**

The architecture remains understandable as the platform grows.

**Scalable**

The same core platform can support larger installations without fundamental redesign.

---

# 16. Guardian Values

## Integrity

Guardian should provide information and behaviour that customers can trust.

## Reliability

Access-control infrastructure must operate consistently and predictably.

## Simplicity

Complex engineering should result in a straightforward user experience.

## Innovation

New technology should be adopted when it provides meaningful operational, security, or customer value.

## Quality

Engineering quality takes precedence over short-term shortcuts that create long-term instability.

## Partnership

Guardian should support long-term relationships with customers, installers, and sites through dependable products and support.

---

# 17. Current Development Priority

The immediate development priority is to establish reliable communication with the first Guardian hardware platform:

    CodeIgniter 4
          |
          v
    Guardian Services
          |
          v
    Hardware Service
          |
          v
    ICP DAS I-7188E2D
          |
          v
    RS-485 / DCON
          |
          v
    ICP DAS I-7065
          |
          +-- IN1-IN4
          |
          +-- Relay 1-5

Once live input and output communication has been verified, the Guardian Control Engine can be implemented against a known and tested hardware layer.

---

# Related Documents

- `README.md`
- `01_SYSTEM_ARCHITECTURE.md`
- `03_HARDWARE_ARCHITECTURE.md`
- `04_CONTROL_ENGINE.md`
- `08_ROADMAP.md`
- `10_DECISIONS.md`
- `SPRINTS.md`

---

# Revision History

| Version | Date | Description |
|---|---|---|
| 0.1 | 2026-07-23 | Initial Guardian project vision established during Sprint #001. |