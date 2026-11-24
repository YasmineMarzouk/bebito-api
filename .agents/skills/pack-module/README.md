# Pack Module Codex Skill

## Install
Copy the `pack-module` directory into your repository's `.agents/skills/` directory.

Expected path:
`.agents/skills/pack-module/SKILL.md`

## Start Codex
Ask Codex to use the `pack-module` skill and begin with Task 01. It must read `docs/architecture/architecture.md` before making changes.

Example:
> Use the pack-module skill. Read its references and docs/architecture/architecture.md. Execute tasks 01–07 in order, then 08–11. Do not skip tests or report unexecuted tests as passed. Start with Task 01 and report your design before modifying files.

For safer iterative work, run one task at a time and review its report before proceeding.

## Contents
- `SKILL.md`: orchestrator and non-negotiable rules
- `references/`: business rules, architecture, inventory invariants, complete testing matrix
- `tasks/`: 11 focused implementation/testing/audit tasks
