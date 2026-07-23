---
Title: Guardian Database Design
Document: 02_DATABASE_DESIGN.md
Version: 0.1
Status: Draft
Project: Guardian
Last Updated: 2026-07-23
Related Sprint: Sprint #001
---

# Guardian Database Design

## Guardian – Secure Access. Intelligent Control.

## 1. Purpose

This document defines the database architecture used to represent Guardian entrances, hardware controllers, I/O modules, physical channels, and standardized logical I/O functions.

The database must reflect the real hardware architecture rather than forcing different hardware components into a single generic record.

The design follows the Guardian principles of:

- Security and safe operation
- Configuration before customisation
- Hardware independence
- Modular design
- Scalability
- Traceability

---

# 2. Core Hierarchy

The approved Guardian hierarchy is:

    SITE
      |
      v
    ENTRANCE
      |
      v
    CONTROLLER
      |
      v
    I/O MODULE
      |
      +----------------+
      |                |
      v                v
    INPUTS           OUTPUTS
      |                |
      +-------+--------+
              |
              v
      GUARDIAN I/O TYPES

In database terms:

    tbl_sites
        |
        v
    tbl_entrances
        |
        v
    tbl_hardware_controllers
        |
        v
    tbl_hardware_modules
        |
        +---------------------------+
        |                           |
        v                           v
    tbl_hardware_inputs       tbl_hardware_outputs
        |                           |
        +-------------+-------------+
                      |
                      v
            tbl_hardware_io_types

---

# 3. Design Principle: Entrance First

Guardian controls an entrance, not a relay.

For example:

    Main Entrance
         |
         v
    Guardian Control Engine
         |
         v
    Hardware Configuration
         |
         v
    Controller
         |
         v
    I/O Module
         |
         v
    Physical I/O

Higher-level Guardian functionality should therefore operate using an entrance identifier.

Conceptually:

    grantAccess($entranceId)

rather than:

    activateRelay($deviceId, 1)

The hardware layer resolves the logical request to the configured physical equipment.

---

# 4. tbl_entrances

`tbl_entrances` represents physical access points managed by Guardian.

Examples include:

- Main Entrance
- Visitor Entrance
- Exit Gate
- Clubhouse Entrance
- Pedestrian Gate

Proposed structure:

    Id
    FkSiteId

    EntranceName
    EntranceType

    Location
    Description

    IsActive
    DisplayOrder

    CreatedAt
    UpdatedAt

Proposed SQL:

    CREATE TABLE tbl_entrances (
        Id INT NOT NULL AUTO_INCREMENT,

        FkSiteId INT NOT NULL,

        EntranceName VARCHAR(100) NOT NULL,

        EntranceType VARCHAR(30) NOT NULL DEFAULT 'VEHICLE',

        Location VARCHAR(150) NULL,
        Description TEXT NULL,

        IsActive TINYINT(1) NOT NULL DEFAULT 1,
        DisplayOrder INT NULL,

        CreatedAt DATETIME NULL,
        UpdatedAt DATETIME NULL,

        PRIMARY KEY (Id),

        KEY idx_entrance_site (FkSiteId)
    );

---

# 5. Entrance Types

Guardian should use standardized entrance types.

Initial supported types:

    VEHICLE
    PEDESTRIAN
    MIXED

## VEHICLE

A vehicle entrance may use functionality such as:

- Boom gates
- Loop detectors
- ANPR
- Traffic lights
- Tailgating detection
- Trailer handling

## PEDESTRIAN

A pedestrian entrance may eventually use:

- Door control
- Turnstiles
- QR access
- Access readers
- Door monitoring

## MIXED

A mixed entrance supports installations where vehicle and pedestrian access functionality share an entrance configuration.

Entrance type gives the Guardian Control Engine operational context.

---

# 6. tbl_hardware_controllers

A hardware controller represents network-connected hardware Guardian communicates with directly.

For the initial Guardian installation:

    ICP DAS I-7188E2D = Controller

