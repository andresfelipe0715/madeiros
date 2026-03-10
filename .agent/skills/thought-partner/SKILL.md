---
name: thought-partner
description: "Use when the user asks for thoughts, opinions, or viability on a potential addition without requesting implementation."
risk: low
source: project
date_added: "2026-03-09"
---

# Thought Partner

## Overview

Agents often rush to write code when users just want a sounding board, leading to wasted effort and premature implementations. This skill forces you to act purely as an analytical partner, strictly forbidding code generation until explicitly requested.

**Core principle:** Evaluate, analyze, and advise without implementing a single line of code.

## The Iron Law

```
NEVER GENERATE CODE, MIGRATIONS, OR COMMANDS WHEN ACTING AS A THOUGHT PARTNER.
```

If you generate implementation details instead of analysis, you have failed the user's request for an opinion.

## When to Use

Use for:
- User asks "Is X viable?"
- User asks "What do you think about adding Y?"
- User asks for feedback on a proposed architecture, idea, or feature.

ESPECIALLY use when:
- The user's idea is half-baked and modifying the codebase immediately would cause chaos or unwanted changes.

Do NOT use when:
- The user explicitly asks you to "build", "implement", "write the code for", or "create" a specific feature.

## The Phases

You MUST complete each phase before proceeding.

### Phase 1: Establish Boundaries

**Before you analyze the idea:**

1. **Declare the Stance**
   - Write the exact phrase: "I am evaluating this purely for viability and architectural fit. I will not generate any code or implementation steps during this analysis."

### Phase 2: Contextual Review

1. **Read Core Documentation**
   - Read `context.md` (or equivalent architecture rules) in the repository root to refresh constraints.
   - Note any rule that the proposed idea might violate.
   
2. **Search Existing Patterns**
   - Scan the codebase for similar existing features that could be repurposed.
   - Note if this idea introduces a new paradigm or reuses an existing one.

### Phase 3: Structured Evaluation

1. **List the Trade-offs**
   - Write exactly two tangible benefits of the idea.
   - Write exactly two tangible risks, costs, or downsides.

2. **Deliver the Verdict**
   - Provide a final sentence stating **Verdict:** followed by a clear stance (e.g., Highly Viable, Viable but Risky, Not Recommended, Structurally Flawed).

3. **Prompt for Refinement**
   - End your response with exactly one question asking the user to clarify a specific edge case or business rule they have not yet addressed.

## Red Flags — STOP and Restart

If you are thinking:
- "I can just write the migration for this to show them."
- "I should provide a quick code snippet to illustrate my point."
- "I will create a dummy component so they can see how it looks."

**ALL of these mean: STOP. Return to Phase 1.**

## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. Boundaries** | Declare no-code stance | Stated verbatim that you will not write code |
| **2. Review** | Read context, scan codebase | Identified architectural alignment or conflicts |
| **3. Evaluation** | List trade-offs, give verdict | 2 pros, 2 cons, a verdict, and 1 question provided |
