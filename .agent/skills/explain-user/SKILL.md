---
name: explain-user
description: "Use when the user asks a question, expresses confusion, or needs a clear explanation of what to do next."
risk: low
source: project
date_added: "2026-03-13"
---

## Overview

When users ask questions, they often receive technical jargon or passive info-dumps that don't help them move forward. This skill ensures every explanation is actionable and direct.

**Core principle:** Every explanation must end with a clear "next step" for the user.

## The Iron Law

```
NEVER EXPLAIN "WHY" WITHOUT ALSO EXPLAINING "HOW TO FIX IT".
```

## When to Use

Use for:
- Direct questions from the user (e.g., "What does this do?", "How do I run this?")
- Error messages reported by the user that they don't understand
- Requests for clarification on technical concepts in the codebase

ESPECIALLY use when:
- The user seems frustrated or stuck in a loop.

Do NOT use when:
- The user is giving a direct command to execute code (e.g., "Implement X").
- The user is providing specific feedback on a plan.

## The Phases

You MUST complete each phase before proceeding.

### Phase 1: Contextual Analysis

1. **Identify the Core Confusion**
   - Read the user's prompt carefully to find the specific "gap" in their knowledge.
   - Don't assume they want a lecture; find the obstacle.

2. **Locate Source Truth**
   - Use `grep_search` or `view_file` to find the code or docs relevant to their question.
   - Verify the current state of the app before explaining anything.

### Phase 2: Actionable Explanation

1. **The "Human" Summary**
   - Explain the concept in 1-2 sentences using minimal jargon.
   - Relate it to their current task.

2. **The "How-To"**
   - Provide the specific command, file path, or action needed to proceed.
   - If multiple steps are involved, use a numbered list.

3. **The Call to Action**
   - Use `notify_user` to present the explanation and the next step.
   - Ask: "Would you like me to [perform action] for you?" if applicable.

## Red Flags — STOP and Restart

If you are thinking:
- "I should explain the history of the Laravel framework first."
- "I'll just paste the entire error log and let them read it."

**ALL of these mean: STOP. Return to Phase 1.**

## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. Analysis** | Find the "gap" and source truth | You know exactly what file/command solves the problem. |
| **2. Explanation** | Human summary + specific actions | The user knows exactly what to do next. |