Proposed structure:

    Id
    FkSiteId
    FkEntranceId

    ControllerName
    ControllerType

    IPAddress
    TcpPort

    Protocol
    Timeout

    Location
    Description

    IsOnline
    LastSeen

    DisplayOrder
    IsActive

    CreatedAt
    UpdatedAt

Proposed SQL:

    CREATE TABLE tbl_hardware_controllers (
        Id INT NOT NULL AUTO_INCREMENT,

        FkSiteId INT NOT NULL,
        FkEntranceId INT NOT NULL,

        ControllerName VARCHAR(100) NOT NULL,
        ControllerType VARCHAR(50) NOT NULL,

        IPAddress VARCHAR(50) NOT NULL,
        TcpPort INT NOT NULL DEFAULT 10002,

        Protocol VARCHAR(20) NULL DEFAULT 'DCON',

        Timeout INT NOT NULL DEFAULT 3000,

        Location VARCHAR(100) NULL,
        Description TEXT NULL,

        IsOnline TINYINT(1) NOT NULL DEFAULT 0,
        LastSeen DATETIME NULL,

        DisplayOrder INT NULL,

        IsActive TINYINT(1) NOT NULL DEFAULT 1,

        CreatedAt DATETIME NULL,
        UpdatedAt DATETIME NULL,

        PRIMARY KEY (Id),

        KEY idx_controller_site (FkSiteId),
        KEY idx_controller_entrance (FkEntranceId)
    );

---

# 7. Controller Network Configuration

Network settings belong to the controller.

For example:

    I-7188E2D

    IP Address
    TCP Port
    Timeout

The downstream I-7065 must not be forced to contain these values.

This corrects an architectural limitation in the original `tbl_hardware_devices` design.

---

# 8. tbl_hardware_modules

A hardware module represents an addressable module connected downstream of a Guardian controller.

For the initial hardware platform:

    ICP DAS I-7065 = I/O Module

Proposed structure:

    Id
    FkControllerId

    ModuleName
    ModuleType

    ModuleAddress

    BaudRate
    Protocol

    Location
    Description

    IsOnline
    LastSeen

    DisplayOrder
    IsActive

    CreatedAt
    UpdatedAt

Proposed SQL:

    CREATE TABLE tbl_hardware_modules (
        Id INT NOT NULL AUTO_INCREMENT,

        FkControllerId INT NOT NULL,

        ModuleName VARCHAR(100) NOT NULL,
        ModuleType VARCHAR(50) NOT NULL,

        ModuleAddress CHAR(2) NOT NULL DEFAULT '01',

        BaudRate INT NULL,
        Protocol VARCHAR(20) NULL DEFAULT 'DCON',

        Location VARCHAR(100) NULL,
        Description TEXT NULL,

        IsOnline TINYINT(1) NOT NULL DEFAULT 0,
        LastSeen DATETIME NULL,

        DisplayOrder INT NULL,

        IsActive TINYINT(1) NOT NULL DEFAULT 1,

        CreatedAt DATETIME NULL,
        UpdatedAt DATETIME NULL,

        PRIMARY KEY (Id),

        KEY idx_module_controller (FkControllerId)
    );

---

# 9. Controller and Module Relationship

The relationship is:

    I-7188E2D
    Controller
         |
         | RS-485 / DCON
         |
         v
    I-7065
    Module

Guardian communicates over Ethernet/TCP with the controller.

The controller provides access to the downstream RS-485/DCON module.

Therefore:

    Controller owns:
        IPAddress
        TcpPort
        Timeout

    Module owns:
        ModuleAddress
        BaudRate
        ModuleType

---

# 10. tbl_hardware_io_types

Guardian uses a standard catalogue of logical I/O functions.

This prevents inconsistent naming such as:

    Boom
    boom
    Boom Gate
    Open Boom
    gate relay

Instead, Guardian uses a stable internal code.

Proposed structure:

    Id

    Code
    Name

    Direction
    Category

    Description

    IsSafetyCritical

    IsActive
    DisplayOrder

