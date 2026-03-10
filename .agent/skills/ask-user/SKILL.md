---
name: ask-user
description: "Use when you are about to implement something but lack clarity, or when the user explicitly requests this skill."
risk: low
source: project
date_added: "2026-03-09"
---

# Ask User

## Overview

Agents often proceed with assumptions when requirements are ambiguous, leading to incorrect implementations.
This skill forces the agent to stop and explicitly ask the user for clarification before writing any code.

**Core principle:** Never guess the user's intent when requirements are missing or ambiguous.

## The Iron Law

```
NEVER WRITE CODE BASED ON ASSUMPTIONS.
ALWAYS ASK FOR CLARIFICATION BEFORE PROCEEDING.
```

Writing code based on guesses wastes time and introduces bugs that are hard to untangle later.

## When to Use

Use for:
- User prompt is vague or lacks specific details (e.g. "make it look better", "fix the bug").
- Multiple implementation approaches are valid and the user hasn't specified one.
- The user explicitly says "use the ask-user skill" or "/ask-user".

ESPECIALLY use when:
- The task involves architectural decisions or database schema changes where mistakes are costly to reverse.

Do NOT use when:
- The instruction is completely deterministic (e.g., "fix this typo", "run formatting").
- The implementation path is already crystal clear and fully documented in context.

## The Phases

You MUST complete each phase before proceeding.

### Phase 1: Identify Missing Information

**Before you do anything:**

1. **Review the Request**
   - Read the user request carefully.
   - List the components, files, or logic that need to be created or modified.

2. **Identify Ambiguities**
   - Note any missing requirements, design decisions, or edge cases that are not defined.
   - If there are zero ambiguities, do NOT proceed with this skill.

### Phase 2: Formulate Questions

**Before you ask the user:**

1. **Draft Clear Questions**
   - Write specific, concrete questions. Avoid open-ended "what do you mean?" questions.
   - Provide options if applicable (e.g. "Should I use approach A or approach B?").

2. **Group Questions**
   - Consolidate related questions so the user can address them in a single reply.

### Phase 3: Ask the User

**Before you continue work:**

1. **Send the Message**
   - Present the questions clearly to the user using the `notify_user` or standard messaging tools (when not in a task).
   - State what you are waiting for before you will proceed.

2. **Wait for Reply**
   - Stop execution and wait for the user to answer the questions. Do NOT implement anything.

### Phase 4: Validate the Answer

**After the user replies:**

1. **Check Completeness**
   - Review the user's answers against your questions.
   - If answers are still vague, repeat Phase 2 and 3.

2. **Proceed**
   - Once all ambiguities are resolved, proceed with the implementation.

## Red Flags — STOP and Restart

If you are thinking:
- "I think they probably mean..."
- "I'll just assume X for now and they can correct me later."
- "This might be what they want, let's try it."

**ALL of these mean: STOP. Return to Phase 1.**

## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. Identify** | Find ambiguities in the request | A list of clear unknowns is created |
| **2. Formulate** | Draft specific, actionable questions | Questions are precise, preferably with options |
| **3. Ask** | Present questions to the user and stop | The user receives the questions and execution pauses |
| **4. Validate** | Review answers for completeness | All ambiguities are resolved and implementation can begin safely |
