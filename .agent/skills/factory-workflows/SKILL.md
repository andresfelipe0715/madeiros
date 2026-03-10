---
name: factory-workflows
description: "Use when designing, building, or modifying manufacturing processes, inventory movements, or production tracking in this system."
risk: high
source: project
date_added: "2026-03-09"
---

# Factory Workflows

## Overview

Without this skill, factory operations become a mess of ad-hoc database updates, negative inventory balances, and untraceable material consumption. Factory mechanics demand strict state tracking, atomic operations, and physical-to-digital alignment.

**Core principle:** A factory workflow is a physical reality reflected in software; you cannot create material from nothing, and no state change happens without a verifiable transaction.

## The Iron Law

```
NEVER UPDATE INVENTORY OR PRODUCTION STATUS OUTSIDE OF AN ATOMIC, AUDITABLE TRANSACTION.
```

Bypassing explicit workflow logic to "just tweak the quantities" destroys the integrity of the system and creates irreconcilable discrepancies between the physical warehouse and the digital records.

## When to Use

Use for:
- Altering stock quantities, material consumption, or inventory transfers.
- Adding or modifying stages in a physical production or fulfillment process.
- Changing validation rules for orders, materials, or shipments.

ESPECIALLY use when:
- There is pressure to manually patch data for a one-off "edge case" or "quick fix."

Do NOT use when:
- Designing simple CRUD interfaces for master data (e.g., editing a material's name or color) that do not impact physical quantities or state flows.

## The Phases

You MUST complete each phase before proceeding.

### Phase 1: Define the Physical Boundaries

**Before you do anything:**

1. **Verify Physical Preconditions**
   - Identify what must physically exist or be true before this step can occur.
   - Assert these conditions in code (e.g., stock must be > 0, status must be "Pending").

2. **Define the Digital Postconditions**
   - Detail the exact state changes that represent the physical action.
   - Ensure corresponding deductions (e.g., material consumed) and additions (e.g., product created) are balanced.

### Phase 2: Implement the Atomic Transition

1. **Wrap in a Transaction**
   - Use `DB::transaction()` to ensure that if any part of the physical process encoding fails, the entire software state rolls back.
   
2. **Leave an Audit Trail**
   - Ensure the system records who performed the action, the timestamp, and the exact delta in quantities or state.
   - Do not overwrite history; append new events or adjustments (like Bodega transfers or explicit adjustments).

### Phase 3: Defensive Restrictions

1. **Lock Down Direct Edits**
   - Prevent users from directly editing critical aggregate values (like `stock_quantity`) in generic edit views.
   - Route all quantity mutations through explicit domain actions (e.g., "Receive Shipment", "Declare Scrap").

2. **Handle Failure Paths Explicitly**
   - Define exact behaviors for physical exceptions (e.g., insufficient material at the time of consumption).
   - Display clear, actionable error messages to the operator instead of generic system failures.

## Red Flags — STOP and Restart

If you are thinking:
- "I'll just add a quick input to let them edit the stock quantity directly."
- "It is just one field update, I don't need `DB::transaction()` here."
- "We can skip the audit log for this minor rollback."

**ALL of these mean: STOP. Return to Phase 1.**

## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. Boundaries** | Assert preconditions, balance postconditions. | Entry and exit requirements accurately reflect physical reality. |
| **2. Atomic Transition** | Use DB transactions and write audit logs. | Partial failures never leave corrupted quantities; history is traceable. |
| **3. Defensive Restrictions**| Disable direct quantity edits; route through domain actions. | The system enforces process over arbitrary data entry. |