Proposed SQL:

    CREATE TABLE tbl_hardware_io_types (
        Id INT NOT NULL AUTO_INCREMENT,

        Code VARCHAR(50) NOT NULL,
        Name VARCHAR(100) NOT NULL,

        Direction ENUM('INPUT', 'OUTPUT') NOT NULL,

        Category VARCHAR(50) NULL,

        Description VARCHAR(255) NULL,

        IsSafetyCritical TINYINT(1) NOT NULL DEFAULT 0,

        IsActive TINYINT(1) NOT NULL DEFAULT 1,

        DisplayOrder INT NULL,

        PRIMARY KEY (Id),

        UNIQUE KEY uq_hardware_io_type_code (Code),

        KEY idx_io_direction (Direction)
    );

---

# 11. Initial Guardian I/O Catalogue

The initial catalogue should remain intentionally small and expand as real installation requirements are identified.

## Input Functions

    NO_TOUCH
    LOOP_DETECTOR
    DOOR_ALARM
    FIRE_ALARM

## Output Functions

    BOOM_OPEN
    GREEN_LIGHT
    RED_LIGHT
    ALARM

These codes describe functions rather than physical channel numbers.

Additional functions can be added without changing application source code where the architecture supports them.

---

# 12. Functional Naming Convention

Guardian I/O codes should:

- Use uppercase
- Use underscores between words
- Describe the actual function
- Remain stable once released

For example:

    BOOM_OPEN

is preferred over:

    BOOM

because future boom installations may require additional functions such as:

    BOOM_OPEN
    BOOM_CLOSE
    BOOM_STOP
    BOOM_STATUS_OPEN
    BOOM_STATUS_CLOSED
    BOOM_FAULT

These additional functions are not required for Sprint #001 but illustrate why functional naming is important.

---

# 13. tbl_hardware_inputs

The existing `tbl_hardware_inputs` table currently associates inputs with `FkDeviceId`.

Under the approved architecture, inputs belong to an I/O module.

The target structure becomes:

    Id
    FkModuleId
    FkIoTypeId

    InputNumber
    InputName

    CurrentState
    LastChanged

    IsActive

Proposed target SQL:

    CREATE TABLE tbl_hardware_inputs (
        Id INT NOT NULL AUTO_INCREMENT,

        FkModuleId INT NOT NULL,
        FkIoTypeId INT NULL,

        InputNumber INT NOT NULL,

        InputName VARCHAR(100) NULL,

        CurrentState TINYINT(1) NOT NULL DEFAULT 0,

        LastChanged DATETIME NULL,

        IsActive TINYINT(1) NOT NULL DEFAULT 1,

        PRIMARY KEY (Id),

        KEY idx_input_module (FkModuleId),
        KEY idx_input_type (FkIoTypeId),

        UNIQUE KEY uq_module_input (
            FkModuleId,
            InputNumber
        )
    );

---

# 14. tbl_hardware_outputs

The existing output table also currently uses `FkDeviceId`.

Outputs should instead belong to an I/O module.

Target structure:

    Id
    FkModuleId
    FkIoTypeId

    RelayNumber
    RelayName

    CurrentState
    LastChanged

    IsActive

Proposed target SQL:

    CREATE TABLE tbl_hardware_outputs (
        Id INT NOT NULL AUTO_INCREMENT,

        FkModuleId INT NOT NULL,
        FkIoTypeId INT NULL,

        RelayNumber INT NOT NULL,

        RelayName VARCHAR(100) NULL,

        CurrentState TINYINT(1) NOT NULL DEFAULT 0,

        LastChanged DATETIME NULL,

        IsActive TINYINT(1) NOT NULL DEFAULT 1,

        PRIMARY KEY (Id),

        KEY idx_output_module (FkModuleId),
        KEY idx_output_type (FkIoTypeId),

        UNIQUE KEY uq_module_relay (
            FkModuleId,
            RelayNumber
        )
    );

---

# 15. Physical Channel Versus Logical Function

The database deliberately separates physical and logical information.

Example:

    tbl_hardware_inputs

    InputNumber = 1
           |
           v
    FkIoTypeId
           |
           v
    tbl_hardware_io_types
           |
           v
    Code = LOOP_DETECTOR

Guardian therefore knows:

    Module 1
       |
       v
    Physical IN1
       |
       v
    LOOP_DETECTOR

