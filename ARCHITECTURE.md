# Architecture: oxideshop-demodata-installer

## Purpose

Provides a Symfony Console command for installing OXID eShop demo data from modules. Each module can ship its own demo data SQL files; this installer discovers and executes them in the correct order.

## Directory Structure

```
src/Framework/Module/Demodata/
  Demodata_Command.php           — Symfony Console command: php bin/oe-console oe:module:install-demo-data
  Demodata_Dao.php               — Data access: executes SQL demo data files against the database
  Demodata_Dao_Interface.php     — Interface for the DAO (allows mocking in tests)
  Exception/
    Aggregate_Exception.php      — Collects multiple demodata exceptions into one
    Aggregate_Exception_Interface.php
    Demodata_Exception.php       — Thrown when a single demo data file fails
```

## Key Design Decisions

- **Aggregate exceptions**: All failures during a batch install are collected into `Aggregate_Exception` so that one module failure does not abort the entire install; all errors are reported at once
- **DAO interface**: `Demodata_Dao_Interface` allows the command to be tested without a real database

## Extension Points

- A module provides demo data by placing SQL files in a conventionally named directory; the DAO discovers them via the module registry
