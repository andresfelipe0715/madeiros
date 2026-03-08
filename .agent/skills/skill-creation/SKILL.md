---
name: skill-creation
description: "Use when asked to create a new skill. Defines the structure, format, and quality bar for all skills in this project."
risk: low
source: project
date_added: "2026-03-07"
---

# Skill Creation

## Overview

Skills without clear structure are ignored or misapplied. Skills with too much abstraction are useless. A good skill is a precise, opinionated playbook that makes the right choice obvious and the wrong choice obvious to avoid.

**Core principle:** A skill must be so clear that an agent following it produces the same outcome regardless of context pressure, time pressure, or complexity of the surrounding task.

## The Iron Law

```
NEVER WRITE A SKILL THAT DESCRIBES GOALS.
ONLY WRITE SKILLS THAT DESCRIBE ACTIONS.
```

"Be careful with database changes" is not a skill instruction.
"Before running any migration: dump current schema, check for irreversible operations, verify rollback path." is.

---

## Anatomy of a Skill

Every skill lives in its own directory: `.agent/skills/<skill-name>/`. The main file is always `SKILL.md`.

### Required: SKILL.md Frontmatter

```yaml
---
name: skill-name            # kebab-case, matches directory name
description: "Single sentence. Starts with: Use when [trigger condition]."
risk: low | medium | high   # risk of misapplying this skill
source: community | project # community = external repo, project = written for this app
date_added: "YYYY-MM-DD"
---
```

**Rules:**
- `name` must exactly match the directory name
- `description` must start with "Use when" so the agent knows when to invoke it
- `risk` indicates how dangerous mistakes are if the skill is applied incorrectly

### Required: Sections in SKILL.md

Every `SKILL.md` must have the following sections in this order. Do NOT omit any.

#### 1. Overview (2–4 sentences)
State the problem the skill solves. Name the failure mode it prevents. Do NOT list features or goals.

```markdown
## Overview

[What goes wrong without this skill — in one sentence.]
[What the correct mindset is — in one sentence.]

**Core principle:** [One sentence that is a memorable mental anchor.]
```

#### 2. The Iron Law (optional but powerful)
If there is a single rule that must never be broken, declare it in a fenced code block.

```markdown
## The Iron Law

​```
NEVER DO X BEFORE Y.
​```

[One sentence explaining consequences of violating this.]
```

#### 3. When to Use
Be explicit. List concrete triggers, not vague scenarios. Use bullet points.

```markdown
## When to Use

Use for:
- [specific trigger A]
- [specific trigger B]

ESPECIALLY use when:
- [high-pressure scenario that makes agents want to skip the skill]

Do NOT use when:
- [scenario where this skill is overkill or wrong tool]
```

#### 4. The Phases (the main content)
Break the skill into 2–4 numbered phases. Each phase has named substeps. Substeps are numbered, concrete, and imperative ("Read X", "Run Y", "Verify Z"). No vague substeps.

```markdown
## The Phases

You MUST complete each phase before proceeding.

### Phase 1: [Name]

**Before you do anything:**

1. **[Action Name]**
   - [Concrete step]
   - [What to look for]

2. **[Action Name]**
   - [Concrete step]
```

#### 5. Red Flags
A checklist of thoughts or patterns that mean the agent is doing it wrong. This is the most important error-prevention section. Be ruthless.

```markdown
## Red Flags — STOP and Restart

If you are thinking:
- "[quote of wrong thought]"
- "[quote of wrong thought]"

**ALL of these mean: STOP. Return to Phase 1.**
```

#### 6. Quick Reference Table
A summary table with Phase | Key Actions | Success Criteria. Agents scan this under time pressure.

```markdown
## Quick Reference

| Phase | Key Actions | Success Criteria |
|-------|-------------|------------------|
| **1. X** | ... | ... |
| **2. Y** | ... | ... |
```

---

## Supporting Files

When a phase references a technique that needs more explanation, create a companion `.md` file in the same directory. Reference it from `SKILL.md` like:

```markdown
See `technique-name.md` in this directory for the complete procedure.
```

**Rules for companion files:**
- File name is kebab-case
- Starts with a `# Title` header
- Has an `## Overview` and `## When to Use`
- Includes concrete code examples or commands, not abstract descriptions
- Ends with a `## Real-World Impact` section to justify its existence

Common companion files:
- `example.md` — Worked example of applying the main skill
- `anti-patterns.md` — Common wrong approaches and why they fail
- `checklist.md` — Quick checklist for agents under time pressure

---

## Quality Bar

Before finalizing any skill, evaluate it against this checklist:

- [ ] Every instruction is a concrete action, not a goal
- [ ] Every phase has a clear success criterion (how do I know I'm done?)
- [ ] Red Flags section covers the most tempting shortcuts
- [ ] "When to Use" explicitly calls out the high-pressure cases
- [ ] Quick Reference table can be read in 10 seconds
- [ ] No section says "be careful" or "consider" — it says "do X" or "do NOT do Y"
- [ ] Frontmatter `description` starts with "Use when"
- [ ] Skill can be followed cold, with no extra context, by an agent who has never seen it

---

## Anti-Patterns for Skill Writing

| Bad | Why | Good |
|-----|-----|------|
| "Make sure to validate inputs" | No action specified | "Check every incoming request field against the Form Request rules before touching the database" |
| "Be careful with migrations" | Vague fear | "Before running any migration: read the SQL it generates, check for column drops, verify you have a rollback plan" |
| "Follow best practices" | Meaningless | Define the specific practice by name with steps |
| "Consider the user experience" | Opinion without action | "Verify the action works on mobile viewport, check ARIA labels, test keyboard navigation" |
| Long overview with no phases | Wall of text | Max 4 sentences in Overview, then go to phases |
| One giant phase | Not scannable | Max 4 phases, max 6 substeps per phase |

---

## Skill Directory Structure

```
.agent/skills/
└── <skill-name>/
    ├── SKILL.md              ← required: main instruction file
    ├── example.md            ← optional: worked example
    ├── anti-patterns.md      ← optional: what not to do
    ├── checklist.md          ← optional: quick reference checklist
    └── scripts/
        └── helper.sh         ← optional: executable scripts
```

---

## Real-World Impact

A skill written to this standard:
- Is executed consistently every invocation
- Does not get "adapted" by an agent who thinks they know better
- Survives context pressure (tight deadlines, user impatience)
- Can be updated incrementally without rewriting from scratch
- Produces the same result in the 1st invocation and the 100th