Another installation could configure:

    Module 4
       |
       v
    Physical IN3
       |
       v
    LOOP_DETECTOR

The Control Engine does not need to know that the wiring changed.

---

# 16. Example Installation

The following is an example only and is not a hard-coded Guardian mapping.

    Main Entrance

        Controller
        ----------------------
        I-7188E2D
        Ethernet/TCP

              |
              v

        Module
        ----------------------
        I-7065
        Address 01

              |
              +-----------------------+
              |                       |
              v                       v

           INPUTS                  OUTPUTS

        IN1 LOOP_DETECTOR       Relay 1 BOOM_OPEN

        IN2 NO_TOUCH            Relay 2 GREEN_LIGHT

        IN3 DOOR_ALARM          Relay 3 RED_LIGHT

        IN4 FIRE_ALARM          Relay 4 ALARM

                                Relay 5 unassigned

Actual mappings are configured by the installer.

---

# 17. Unassigned Channels

Guardian must allow physical channels to exist without an assigned logical function.

For example:

    Relay 5
    FkIoTypeId = NULL

This means:

    Physical relay exists
    but
    Guardian function is not assigned

The same applies to unused input channels.

This avoids inventing functions such as `SPARE` simply to satisfy database constraints.

---

# 18. I/O Validation

Guardian should validate logical assignments.

For example:

    NO_TOUCH
    Direction = INPUT

must not be assigned to:

    Relay 2

Likewise:

    GREEN_LIGHT
    Direction = OUTPUT

must not be assigned to:

    IN3

The application layer must enforce this rule.

---

# 19. Safety-Critical I/O

`tbl_hardware_io_types` contains:

    IsSafetyCritical

This allows Guardian to identify functions requiring additional operational consideration.

Potential examples include:

    LOOP_DETECTOR
    FIRE_ALARM
    safety-related inputs

The exact safety classification will be defined as each function is implemented and validated.

The presence of this field does not by itself define Control Engine behaviour.

---

# 20. Hardware Status

Controller and module status are deliberately separate.

Example:

    I-7188E2D
    ONLINE

        |
        +-- I-7065 Address 01
            ONLINE

A future condition may be:

    Controller ONLINE

        |
        +-- Module 01 ONLINE
        |
        +-- Module 02 OFFLINE

This provides more accurate Guardian diagnostics.

---

# 21. Current Database

Guardian currently has:

    tbl_hardware_devices
    tbl_hardware_inputs
    tbl_hardware_outputs

The existing `tbl_hardware_devices` structure includes:

    Id
    FkSiteId
    DeviceName
    DeviceType
    IPAddress
    TcpPort
    ModuleAddress
    BaudRate
    Protocol
    Location
    Description
    IsOnline
    LastSeen
    CreatedAt
    UpdatedAt
    IsActive
    Timeout
    DisplayOrder

This structure was useful during initial hardware communication development.

However, it combines controller and module responsibilities.

---

# 22. Existing Design Limitation

For example:

    IPAddress
    TcpPort
    Timeout

describe the:

    I-7188E2D

while:

    ModuleAddress
    BaudRate

describe the:

    I-7065

Placing these values in one record makes it difficult to represent:

- Multiple modules behind one controller
- Separate controller/module health
- Modules without IP addresses
- Additional module types
- Future hardware architectures

The database will therefore evolve to match the approved hardware architecture.

---

# 23. Migration Strategy

Guardian will migrate incrementally.

The existing tables must not be deleted immediately.

Recommended sequence:

    STEP 1
    Create tbl_entrances

            |
            v

    STEP 2
    Create tbl_hardware_controllers

            |
            v

    STEP 3
    Create tbl_hardware_modules

            |
            v

    STEP 4
    Create tbl_hardware_io_types

            |
            v

    STEP 5
    Migrate existing hardware configuration

            |
            v

    STEP 6
    Update Guardian Models

            |
            v

    STEP 7
    Update HardwareService

            |
            v

    STEP 8
    Update device/controller management UI

            |
            v

    STEP 9
    Update inputs and outputs

            |
            v

    STEP 10
    Verify real hardware communication

            |
            v

    STEP 11
    Retire obsolete tbl_hardware_devices structure

No existing table should be removed until the replacement implementation has been tested against the real hardware.

---

# 24. Existing Hardware Migration

An existing combined record such as:

    DeviceName      Main Gate Controller
    DeviceType      ICP DAS
    IPAddress       192.168.x.x
    TcpPort         10002
    ModuleAddress   01
    BaudRate        ...
    Protocol        DCON

will eventually become two records.

Controller:

    tbl_hardware_controllers

    ControllerName
    ControllerType = ICP DAS I-7188E2D

    IPAddress
    TcpPort
    Timeout

and module:

    tbl_hardware_modules

    ModuleName
    ModuleType = ICP DAS I-7065

    ModuleAddress
    BaudRate
    Protocol

This reflects the real physical architecture.

---

# 25. Relationship Summary

The target Guardian relationship is:

    tbl_sites
        |
        | 1:N
        v
    tbl_entrances
        |
        | 1:N
        v
    tbl_hardware_controllers
        |
        | 1:N
        v
    tbl_hardware_modules
        |
        +----------------+
        |                |
        | 1:N            | 1:N
        v                v
    tbl_hardware_inputs  tbl_hardware_outputs
        |                |
        | N:1            | N:1
        +-------+--------+
                |
                v
        tbl_hardware_io_types

---

# 26. Control Engine Relationship

The Control Engine should not need to know the physical relay number for normal operation.

Conceptually:

    Control Engine

        activate:
        BOOM_OPEN

            |
            v

    Entrance Hardware Configuration

            |
            v

    Controller

            |
            v

    Module

            |
            v

    Configured Output

            |
            v

    Physical Relay

This abstraction is a central part of Guardian's design.

---

# 27. Multi-Site Support

All entrances belong to a Guardian site.

This allows:

    Guardian
       |
       +-- Site A
       |     |
       |     +-- Entrance 1
       |     +-- Entrance 2
       |
       +-- Site B
             |
             +-- Entrance 1

Controllers are associated with both their site and entrance.

Site access controls must continue to be enforced by the application.

---

# 28. Naming Conventions

Guardian database conventions currently use:

    tbl_

for application tables.

Primary keys:

    Id

Foreign keys:

    Fk<Entity>Id

Examples:

    FkSiteId
    FkEntranceId
    FkControllerId
    FkModuleId
    FkIoTypeId

Boolean/state fields use descriptive names such as:

    IsActive
    IsOnline
    IsSafetyCritical

Timestamps use:

    CreatedAt
    UpdatedAt
    LastSeen
    LastChanged

---

# 29. Sprint #001 Database Scope

Sprint #001 should concentrate on the minimum database architecture required to test the real Guardian hardware.

Priority:

    Site
      |
      v
    Entrance
      |
      v
    Controller
      |
      v
    Module
      |
      +-- Inputs
      |
      +-- Outputs

followed by:

    7188E2D communication
            |
            v
    7065 communication
            |
            v
    Live input states
            |
            v
    Manual relay testing
            |
            v
    No-Touch test
            |
            v
    Loop Detector test

The Control Engine will be implemented after the underlying hardware layer is verified.

---

# 30. Approved Database Direction

The following database direction is approved:

> Guardian controls entrances rather than physical relays.

> Network controllers and downstream I/O modules are separate entities.

> Physical channels and logical functions are separate concepts.

> Guardian uses standardized logical I/O codes.

> Hardware configuration determines how logical Guardian functions map to physical channels.

> Existing hardware tables will be migrated incrementally rather than discarded before replacement functionality is tested.

---

# Related Documents

- `README.md`
- `00_PROJECT_VISION.md`
- `01_SYSTEM_ARCHITECTURE.md`
- `03_HARDWARE_ARCHITECTURE.md`
- `04_CONTROL_ENGINE.md`
- `07_DEVELOPER_GUIDE.md`
- `10_DECISIONS.md`

---

# Revision History

| Version | Date | Description |
|---|---|---|
| 0.1 | 2026-07-23 | Initial target database architecture documented for Sprint #001. |